<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class IndexController extends Controller
{
    public function index()
    {
        $users = User::paginate(15);

        return view('dashboard', ['users' => $users]);
    }

    public function AddNewUser()
    {
        return view('add-new-user');
    }

    public function StoreNewUser(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required',
            'email'    => 'required|email|unique:users',
            'password' => 'required',
        ]);

        $user           = new User();
        $user->name     = $request->name;
        $user->email    = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        // Check Token already in database?
        $user = User::where('email', $request->email)->first();
        $user->tokens()->delete();

        // Create Token
        $token = $user->createToken('web-token', ['user'])->plainTextToken;

        return view('show-token', ['token' => $token]);
    }

    public function DeleteUser($id)
    {
        $user = User::where('id', $id)->first();
        $user->tokens()->delete();
        $user->delete();


        return Redirect::back();
    }
}

