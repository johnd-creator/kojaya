<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\Procurement\VendorPerformanceService;
use Illuminate\Http\JsonResponse;

class ProcurementApiController extends Controller
{
    public function vendorPerformance(Vendor $vendor, VendorPerformanceService $service): JsonResponse
    {
        $snapshot = $service->calculate($vendor);

        return response()->json([
            'data' => [
                'vendor' => [
                    'id' => $vendor->id,
                    'code' => $vendor->code,
                    'name' => $vendor->name,
                    'status' => $vendor->status->value,
                    'rating' => $vendor->refresh()->rating,
                ],
                'performance' => [
                    'score' => (float) $snapshot->score,
                    'rating' => $snapshot->rating,
                    'on_time_delivery_rate' => (float) $snapshot->on_time_delivery_rate,
                    'quality_acceptance_rate' => (float) $snapshot->quality_acceptance_rate,
                    'purchase_order_count' => $snapshot->purchase_order_count,
                    'goods_receive_note_count' => $snapshot->goods_receive_note_count,
                    'calculated_at' => $snapshot->calculated_at?->toIso8601String(),
                    'breakdown' => $snapshot->breakdown,
                ],
            ],
        ]);
    }
}
