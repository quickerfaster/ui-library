<div>
    @if($errors->any())
        <div class="alert alert-danger mt-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach($displayGroups as $groupKey => $group)
        <div class="mb-4">
            <h5>{{ $group['title'] ?? ucfirst($groupKey) }}</h5>
            <div class="row">
                @foreach($group['fields'] as $fieldName)
                    @if(!$this->isFieldHidden($fieldName, 'onNewForm'))
                        @php
                            $field = $this->getField($fieldName);
                        @endphp
                        <div class="col-md-6">
                            {!! $field->renderForm($this->fields[$fieldName] ?? null) !!}
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>