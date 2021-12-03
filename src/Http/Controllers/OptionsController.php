<?php

namespace Hubertnnn\LaravelNova\Fields\DynamicSelect\Http\Controllers;

use Hubertnnn\LaravelNova\Fields\DynamicSelect\DynamicSelect;
use Illuminate\Routing\Controller;
use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Http\Requests\NovaRequest;

class OptionsController extends Controller
{
    public function index(NovaRequest $request)
    {
        $attribute = $request->input('attribute');
        $dependValues = $request->input('depends');

        if ($request->input('action')) {
            $field = $this->getFieldFromAction($request, $attribute);
        }
        else {
            $field = $this->getFieldFromResource($request, $attribute);
        }

        abort_if(is_null($field), 400);

        /** @var DynamicSelect $field */
        $options = $field->getOptions($dependValues);
        $default = $field->getDefaultValue($dependValues);

        return [
            'options' => $options,
            'default' => $default
        ];
    }

    private function getFieldFromResource(NovaRequest $request, $attribute) {
        $resource = $request->newResource();

        $field = null;

        // Nova tabs compatibility:
        // https://github.com/eminiarts/nova-tabs
        if (method_exists($resource, 'parentUpdateFields')) {
            $fields = $resource->parentUpdateFields($request);
            $field = $fields->findFieldByAttribute($attribute);
        } else {
            $fields = $resource->updateFields($request);
            $field = $fields->findFieldByAttribute($attribute);

            if (!$field) {
                $fields = $resource->fields($request);
            }
        }

        if (!isset($field)) {
            $field = $this->getFieldFromComplexFields($fields, $attribute);
        }

        return $field;
    }

    private function getFieldFromAction(NovaRequest $request, $attribute) {
        $class = $request->input('action');
        $action = new $class();

        $fields = $action->fields();
        $field = collect($fields)->first(function ($f) use ($attribute) {
            return $f->attribute === $attribute;
        });

        if (!$field) {
            $field = $this->getFieldFromComplexFields($fields, $attribute);
        }

        return $field;
    }

    private function getFieldFromComplexFields(array $fields, string $attribute) {
        $field = null;

        foreach ($fields as $updateField) {
            if ($updateField instanceof MergeValue) {
                foreach ($updateField->data as $mergedField) {
                    if ($mergedField->attribute === $attribute) {
                        $field = $mergedField;
                    }
                }
            }

            // Flexible content compatibility:
            // https://github.com/whitecube/nova-flexible-content
            if ($updateField->component == 'nova-flexible-content') {
                foreach ($updateField->meta['layouts'] as $layout) {
                    foreach ($layout->fields() as $layoutField) {
                        if ($layoutField->attribute === $attribute) {
                            $field = $layoutField;
                        }
                    }
                }

                // Dependency container compatibility:
                // https://github.com/epartment/nova-dependency-container
            } elseif ($updateField->component == 'nova-dependency-container') {
                $resolveNestedContainers = function ($novaDependencyContainer, $resolveCallable) use ($attribute, &$field) {
                    foreach ($novaDependencyContainer->meta['fields'] as $layoutField) {
                        if ($layoutField instanceof NovaDependencyContainer) {
                            $resolveCallable($layoutField, $resolveCallable);
                        } elseif ($layoutField->attribute === $attribute) {
                            $field = $layoutField;
                        }
                    }
                };

                $resolveNestedContainers($updateField, $resolveNestedContainers);

                // Conditional container compatibility:
                // https://github.com/dcasia/conditional-container
            } elseif ($updateField->component === 'conditional-container') {
                foreach ($updateField->fields as $layouts) {
                    if ($layouts->component === 'nova-flexible-content') {
                        foreach ($layouts->meta['layouts'] as $layout) {
                            foreach ($layout->fields() as $layoutField) {
                                if ($layoutField->attribute === $attribute) {
                                    $field = $layoutField;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $field;
    }
}
