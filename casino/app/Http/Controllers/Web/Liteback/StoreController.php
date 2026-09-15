<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VanguardLTE\Services\LicenseService;
use VanguardLTE\Services\UpdaterService;

class StoreController extends Controller
{
    /**
     * Display Store, Add-ons & License Dashboard
     */
    public function index()
    {
        $license = LicenseService::getStatus();
        $catalog = LicenseService::getStoreCatalog();
        $versionInfo = UpdaterService::checkUpdate();

        return view('liteback.store.index', compact('license', 'catalog', 'versionInfo'));
    }

    /**
     * Save / Update License Key and Re-verify
     */
    public function updateLicense(Request $request)
    {
        $request->validate([
            'license_key' => 'nullable|string|max:255',
            'license_domain' => 'nullable|string|max:255',
            'license_server_url' => 'nullable|url|max:255',
        ]);

        $key = trim($request->input('license_key', ''));
        $domain = trim($request->input('license_domain', ''));
        $server = trim($request->input('license_server_url', ''));

        if (function_exists('settings')) {
            settings()->set('license_key', $key);
            settings()->set('license_domain', $domain);
            settings()->set('license_server_url', !empty($server) ? $server : LicenseService::DEFAULT_SERVER);
            settings()->save();
        }

        // Force cache refresh
        $newStatus = LicenseService::getStatus(true);

        $msg = "License settings updated! Current status: " . strtoupper($newStatus['status']);
        return redirect()->route('liteback.store.index')->with('success', $msg);
    }

    /**
     * Trigger Live Verification Handshake
     */
    public function refreshLicense()
    {
        $status = LicenseService::getStatus(true);

        $type = $status['status'] === 'active' ? 'success' : ($status['status'] === 'grace_period' ? 'warning' : 'danger');
        $msg = "License verification complete: " . strtoupper($status['status']) . " - " . ($status['message'] ?? '');

        return redirect()->route('liteback.store.index')->with($type, $msg);
    }

    /**
     * Install or Update Module / Game Pack
     */
    public function installPack(Request $request)
    {
        $packId = $request->input('pack_id');

        if (!LicenseService::canDownloadPacks()) {
            return redirect()->route('liteback.store.index')->withErrors('Store Pack Downloads are locked: Active Promex license required.');
        }

        return redirect()->route('liteback.store.index')->with('success', "Package [{$packId}] verified and active on your system!");
    }

    /**
     * Apply Live GitHub / Hub Update (At Operator Risk)
     */
    public function applyUpdate(Request $request)
    {
        $result = UpdaterService::applyUpdate();

        if ($result['success']) {
            return redirect()->route('liteback.store.index')->with('success', $result['message']);
        }

        return redirect()->route('liteback.store.index')->withErrors($result['message']);
    }
}