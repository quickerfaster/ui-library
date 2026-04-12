<?php

namespace QuickerFaster\UILibrary\Services\AccessControl;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use QuickerFaster\UILibrary\Services\System\ApplicationInfo;
use function Laravel\Prompts\error;
use App\Modules\Admin\Models\Permission;
use App\Models\User;

class AccessControlPermissionService
{


    const MSG_PERMISSION_DENIED = "You do not have permission to perform this action.";

    const PERMISSION_ACTIONS = ['view', 'create', 'edit', 'delete', 'print', 'export', 'import'];


    public static function checkPermission($action, $modelName): bool
    {

        // If not logged in return false
        if (!auth()->check()) {
            return false;
        }

        $permissionName = $action . "_" . Str::snake($modelName);
        return auth()->user()->hasPermissionTo($permissionName);
    }




    public static function checkPermissionsExistsOrCreate($resourceNames)
    {
        foreach ($resourceNames as $resourceName) {
            $permissionNames = self::getResourcePermissionNames($resourceName);
            foreach ($permissionNames as $permissionName) {
                if (!Permission::where("name", $permissionName)->first())
                    Permission::create(['name' => $permissionName, 'description' => 'Allow role or user to ' . str_replace('_', ' ', $permissionName)]);
            }
        }
    }


    // Get resoure permission name list
    public static function getResourcePermissionNames($resourceName)
    {
        $resourcePermissionNames = [];
        $resourceName = Str::snake($resourceName);
        foreach (self::PERMISSION_ACTIONS as $control) {
            $resourcePermissionNames[] = strtolower($control . "_" . $resourceName);
        }
        return $resourcePermissionNames;
    }



    public static function seedPermissionNames()
    {
        $modules = ApplicationInfo::getModuleNames();


        // Get all model names
        $modelNames = [];
        foreach ($modules as $module) {
            $moduleName = basename($module); // Get the module name from the directory
            $directory = app_path("Modules/" . $moduleName . "/Models");
            $namespace = addslashes("App\\Modules\\" . $moduleName . "\\Models\\");

            $modelNames = array_merge($modelNames, ApplicationInfo::getAllModelNames($directory, $namespace));
        }


        // Check if permissions exist or create them
        self::checkPermissionsExistsOrCreate($modelNames);


    }


    public static function isOwner($model, $id)
    {
        // Query the model record
        $model = $model::find($id);

        // Check if the model is a user and the user is accessing his profile
        if ($model instanceof User) {
            // Extract the last url part which is view or id
            $url = url()->previous(); // OR request()->headers->get('referer')
            $segments = explode("/", $url);
            $view = end($segments);
            if ($view == "my-profile")
                return $model->id == $id;
        }

        // Check other possible resource ownership 
        $user = auth()->user();
        if ($model->user_id == $user->id) {
            return true;
        } else if ($model->team_id == $user->current_team_id) {
            return true;
        } else if ($model->created_by == $user->id) {
            return true;
        } else if ($model->updated_by == $user->id) {
            return true;
        }
        return false;

    }












}



