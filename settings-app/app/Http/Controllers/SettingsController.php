<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        // Get the ID from the session we bridged from your PHP files
        $userId = session('user_id');

        if (!$userId) {
            // If no session, redirect back to your original login page
            return redirect('../../login.php');
        }

        // Fetch user data from the database
        $user = DB::table('users')->where('id', $userId)->first();

        return view('settings', compact('user'));
    }

    public function update(Request $request)
    {
        $userId = session('user_id');

        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255', // Add this
        ]);

        $updateData = [
            'fullname' => $request->fullname,
            'email' => $request->email,    // Add this
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)->update($updateData);

        return back()->with('status', 'Profile Updated Successfully!');
    }
}
