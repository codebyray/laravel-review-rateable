<?php

namespace Codebyray\ReviewRateable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class Review extends Model
{
    protected ?Collection $imagesPendingDeletion = null;

    protected $casts = [
        'recommend' => 'boolean',
        'approved' => 'boolean',
    ];

    protected $fillable = [
        'reviewable_id',
        'reviewable_type',
        'user_id',
        'review',
        'department',
        'recommend',
        'approved',
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Store and attach an image to this review.
     *
     * A pre-generated thumbnail may be supplied. If it is omitted, the
     * ReviewImage thumbnail URL falls back to the original image URL.
     */
    public function addImage(
        File|UploadedFile $image,
        ?string $altText = null,
        File|UploadedFile|null $thumbnail = null
    ): ReviewImage
    {
        $this->ensureImageCanBeAdded($image);

        if ($altText !== null && mb_strlen($altText) > 255) {
            throw new InvalidArgumentException('Review image alt text may not exceed 255 characters.');
        }

        if ($thumbnail !== null) {
            $this->validateImageFile($thumbnail);
        }

        $disk = (string) config('review-rateable.images.disk', 'public');
        $name = Str::uuid()->toString().'.'.$this->extensionFor($image);
        $path = $this->imageDirectory().'/'.$name;
        $thumbnailPath = null;

        try {
            $path = $this->storeImageFile($image, $disk, $this->imageDirectory(), $name);

            if ($thumbnail !== null) {
                $thumbnailName = Str::beforeLast($name, '.').'.'.$this->extensionFor($thumbnail);
                $thumbnailPath = $this->thumbnailDirectory().'/'.$thumbnailName;
                $thumbnailPath = $this->storeImageFile(
                    $thumbnail,
                    $disk,
                    $this->thumbnailDirectory(),
                    $thumbnailName
                );
            }

            [$width, $height] = $this->imageDimensions($image);
            $nextOrder = (int) ($this->images()->max('sort_order') ?? -1) + 1;

            return $this->images()->create([
                'disk' => $disk,
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'original_name' => $image instanceof UploadedFile
                    ? $image->getClientOriginalName()
                    : $image->getFilename(),
                'mime_type' => (string) $image->getMimeType(),
                'size' => (int) $image->getSize(),
                'width' => $width,
                'height' => $height,
                'sort_order' => $nextOrder,
                'alt_text' => $altText,
            ]);
        } catch (Throwable $exception) {
            try {
                Storage::disk($disk)->delete(array_values(array_filter([$path, $thumbnailPath])));
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }

    /**
     * Store several images. Items may be File instances or arrays containing
     * image, alt_text, and thumbnail keys.
     */
    public function addImages(iterable $images): Collection
    {
        $items = collect($images);
        $maximum = (int) config('review-rateable.images.max_count', 10);

        if ($this->images()->count() + $items->count() > $maximum) {
            throw new InvalidArgumentException("A review may have at most {$maximum} images.");
        }

        $normalized = $items->map(function ($item) {
            if ($item instanceof File || $item instanceof UploadedFile) {
                $this->validateImageFile($item);

                return ['image' => $item, 'alt_text' => null, 'thumbnail' => null];
            }

            if (! is_array($item) || ! $this->isImageFile($item['image'] ?? null)) {
                throw new InvalidArgumentException('Each image must be a File instance or an array with an image key.');
            }

            if (isset($item['thumbnail']) && ! $this->isImageFile($item['thumbnail'])) {
                throw new InvalidArgumentException('Each thumbnail must be a File instance.');
            }

            $this->validateImageFile($item['image']);

            if (isset($item['thumbnail'])) {
                $this->validateImageFile($item['thumbnail']);
            }

            return [
                'image' => $item['image'],
                'alt_text' => $item['alt_text'] ?? null,
                'thumbnail' => $item['thumbnail'] ?? null,
            ];
        });

        $created = collect();

        try {
            foreach ($normalized as $item) {
                $created->push($this->addImage(
                    $item['image'],
                    $item['alt_text'],
                    $item['thumbnail']
                ));
            }
        } catch (Throwable $exception) {
            $created->each(function (ReviewImage $image) {
                $deleted = false;

                try {
                    $deleted = (bool) $image->deleteQuietly();
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }

                if ($deleted) {
                    $image->deleteFiles(force: true);
                }
            });

            throw $exception;
        }

        return $created;
    }

    public function removeImage(ReviewImage|int $image): bool
    {
        $reviewImage = $image instanceof ReviewImage
            ? $this->images()->find($image->getKey())
            : $this->images()->find($image);

        if ($reviewImage === null) {
            return false;
        }

        return (bool) $reviewImage->delete();
    }

    /**
     * Reorder every image attached to the review using an ordered list of IDs.
     */
    public function reorderImages(array $imageIds): bool
    {
        $requested = array_values(array_map('intval', $imageIds));
        $existing = $this->images()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($requested) !== count(array_unique($requested))
            || collect($requested)->sort()->values()->all() !== collect($existing)->sort()->values()->all()) {
            throw new InvalidArgumentException('The image order must contain every review image ID exactly once.');
        }

        return DB::connection($this->getConnectionName())->transaction(function () use ($requested) {
            foreach ($requested as $position => $imageId) {
                $this->images()->whereKey($imageId)->update(['sort_order' => $position]);
            }

            return true;
        });
    }

    public function scopeDepartment(Builder $query, $department): Builder
    {
        return $query->where('department', $department);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('review-rateable.user_model'));
    }

    protected static function booted(): void
    {
        static::deleting(function (Review $review) {
            if (! config('review-rateable.images.delete_files_on_delete', true)) {
                return;
            }

            if (! $review->relationLoaded('images')) {
                $schema = $review->getConnection()->getSchemaBuilder();

                if (! $schema->hasTable((new ReviewImage)->getTable())) {
                    return;
                }

                $review->load('images');
            }

            $review->imagesPendingDeletion = $review->images;
        });

        static::deleted(function (Review $review) {
            if ($review->imagesPendingDeletion === null) {
                return;
            }

            $images = $review->imagesPendingDeletion;
            $review->afterCommit(fn () => $images->each->deleteFiles());
        });
    }

    protected function ensureImageCanBeAdded(File|UploadedFile $image): void
    {
        $this->validateImageFile($image);
        $maximum = (int) config('review-rateable.images.max_count', 10);

        if ($this->images()->count() >= $maximum) {
            throw new InvalidArgumentException("A review may have at most {$maximum} images.");
        }
    }

    protected function validateImageFile(File|UploadedFile $image): void
    {
        if ($image instanceof UploadedFile && ! $image->isValid()) {
            throw new InvalidArgumentException('The review image upload is not valid.');
        }

        $mimeType = (string) $image->getMimeType();
        $allowed = (array) config('review-rateable.images.allowed_mime_types', []);

        if (! in_array($mimeType, $allowed, true)) {
            throw new InvalidArgumentException("The {$mimeType} MIME type is not allowed for review images.");
        }

        $maximumBytes = (int) config('review-rateable.images.max_file_size', 5120) * 1024;

        if ((int) $image->getSize() > $maximumBytes) {
            throw new InvalidArgumentException('The review image exceeds the configured maximum file size.');
        }

        if (@getimagesize($image->getPathname()) === false) {
            throw new InvalidArgumentException('The review image contents could not be read.');
        }
    }

    protected function storeImageFile(
        File|UploadedFile $image,
        string $disk,
        string $directory,
        string $name
    ): string
    {
        $path = Storage::disk($disk)->putFileAs($directory, $image, $name);

        if ($path === false) {
            throw new InvalidArgumentException('The review image could not be stored.');
        }

        return $path;
    }

    protected function imageDirectory(): string
    {
        return trim((string) config('review-rateable.images.directory', 'review-images'), '/')
            .'/'.$this->getKey();
    }

    protected function thumbnailDirectory(): string
    {
        return trim((string) config('review-rateable.images.thumbnail_directory', 'review-images/thumbnails'), '/')
            .'/'.$this->getKey();
    }

    protected function extensionFor(File|UploadedFile $image): string
    {
        return match ((string) $image->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => $image->guessExtension() ?: 'bin',
        };
    }

    protected function imageDimensions(File|UploadedFile $image): array
    {
        $dimensions = @getimagesize($image->getPathname());

        return $dimensions === false
            ? [null, null]
            : [(int) $dimensions[0], (int) $dimensions[1]];
    }

    protected function afterCommit(callable $callback): void
    {
        $connection = DB::connection($this->getConnectionName());

        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit($callback);

            return;
        }

        $callback();
    }

    protected function isImageFile(mixed $file): bool
    {
        return $file instanceof File || $file instanceof UploadedFile;
    }
}
