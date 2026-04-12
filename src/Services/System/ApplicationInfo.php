<?php

namespace QuickerFaster\UILibrary\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use App\Modules\Admin\Models\Permission;
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
        // Get all module directories
        $modules = File::directories(base_path('app/Modules'));

        // Loop through each module to load views, routes, and config files dynamically
        foreach ($modules as $module) {
            $moduleNames[] = basename($module); // Get the module name from the directory
        }

        return $moduleNames;
    }

    


    
}



