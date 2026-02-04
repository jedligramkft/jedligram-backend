<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use Illuminate\Http\Request;
use App\Http\Requests\StoreThreadRequest;

class ThreadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Thread::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreThreadRequest $request)
    {
        $thread = Thread::create($request->validated());
        return response()->json($thread, 201);
    }

    /**
     * Search for the specified resource.
     * for doc: LARAVEL SCOUT
     */
    public function search(Request $request)
    {
        if ($request->filled('search')) {
            $threads = Thread::search($request->input('search'))->get();
            if($threads->isEmpty()) {
                return response()->json(Thread::all());
            }
            return response()->json($threads);
        }
        // it works funny
        return response()->json(Thread::all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Thread $thread){
        return response()->json($thread);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Thread $thread)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Thread $thread)
    {
        //
    }
}
