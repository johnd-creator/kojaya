<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationResource;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        $notifications = $this->filteredNotifications($request)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return NotificationResource::collection($notifications);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 5), 1), 10);
        $notifications = $this->filteredNotifications($request)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => NotificationResource::collection($notifications)->resolve($request),
            'meta' => [
                'limit' => $limit,
                'unread_count' => $this->notificationService->getUnreadCount($this->currentUser()),
            ],
        ]);
    }

    public function summary(): JsonResponse
    {
        $notifications = $this->currentUser()
            ->notifications()
            ->get();

        $unread = $notifications->whereNull('read_at');

        return response()->json([
            'unread_count' => $unread->count(),
            'by_category' => $unread
                ->groupBy(fn ($notification): string => $this->notificationDataValue($notification->data, 'category', 'general'))
                ->map->count()
                ->all(),
            'by_severity' => $unread
                ->groupBy(fn ($notification): string => $this->notificationDataValue($notification->data, 'severity', 'info'))
                ->map->count()
                ->all(),
        ]);
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
                'whatsapp_enabled' => false,
                'whatsapp_phone' => null,
                'channels' => ['mail', 'database'],
                'categories' => [],
            ],
        ]);
    }

    protected function filteredNotifications(Request $request): MorphMany
    {
        $query = $this->currentUser()->notifications();

        if ($request->string('status')->toString() === 'unread' || $request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        if ($request->string('status')->toString() === 'read') {
            $query->whereNotNull('read_at');
        }

        foreach (['category', 'severity', 'event_type'] as $field) {
            if ($request->filled($field)) {
                $query->where("data->{$field}", $request->string($field)->toString());
            }
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>|string|null  $data
     */
    protected function notificationDataValue(array|string|null $data, string $key, string $default): string
    {
        $payload = is_array($data) ? $data : (json_decode((string) $data, true) ?: []);

        return (string) ($payload[$key] ?? $default);
    }

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
