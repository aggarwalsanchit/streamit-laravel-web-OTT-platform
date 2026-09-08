<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\View;

trait ModuleTrait
{
    protected string $moduleTitle;
    protected string $moduleName;
    protected string $moduleIcon;

    public function initializeModuleTrait(string $moduleTitle, string $moduleName, string $moduleIcon): void
    {
        $this->moduleTitle = $moduleTitle;
        $this->moduleName = $moduleName;
        $this->moduleIcon = $moduleIcon;

        View::share([
            'module_title' => $this->moduleTitle,
            'module_icon' => $this->moduleIcon,
            'module_name' => $this->moduleName,
        ]);
    }

    public function performBulkAction($model, $ids, $actionType, $moduleName)
{
    $message = __('messages.bulk_update');
    switch ($actionType) {
        case 'change-status':
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model));
            if ($usesSoftDeletes) {
                $model::query()->withoutGlobalScope(SoftDeletingScope::class)->whereIn('id', $ids)->update(['status' => request()->status]);
            } else {
                $model::whereIn('id', $ids)->update(['status' => request()->status]);
            }
            $message = trans('messages.status_updated');
            break;
        case 'delete':
            if (setting('demo_login')) {
                return response()->json(['message' => __('messages.permission_denied'), 'status' => false]);
            }
            // Special handling for Entertainment model to trigger model events
            if ($model == 'Modules\Entertainment\Models\Entertainment') {
                \Modules\Entertainment\Models\Entertainment::whereIn('id', $ids)->each(function($item) {
                    $item->delete();
                });
            } else {
                $model::whereIn('id', $ids)->delete();
            }
            $message = trans('messages.delete_form');
            break;
        case 'restore':
            // Special handling for Entertainment model to trigger model events
            if ($model == 'Modules\Entertainment\Models\Entertainment') {
                \Modules\Entertainment\Models\Entertainment::whereIn('id', $ids)->each(function($item) {
                    $item->restore();
                });
            } else {
                $model::whereIn('id', $ids)->restore();
            }
            $message = trans('messages.restore_form');
            break;

        case 'permanently-delete':
            // Special handling for Entertainment model to trigger model events
            if ($model == 'Modules\Entertainment\Models\Entertainment') {
                \Modules\Entertainment\Models\Entertainment::whereIn('id', $ids)->each(function($item) {
                    $item->forceDelete();
                });
            } else {
                $model::whereIn('id', $ids)->forceDelete();
            }
            $message = trans('messages.permanent_delete_form');
            break;
        default:
            return response()->json(['status' => false, 'message' => __('service_providers.invalid_action')]);
    }

    return response()->json(['status' => true, 'message' => $message]);
}

}
