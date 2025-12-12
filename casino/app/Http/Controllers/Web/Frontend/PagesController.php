<?php 
namespace VanguardLTE\Http\Controllers\Web\Frontend
{
    include_once(base_path() . '/app/ShopCore.php');
    include_once(base_path() . '/app/ShopGame.php');
    class PagesController extends \VanguardLTE\Http\Controllers\Controller
    {
        public function new_license()
        {
			/*
			$licensed = false;
			$licensed = true;
            $checked = new \VanguardLTE\Lib\LicenseDK();
            $license_notifications_array = $checked->aplVerifyLicenseDK(null, 0);
            if( $license_notifications_array['notification_case'] == 'notification_license_ok' ) 
            {
                $licensed = true;
            }
            if( !file_exists(resource_path() . '/views/system/pages/new_license.blade.php') ) 
            {
                abort(404);
            }
            return view('system.pages.new_license', compact('licensed'));
			*/
        }
        public function new_license_post(\Illuminate\Http\Request $request)
        {
            return redirect()->back()->withErrors([trans('app.license_is_already_installed')]);
        }

        public function error_license()
        {
            return view('system.pages.error_license');
        }
    }

}

