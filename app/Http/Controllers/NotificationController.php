<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationResource;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $notifications = $this->currentUser()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return NotificationResource::collection($notifications);
    }

    public function show(string $id)
    {
        $notification = $this->currentUser()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        return new NotificationResource($notification);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $success = $this->notificationService->markAsRead($this->currentUser(), $id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read',
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $success = $this->notificationService->markAllAsRead($this->currentUser());

        return response()->json([
            'success' => $success,
            'message' => $success ? 'All notifications marked as read' : 'Failed to mark all as read',
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($this->currentUser());

        return response()->json([
            'count' => $count,
        ]);
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $preference = $this->currentUser()->notificationPreference ?? new NotificationPreference;
        $preference->user_id = Auth::id();
        $preference->fill($validated);
        $preference->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'data' => $preference,
        ]);
    }

    public function getPreferences(): JsonResponse
    {
        $preference = $this->currentUser()->notificationPreference;

        return response()->json([
            'data' => $preference ?? [
                'email_enabled' => true,
                'database_enabled' => true,
                'push_enabled' => false,
                'channels' => ['mail', 'database'],
            ],
        ]);
    }

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
