@php
    use QuickerFaster\UILibrary\Services\Config\Wizards\WizardConfigResolver;
    use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
    $resolver = new WizardConfigResolver($configKey);
    $linkFields = $resolver->getLinkFields();
@endphp

<div class="review-container">
    <h3 class="mb-4">Review Your Information</h3>
    <p class="text-muted mb-4">Please review all details before final submission.</p>

    @foreach($steps as $index => $step)
        @if(isset($step['model']) && isset($stepData[$index]))
            @php
                $modelClass = $step['model'];
                $record = $modelClass::find($stepData[$index]);
                if (!$record) continue;

                // Get model config key using the same method as Wizard
                $modelConfigKey = (new \QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard($configKey))
                    ->getModelConfigKey($modelClass);
                $configResolver = app(ConfigResolver::class, ['configKey' => $modelConfigKey]);
                $fieldDefinitions = $configResolver->getFieldDefinitions();
                $fieldGroups = $configResolver->getFieldGroups();
            @endphp

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $step['title'] }}</h5>
                </div>
                <div class="card-body">
                    @if(!empty($step['groups']))
                        @foreach($step['groups'] as $groupKey)
                            @if(isset($fieldGroups[$groupKey]))
                                <h6 class="text-primary mt-3 mb-2">{{ $fieldGroups[$groupKey]['title'] ?? $groupKey }}</h6>
                                <dl class="row">
                                    @foreach($fieldGroups[$groupKey]['fields'] as $fieldName)
                                        @if(isset($fieldDefinitions[$fieldName]))
                                            @php
                                                $def = $fieldDefinitions[$fieldName];
                                                $value = $record->$fieldName ?? null;

                                                // Handle relationships
                                                if (isset($def['relationship'])) {
                                                    $rel = $def['relationship'];
                                                    $dynamicProp = $rel['dynamic_property'] ?? $fieldName;
                                                    //if ($record->relationLoaded($dynamicProp)) {
                                                        $related = $record->$dynamicProp;
                                                        
                                                        if ($related) {
                                                            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                                                                $displayField = $rel['display_field'] ?? 'name';
                                                                $value = $related->pluck($displayField)->implode(', ');
                                                            } else {
                                                                $displayField = $rel['display_field'] ?? 'name';
                                                                $value = $related->$displayField ?? '';
                                                            }
                                                        } else {
                                                            $value = '';
                                                        }
                                                   // }
                                                } elseif (isset($def['options']) && is_array($def['options'])) {
                                                    $value = $def['options'][$value] ?? $value;
                                                }
                                            @endphp
                                            <dt class="col-sm-4">{{ $def['label'] ?? $fieldName }}</dt>
                                            <dd class="col-sm-8">{{ $value ?: '—' }}</dd>
                                        @endif
                                    @endforeach
                                </dl>
                            @endif
                        @endforeach
                    @else
                        <p class="text-muted">No data available for this step.</p>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>