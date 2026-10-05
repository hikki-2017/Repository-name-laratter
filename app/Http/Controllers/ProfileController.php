<?php

namespace App\Http\Controllers;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if (auth()->user()->is($user)) {
            // 自分のページ：自分とフォローしているユーザの Tweet を取得
            $tweets = Tweet::timeline($user)->latest()->paginate(10);
        } else {
            // 他のユーザのページ：そのユーザの Tweet のみを取得
            $tweets = $user->tweets()->latest()->paginate(10);
        }

        // ユーザのフォロワーとフォローしているユーザを取得
        $user->load(['follows', 'followers']);

        return view('profile.show', compact('user', 'tweets'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
