<?php

namespace QuickerFaster\UILibrary\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\File;


class ApplicationInfo
{




    public static function getAllModelNames($directory = null, $namespace = 'App\\Models\\')
    {


        if (!$directory) {
            $directory = app_path('Models');
        }

        $models = [];
        if (!file_exists($directory))
            return $models;
        
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $fullClassName = $namespace . str_replace(['/', '.php'], ['\\', ''], $relativePath);


            //if (class_exists($fullClassName)) {
                // Take class name out of the path
                $models[] = class_basename($fullClassName);
            //}
        }

        return $models;
    }






    public static function getModuleNames() {
        $moduleNames = [];

        // Scan business modules path from config
        $businessPath = config('ui-library.module_paths.business', base_path('app/Modules'));
        if (is_dir($businessPath)) {
            $modules = File::directories($businessPath);
            foreach ($modules as $module) {
                $moduleNames[] = basename($module);
            }
        }

        // Also scan core modules path
        $corePath = config('ui-library.module_paths.core');
        if ($corePath && is_dir($corePath)) {
            $coreModules = File::directories($corePath);
            foreach ($coreModules as $module) {
                $moduleNames[] = basename($module);
            }
        }

        return array_unique($moduleNames);
    }

    


    
}



