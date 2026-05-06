<?php

namespace App\Http\Controllers;

use App\Services\OpenApi\OpenApiGenerator;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(OpenApiGenerator $generator): JsonResponse
    {
        return response()->json($generator->generate());
    }
}
