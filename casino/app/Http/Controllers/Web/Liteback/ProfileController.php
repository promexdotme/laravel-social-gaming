<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use VanguardLTE\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function editPassword()
    {
        return view('liteback.profile.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();
        $user->password = $request->input('password'); // mutator will hash
        $user->save();

        return redirect()->route('liteback.profile.password')->with('success', 'Password updated.');
    }
}
