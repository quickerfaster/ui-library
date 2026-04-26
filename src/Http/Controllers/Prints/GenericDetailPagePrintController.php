<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Prints;

use Illuminate\Routing\Controller;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use Illuminate\Support\Str;

class GenericDetailPagePrintController extends Controller
{
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
        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $modelClass = $resolver->getModel();
        $record = $modelClass::findOrFail($id);

        // Load all relations defined in the config
        $relations = array_keys($resolver->getRelations());
        if (!empty($relations)) {
            $record->load($relations);
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