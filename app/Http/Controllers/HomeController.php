<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\User;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//        return new UserResource(auth()->user());
//        return UserResource::collection(User::all());
        return view('home');
    }

    public function getFriends()
    {
        // 自分以外の人を表示する
        return UserResource::collection(User::where('id', '!=', auth()->id())->get());
    }
}
