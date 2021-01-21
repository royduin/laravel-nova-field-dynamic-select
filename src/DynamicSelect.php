<?php

namespace Hubertnnn\LaravelNova\Fields\DynamicSelect;

use Hubertnnn\LaravelNova\Fields\DynamicSelect\Traits\DependsOnAnotherField;
use Hubertnnn\LaravelNova\Fields\DynamicSelect\Traits\HasDynamicOptions;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Field;

class DynamicSelect extends Field
{
    use HasDynamicOptions;
    use DependsOnAnotherField;

    public $component = 'dynamic-select';
    public $labelKey;
    public $multiselect = false;

    public function resolve($resource, $attribute = null)
    {
        $this->extractDependentValues($resource);

        return parent::resolve($resource, $attribute);
    }

    /**
     * Makes the field to manage a BelongsToMany relationship.
     * todo: dependsOn
     *
     * @param string $resourceClass The Nova Resource class for the other model.
     * @return DynamicSelect
     **/
    public function belongsToMany($resourceClass)
    {
        $model = $resourceClass::$model;
        $primaryKey = (new $model)->getKeyName();

        $this->resolveUsing(function ($value) use ($primaryKey, $resourceClass) {
            $value = collect($value)->map(function ($option) use ($primaryKey) {
                return [
                    'label' => $option->{$this->labelKey ?: 'name'},
                    'value' => $option->{$primaryKey},
                ];
            });

            return $value;
        });

        $this->fillUsing(function ($request, $model, $requestAttribute, $attribute) {
            $model::saved(function ($model) use ($attribute, $request) {
                // Validate
                if (!method_exists($model, $attribute)) {
                    throw new RuntimeException("{$model}::{$attribute} must be a relation method.");
                }

                $relation = $model->{$attribute}();

                if (!method_exists($relation, 'sync')) {
                    throw new RuntimeException("{$model}::{$attribute} does not appear to model a BelongsToMany or MorphsToMany.");
                }

                $values = collect($request->get($attribute))
                    ->filter(function ($v) {
                        return $v;
                    })
                    ->map(function ($v) {
                        return json_decode($v)->value;
                    })->toArray();

                // Sync
                $relation->sync($values ?? []);
            });
        });

        $this->multiselect = true;

        return $this;
    }

    protected function fillAttributeFromRequest(Request $request, $requestAttribute, $model, $attribute)
    {
        if ($this->multiselect) {
            $values = $request->input($requestAttribute) ?? null;

            if ($values && !empty($values)) {
                $values = collect($values)->map(function ($value) {
                    return json_decode($value);
                });

                $model->{$attribute} = $values;
            }
        }
    }

    public function multiselect($multiselect = true)
    {
        $this->multiselect = $multiselect;

        return $this;
    }

    public function labelKey($labelKey)
    {
        $this->labelKey = $labelKey;

        return $this;
    }

    public function meta()
    {
        $this->meta = parent::meta();
        return array_merge([
            'options' => $this->getOptions($this->dependentValues),
            'dependsOn' => $this->getDependsOn(),
            'dependValues' => count($this->dependentValues) ? $this->dependentValues :  new \ArrayObject(),
            'placeholder' => __('Pick a value'),
            'selectLabel' => __('Press enter to select'),
            'deselectLabel' => __('Press enter to remove'),
            'selectedLabel' => __('Selected'),
            'labelKey' => $this->labelKey,
            'multiselect' => $this->multiselect,
        ], $this->meta);
    }
}
