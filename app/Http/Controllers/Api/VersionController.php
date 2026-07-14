<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'kojaya-backend',
            'git_sha' => config('app.git_sha', $this->detectGitSha()),
            'built_at' => config('app.built_at', now()->toISOString()),
            'environment' => app()->environment(),
        ]);
    }

    private function detectGitSha(): ?string
    {
        $path = base_path('.git/HEAD');

        if (! is_readable($path)) {
            return null;
        }

        $head = trim((string) file_get_contents($path));

        if (str_starts_with($head, 'ref:')) {
            $refPath = base_path('.git/' . substr($head, 5));
            if (is_readable($refPath)) {
                return trim((string) file_get_contents($refPath));
            }
        }

        return $head !== '' ? $head : null;
    }
}
