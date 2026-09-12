<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelCategory;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index()
    {
        $channels = Channel::with('category')
            ->withCount('plans')
            ->orderByRaw('channel_number IS NULL')
            ->orderBy('channel_number')
            ->orderBy('name')
            ->get();

        $categories = ChannelCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('channels', compact('channels', 'categories'));
    }

    public function update(Request $request, Channel $channel)
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:160'],
            'channel_number' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'category_id' => ['nullable', 'exists:channel_categories,id'],
            'published' => ['nullable', 'boolean'],
        ]);

        $channel->update([
            'display_name' => trim((string) ($data['display_name'] ?? '')) ?: null,
            'channel_number' => $data['channel_number'] ?? null,
            'logo_url' => trim((string) ($data['logo_url'] ?? '')) ?: null,
            'category_id' => $data['category_id'] ?? null,
            'published' => (bool) ($data['published'] ?? false),
        ]);

        return back()->with('ok', 'Canal actualizado.');
    }
}
