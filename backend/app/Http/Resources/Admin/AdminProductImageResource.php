<?php

namespace App\Http\Resources\Admin;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = filter_var($this->path, FILTER_VALIDATE_URL)
            ? $this->path
            : $this->storedUrl();

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,

            'disk' => $this->disk,
            'path' => $this->path,

            'url' => $url,

            'alt_text' => $this->alt_text,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function storedUrl(): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->url($this->path);
    }
}
