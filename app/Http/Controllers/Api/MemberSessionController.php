<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RenewMemberSessionRequest;
use App\Models\MemberAuthSession;
use App\Services\Auth\MemberSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberSessionController extends Controller
{
    public function __construct(private readonly MemberSessionService $sessions) {}

    public function capabilities(): JsonResponse
    {
        return response()->json(['session_protocol' => ($this->sessions->enabled() && $this->sessions->schemaAvailable()) ? 2 : null])->header('Cache-Control', 'no-store');
    }

    public function renew(RenewMemberSessionRequest $request): JsonResponse
    {
        return response()->json($this->sessions->renew($request->validated('renewal_token')))->header('Cache-Control', 'no-store');
    }

    public function index(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        $current = $token instanceof \Laravel\Sanctum\PersonalAccessToken ? $token->getAttribute('member_session_id') : null;
        $items = $this->sessions->schemaAvailable() ? MemberAuthSession::query()->where('user_id', $request->user()->id)->whereNull('revoked_at')->orderByDesc('created_at')->get()->map(fn (MemberAuthSession $s): array => [
            'active' => $s->idle_expires_at->isFuture() && $s->absolute_expires_at->isFuture(),
            'id' => $s->id, 'device_name' => $s->device_name, 'remembered' => $s->remembered, 'current' => $current === $s->id,
            'created_at' => $s->created_at->toISOString(), 'last_renewed_at' => $s->last_renewed_at?->toISOString(),
            'idle_expires_at' => $s->idle_expires_at->toISOString(), 'absolute_expires_at' => $s->absolute_expires_at->toISOString(),
        ])->values() : [];

        return response()->json(['data' => $items])->header('Cache-Control', 'no-store');
    }

    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        abort_unless($this->sessions->schemaAvailable(), 404);
        $owned = MemberAuthSession::query()->where('user_id', $request->user()->id)->findOrFail($sessionId);
        $this->sessions->revoke($owned->id, 'device_logout', $request->user());

        return response()->json(['message' => 'Sesi perangkat dicabut.'])->header('Cache-Control', 'no-store');
    }
}
