# Dynamic select field for Laravel Nova (maintained repo)

This field allows you to dynamically fill contents of a select based on values in other dynamic select fields.

Field is based on [nova-belongs-to-dependency](https://github.com/manmohanjit/nova-belongs-to-dependency) but instead of selecting model, you can select custom values.

## Installation

```
composer require royduin/laravel-nova-field-dynamic-select
```

## Usage

Class has 5 special methods on top of default Select from Laravel Nova.
- `dependsOn` can take a list of other fields this one depends on. On initial page load the list will be null, so make sure to check for null before accessing.
- `default` can take a list of other fields this one depends on.
- `options` can be either an array or a callable.
- `forAction` to indicate that the dynamic select is running in an action.
- `colors` (optional) to choose which colors to use when highlighting options. Accepts an array with 4 different hexadecimal colors: 
  - `highlightBgColor`: Color of the background when the cursor is over an option
  - `highlightTextColor`: Color of the text when the cursor is over an option
  - `selectedHighlightBgColor`: Color of the background when the cursor is over a selected option
  - `selectedHighlightTextColor`: Color of the text when the cursor is over a selected option


If its a callable, it will receive array with selected dependency values as first argument and should return an array of items to be shown on the select field.


## Example

```
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        DynamicSelect::make('Country', 'country')
            ->options(['US' => 'United States', 'UK' => 'United Kingdom'])
            ->rules('required')
            ->colors([
                'highlightBgColor' => '#0f0',
                'highlightTextColor' => '#fff',
                'selectedHighlightBgColor' => '#f00',
                'selectedHighlightTextColor' => '#fff',
            ])
        ,

        DynamicSelect::make('Provider', 'provider')
            ->options(['PR' => 'Premium', 'ST' => 'Standard'])
            ->rules('required')
        ,

        DynamicSelect::make('Product', 'product')
            ->dependsOn(['country', 'provider'])
            ->options(function($values) { 
                if($values['country'] === 'UK' && $values['provider'] === 'PR') {
                    return ['A' => 'Fast shipping', 'B' => 'Normal shipping', 'C' => 'Free shipping'];
                } else {
                    return ['A' => 'Fast shipping', 'B' => 'Normal shipping'];
                }
            })
            ->default(function ($values) {
                if (! $values) {
                    return null;
                }
                if ($values['country'] === 'UK') {
                    return [
                        'label' => 'Normal shipping',
                        'value' => 'B'
                    ];
                }
            })
            ->rules('required')
        ,
    ];
}

```

## Example for use in Nova Actions

```
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        DynamicSelect::make('Country', 'country')
            ->forAction(self::class)
            ->options(['US' => 'United States', 'UK' => 'United Kingdom'])
            ->rules('required')
        ,

        DynamicSelect::make('Provider', 'provider')
            ->forAction(self::class)
            ->options(['PR' => 'Premium', 'ST' => 'Standard'])
            ->rules('required')
        ,

        DynamicSelect::make('Product', 'product')
            ->forAction(self::class)
            ->dependsOn(['country', 'provider'])
            ->options(function($values) { 
                if($values['country'] === 'UK' && $values['provider'] === 'PR') {
                    return ['A' => 'Fast shipping', 'B' => 'Normal shipping', 'C' => 'Free shipping'];
                } else {
                    return ['A' => 'Fast shipping', 'B' => 'Normal shipping'];
                }
            })
            ->rules('required')
        ,
    ];
}

```
