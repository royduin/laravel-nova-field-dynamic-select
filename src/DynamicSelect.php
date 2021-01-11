<?php

namespace Hubertnnn\LaravelNova\Fields\DynamicSelect;

use Hubertnnn\LaravelNova\Fields\DynamicSelect\Traits\DependsOnAnotherField;
use Hubertnnn\LaravelNova\Fields\DynamicSelect\Traits\HasDynamicOptions;
use Laravel\Nova\Fields\Field;

class DynamicSelect extends Field
{
    use HasDynamicOptions;
    use DependsOnAnotherField;

    public $component = 'dynamic-select';

    public function resolve($resource, $attribute = null)
    {
        $this->extractDependentValues($resource);

        return parent::resolve($resource, $attribute);
    }

    public function placheholder($placeholder = 'Pick a value')
    {
        return $this->withMeta(['placeholder' => $placeholder]);
    }

    public function selectLabel($selectLabel = 'Press enter to select')
    {
        return $this->withMeta(['selectLabel' => $selectLabel]);
    }

    public function deselectLabel($deselectLabel = 'Press enter to remove')
    {
        return $this->withMeta(['deselectLabel' => $deselectLabel]);
    }

    public function selectedLabel($selectedLabel = 'Selected')
    {
        return $this->withMeta(['selectedLabel' => $selectedLabel]);
    }

    public function meta()
    {
        $this->meta = parent::meta();
        return array_merge([
            'options' => $this->getOptions($this->dependentValues),
            'dependsOn' => $this->getDependsOn(),
            'dependValues' => count($this->dependentValues) ? $this->dependentValues :  new \ArrayObject(),
        ], $this->meta);
    }
}
