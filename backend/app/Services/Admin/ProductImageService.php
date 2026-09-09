<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProductImageService
{
    private const DISK = 'public';

    public function create(
        Product $product,
        array $data
    ): ProductImage {
        /** @var UploadedFile $file */
        $file = $data['image'];

        unset(
            $data['image'],
            $data['disk'],
            $data['path'],
            $data['product_id']
        );

        $path = $file->store(
            "products/{$product->id}",
            self::DISK
        );

        if ($path === false) {
            throw new RuntimeException(
                'The product image could not be stored.'
            );
        }

        try {
            return DB::transaction(
                function () use (
                    $product,
                    $data,
                    $path
                ): ProductImage {
                    /*
                     * Verrouillage du produit pour éviter
                     * deux images principales concurrentes.
                     */
                    Product::query()
                        ->whereKey(
                            $product->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $hasImages = $product
                        ->images()
                        ->exists();

                    $isPrimary = ! $hasImages
                        || (
                            $data['is_primary']
                            ?? false
                        );

                    if ($isPrimary) {
                        $product
                            ->images()
                            ->update([
                                'is_primary' => false,
                            ]);
                    }

                    unset(
                        $data['is_primary']
                    );

                    return $product
                        ->images()
                        ->create([
                            ...$data,

                            'disk' => self::DISK,
                            'path' => $path,
                            'is_primary' => $isPrimary,
                        ]);
                }
            );
        } catch (Throwable $exception) {
            Storage::disk(
                self::DISK
            )->delete(
                $path
            );

            throw $exception;
        }
    }

    public function update(
        Product $product,
        ProductImage $image,
        array $data
    ): ProductImage {
        $newPath = null;
        $oldDisk = $image->disk;
        $oldPath = $image->path;

        if (
            array_key_exists(
                'image',
                $data
            )
        ) {
            /** @var UploadedFile $file */
            $file = $data['image'];

            $newPath = $file->store(
                "products/{$product->id}",
                self::DISK
            );

            if ($newPath === false) {
                throw new RuntimeException(
                    'The replacement product image could not be stored.'
                );
            }
        }

        unset(
            $data['image'],
            $data['disk'],
            $data['path'],
            $data['product_id']
        );

        try {
            $updated = DB::transaction(
                function () use (
                    $product,
                    $image,
                    $data,
                    $newPath
                ): ProductImage {
                    Product::query()
                        ->whereKey(
                            $product->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($newPath !== null) {
                        $data['disk'] = self::DISK;
                        $data['path'] = $newPath;
                    }

                    $requestedPrimary = array_key_exists(
                        'is_primary',
                        $data
                    )
                        ? (bool) $data['is_primary']
                        : null;

                    if ($requestedPrimary === true) {
                        $product
                            ->images()
                            ->whereKeyNot(
                                $image->id
                            )
                            ->update([
                                'is_primary' => false,
                            ]);

                        $data['is_primary'] = true;
                    }

                    if (
                        $requestedPrimary === false
                        && $image->is_primary
                    ) {
                        unset(
                            $data['is_primary']
                        );

                        $replacement = $product
                            ->images()
                            ->whereKeyNot(
                                $image->id
                            )
                            ->orderBy(
                                'sort_order'
                            )
                            ->orderBy(
                                'id'
                            )
                            ->first();

                        if ($replacement !== null) {
                            $replacement->update([
                                'is_primary' => true,
                            ]);

                            $data['is_primary'] = false;
                        } else {
                            /*
                             * Une collection non vide conserve
                             * toujours une image principale.
                             */
                            $data['is_primary'] = true;
                        }
                    }

                    $image->update(
                        $data
                    );

                    $image->refresh();

                    return $image;
                }
            );
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                Storage::disk(
                    self::DISK
                )->delete(
                    $newPath
                );
            }

            throw $exception;
        }

        if (
            $newPath !== null
            && (
                $oldDisk !== self::DISK
                || $oldPath !== $newPath
            )
        ) {
            Storage::disk(
                $oldDisk
            )->delete(
                $oldPath
            );
        }

        return $updated;
    }

    public function delete(
        Product $product,
        ProductImage $image
    ): void {
        $disk = $image->disk;
        $path = $image->path;

        DB::transaction(
            function () use (
                $product,
                $image
            ): void {
                Product::query()
                    ->whereKey(
                        $product->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($image->is_primary) {
                    $replacement = $product
                        ->images()
                        ->whereKeyNot(
                            $image->id
                        )
                        ->orderBy(
                            'sort_order'
                        )
                        ->orderBy(
                            'id'
                        )
                        ->first();

                    if ($replacement !== null) {
                        $replacement->update([
                            'is_primary' => true,
                        ]);
                    }
                }

                $image->delete();
            }
        );

        Storage::disk(
            $disk
        )->delete(
            $path
        );
    }
}
