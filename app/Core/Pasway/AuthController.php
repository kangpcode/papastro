<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Pasway;

use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * Controller to handle channel authentication and SSE fallback streaming.
 */
class AuthController
{
    /**
     * Authenticate a private or presence channel subscription request.
     */
    public function authenticate(Request $request): Response
    {
        $channel = (string) ($request->input('channel_name') ?? $request->input('channel') ?? '');
        $socketId = (string) ($request->input('socket_id') ?? '');

        if (!$channel || !$socketId) {
            return Response::json(['error' => 'Missing channel_name or socket_id'], 400);
        }

        // Get authenticated user from session or auth guard
        $user = function_exists('session') ? session('user') : null;
        if (!$user && function_exists('auth')) {
            $user = auth()->user();
        }

        // If private channel requires auth and no user found:
        // By default, allow if user exists or if in local/testing environment, else authorize
        $userData = null;
        if (str_starts_with($channel, 'presence-')) {
            $userId = (string) ($user['id'] ?? $user->id ?? 'guest_' . substr(md5($socketId), 0, 8));
            $userName = (string) ($user['name'] ?? $user->name ?? 'Guest User');

            $userData = [
                'user_id'   => $userId,
                'user_info' => [
                    'id'   => $userId,
                    'name' => $userName,
                ],
            ];
        }

        $auth = Pasway::auth($channel, $socketId, $userData);

        return Response::json($auth, 200);
    }

    /**
     * Handle Server-Sent Events (SSE) streaming endpoint.
     */
    public function sse(Request $request): Response
    {
        $channelsParam = (string) $request->query('channels', 'public');
        $channels = array_filter(array_map('trim', explode(',', $channelsParam)));
        $lastEventTime = (float) $request->header('Last-Event-ID', (string) $request->query('since', 0));

        $events = Pasway::getSseEvents($channels, $lastEventTime);

        $body = ": ping\n\n";
        foreach ($events as $event) {
            $dataStr = json_encode($event['data'] ?? []);
            $timeStr = (string) ($event['time'] ?? microtime(true));
            $body .= "id: {$timeStr}\n";
            $body .= "event: {$event['event']}\n";
            $body .= "data: {$dataStr}\n\n";
        }

        return Response::make($body, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-transform',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
