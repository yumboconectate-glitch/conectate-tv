<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount(['channels', 'subscribers'])->orderBy('name')->get();
        $channels = Channel::orderBy('name')->get();

        return view('plans', compact('plans', 'channels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:plans,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_connections' => ['required', 'integer', 'min:1', 'max:20'],
            'channels' => ['array'],
            'channels.*' => ['integer', 'exists:channels,id'],
        ]);

        $plan = Plan::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_connections' => $data['max_connections'],
            'active' => true,
        ]);

        $plan->channels()->sync($data['channels'] ?? []);

        return back()->with('ok', 'Plan creado correctamente.');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'max_connections' => ['required', 'integer', 'min:1', 'max:20'],
            'active' => ['nullable', 'boolean'],
            'channels' => ['array'],
            'channels.*' => ['integer', 'exists:channels,id'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_connections' => $data['max_connections'],
            'active' => (bool) ($data['active'] ?? false),
        ]);

        $plan->channels()->sync($data['channels'] ?? []);

        return back()->with('ok', 'Plan actualizado.');
    }
}
