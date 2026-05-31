@props(['name', 'wireModel', 'customAttributes' => [], 'rowId', 'fieldName'])

<input type="password"
       {{ $attributes->merge($customAttributes)->merge([
           'class' => 'form-control form-control-sm',
           'wire:model' => $wireModel,
           'autocomplete' => 'new-password',
           'id' => "inline_{$rowId}_{$fieldName}",
       ]) }}
>