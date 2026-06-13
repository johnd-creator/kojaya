<?php

namespace App\Services\Cooperative;

use App\Models\PosProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PosProductImageService
{
    public function storeImage(PosProduct $product, UploadedFile $file): string
    {
        $disk = Storage::disk(config('filesystems.default') === 'local' ? 'public' : config('filesystems.default'));
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $filename = 'pos-products/'.Str::slug($product->sku ?: 'product').'-'.Str::random(8).'.'.strtolower($extension);

        $disk->put($filename, file_get_contents($file->getRealPath()));

        return $filename;
    }

    public function deleteImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http')) {
            return;
        }

        $disk = Storage::disk(config('filesystems.default') === 'local' ? 'public' : config('filesystems.default'));
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
