<?php

namespace App\Http\Controllers;

use App\Models\AuthSession;
use App\Models\Device;
use App\Services\AstraSessionService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Throwable;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with(['subscriber.plan', 'lastChannel'])
            ->orderByDesc('last_seen_at')
            ->get();

        return view('devices', compact('devices'));
    }

    public function rename(Request $request, Device $device, ActivityLogger $logger)
    {
        $data = $request->validate([
            'custom_name' => ['nullable', 'string', 'max:100'],
        ]);

        $device->update([
            'custom_name' => trim((string) ($data['custom_name'] ?? '')) ?: null,
        ]);
        $logger->log('device.renamed', $device->subscriber, ['device_id' => $device->id, 'name' => $device->custom_name]);

        return back()->with('ok', 'Nombre del dispositivo actualizado.');
    }

    public function toggleBlock(Device $device, AstraSessionService $sessions, ActivityLogger $logger)
    {
        $blocking = !$device->blocked;

        $device->update([
            'blocked' => $blocking,
            'blocked_at' => $blocking ? now() : null,
            'block_reason' => $blocking ? 'Bloqueado desde Conectate TV' : null,
        ]);

        if ($blocking) {
            $this->closeMatchingSessions($device, $sessions);
        }

        $logger->log($blocking ? 'device.blocked' : 'device.unblocked', $device->subscriber, ['device_id' => $device->id]);

        return back()->with('ok', $blocking
            ? 'Dispositivo bloqueado y sesiones cerradas.'
            : 'Dispositivo habilitado.');
    }

    public function toggleIpBlock(Device $device, AstraSessionService $sessions, ActivityLogger $logger)
    {
        if (!$device->client_ip) {
            return back()->with('warn', 'El dispositivo no tiene una IP registrada.');
        }

        $blocking = !Device::where('subscriber_id', $device->subscriber_id)
            ->where('client_ip', $device->client_ip)
            ->where('ip_blocked', true)
            ->exists();

        Device::where('subscriber_id', $device->subscriber_id)
            ->where('client_ip', $device->client_ip)
            ->update([
                'ip_blocked' => $blocking,
                'blocked_at' => $blocking ? now() : null,
            ]);

        if ($blocking) {
            $this->closeMatchingSessions($device, $sessions, true);
        }

        $logger->log($blocking ? 'device.ip_blocked' : 'device.ip_unblocked', $device->subscriber, ['device_id' => $device->id, 'ip' => $device->client_ip]);

        return back()->with('ok', $blocking
            ? 'IP bloqueada para este abonado.'
            : 'IP habilitada para este abonado.');
    }

    public function closeSessions(Device $device, AstraSessionService $sessions)
    {
        $closed = $this->closeMatchingSessions($device, $sessions);

        return back()->with('ok', "Sesiones cerradas: {$closed}.");
    }

    private function closeMatchingSessions(
        Device $device,
        AstraSessionService $sessions,
        bool $byIpOnly = false
    ): int {
        $query = AuthSession::where('subscriber_id', $device->subscriber_id)
            ->where('active', true);

        if ($device->client_ip) {
            $query->where('client_ip', $device->client_ip);
        }

        if (!$byIpOnly && $device->user_agent) {
            $query->where('user_agent', $device->user_agent);
        }

        $closed = 0;

        foreach ($query->get() as $session) {
            try {
                $sessions->close($session->astra_session_id);
                $closed++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $closed;
    }
}
