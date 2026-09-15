<?php

namespace VanguardLTE\Http\Controllers\Web\Backend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemDocsController extends Controller
{
    /**
     * Display Interactive Admin System Documentation Desk
     */
    public function index()
    {
        $user = Auth::user();

        // Enforce Admin Verification
        if (!$user || (!$user->hasRole('admin') && !$user->is_admin)) {
            // If non-admin, check role_id
            if ($user && $user->role_id != 6) {
                return redirect()->route('frontend.game.list')->with('error', 'Access Denied: Admin privileges required.');
            }
        }

        return view('backend.docs.index', compact('user'));
    }
}
