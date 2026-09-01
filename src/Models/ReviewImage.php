<?php

namespace Codebyray\ReviewRateable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReviewImage extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'thumbnail_path',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'sort_order',
        'alt_text',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    protected static function booted(): void
    {
        static::deleted(function (ReviewImage $image) {
            $connection = DB::connection($image->getConnectionName());
            $callback = fn () => $image->deleteFiles();

            if ($connection->transactionLevel() > 0) {
                $connection->afterCommit($callback);

                return;
            }

            $callback();
        });
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function thumbnailUrl(): string
    {
        return Storage::disk($this->disk)->url($this->thumbnail_path ?: $this->path);
    }

    /**
     * Delete the stored files without allowing a filesystem failure to undo
     * an otherwise successful database deletion.
     */
    public function deleteFiles(bool $force = false): bool
    {
        if (! $force && ! config('review-rateable.images.delete_files_on_delete', true)) {
            return true;
        }

        $paths = array_values(array_unique(array_filter([
            $this->path,
            $this->thumbnail_path,
        ])));

        if ($paths === []) {
            return true;
        }

        try {
            return Storage::disk($this->disk)->delete($paths);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
