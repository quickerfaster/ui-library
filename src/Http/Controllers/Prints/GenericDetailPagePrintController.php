<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Prints;

use QuickerFaster\UILibrary\Concerns\ResolvesModels;
use Illuminate\Routing\Controller;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use Illuminate\Support\Str;

class GenericDetailPagePrintController extends Controller
{
    use ResolvesModels;

    protected $fieldFactory;

    public function __construct(FieldFactory $fieldFactory)
    {
        $this->fieldFactory = $fieldFactory;
    }

    protected function getDetailUrl($configKey, $record)
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $module = $resolver->getModuleName();
        $modelPlural = \Str::plural(\Str::kebab($resolver->getModelName()));

        // If your detail route follows a pattern like /{module}/{modelPlural}/{id}
        return url("/{$module}/{$modelPlural}/{$record->id}");
    }

    public function show($configKey, $id)
    {
        // Validate configKey resolves to a valid class
        try {
            $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
            $modelClass = $resolver->getModel();
        } catch (\Exception $e) {
            abort(404, 'Print configuration not found.');
        }

        // Validate ID is numeric and positive
        if (!is_numeric($id) || (int) $id <= 0) {
            abort(404, 'Invalid record identifier.');
        }

        // Safe resolution
        $record = $this->resolveModel($modelClass, (int) $id);

        if (!$record) {
            abort(404, 'The record you are trying to print could not be found.');
        }

        // Authorization check — ensure the user can view this record
        if (method_exists($record, 'getPolicy') || \Gate::has('view', $modelClass)) {
            $this->authorize('view', $record);
        }

        // Load all relations defined in the config
        $relationConfigs = $resolver->getRelations();
        if (!empty($relationConfigs)) {
            // Only eager-load relations that actually exist on the model.
            // This prevents crashes when a config defines a relation (e.g. a
            // module-specific relation like HR's 'profile') that the underlying
            // model class doesn't implement.
            $validRelations = array_filter($relationConfigs, function ($config, $name) use ($record) {
                return method_exists($record, $name);
            }, ARRAY_FILTER_USE_BOTH);
            if (!empty($validRelations)) {
                $record->load(array_keys($validRelations));
            }
        }

        $fieldGroups = $resolver->getFieldGroups();
        $fieldDefinitions = $resolver->getFieldDefinitions();
        $hiddenFields = $resolver->getHiddenFields();
        $modelName = $resolver->getModelName();
        $moduleName = $resolver->getModuleName();

        // Pass everything to a plain Blade view
        return view('qf::print.generic-detail', compact(
            'record',
            'fieldGroups',
            'fieldDefinitions',
            'hiddenFields',
            'modelName',
            'moduleName',
            'configKey'
        ));
    }
}