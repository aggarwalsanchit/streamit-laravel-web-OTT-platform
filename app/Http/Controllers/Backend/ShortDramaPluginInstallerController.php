<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShortDramaPluginInstallerRequest;
use App\Services\ShortDrama\ShortDramaAddonStatus;
use App\Services\ShortDrama\ShortDramaInstallStatus;
use App\Services\ShortDrama\ShortDramaPluginInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShortDramaPluginInstallerController extends Controller
{
    public function __construct(
        private readonly ShortDramaPluginInstaller $installer,
    ) {}

    public function index()
    {
        return view('backend.short-drama.installer', [
            'installed' => ShortDramaAddonStatus::isPresent(),
            'statusChecks' => ShortDramaInstallStatus::checks(),
            'module_title' => __('messages.short_drama_installer_heading'),
            'module_name' => 'short-drama',
        ]);
    }

    public function install(ShortDramaPluginInstallerRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $this->installer->install($request->file('addon_zip'));

            $message = __('messages.short_drama_installer_install_success');
            $redirect = route('backend.short-drama.installer', ['installed' => 1]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => $redirect,
                    'installed' => ShortDramaAddonStatus::isPresent(),
                    'statusChecks' => ShortDramaInstallStatus::checksForApi(),
                ]);
            }

            return redirect()
                ->to($redirect)
                ->with('success', $message);
        } catch (RuntimeException $e) {
            $error = __('messages.short_drama_installer_install_failed', ['error' => $e->getMessage()]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error,
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $error);
        }
    }

    public function uninstall(Request $request): RedirectResponse
    {
        if ($request->input('confirm') !== 'REMOVE_SHORT_DRAMA') {
            return redirect()
                ->back()
                ->with('error', __('messages.short_drama_installer_uninstall_confirm_required'));
        }

        try {
            $this->installer->uninstall();

            return redirect()
                ->route('backend.short-drama.installer')
                ->with('success', __('messages.short_drama_installer_uninstall_success'));
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', __('messages.short_drama_installer_uninstall_failed', ['error' => $e->getMessage()]));
        }
    }
}
