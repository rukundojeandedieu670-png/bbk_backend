<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaAssetResource;
use App\Models\Event;
use App\Models\Hub;
use App\Models\MediaAsset;
use App\Models\NewsPost;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminMediaController extends Controller
{
    private const MAX_UPLOAD_KB = 8192;

    public function show(int $id)
    {
        $asset = MediaAsset::query()->findOrFail($id);

        return Storage::disk($asset->disk)->response($asset->object_key);
    }

    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $fileInput = $request->file('file') ?: $request->file('image') ?: $request->file('photo') ?: $request->file('media');

        $data = $request->validate([
            'file' => ['nullable', 'file', 'max:'.self::MAX_UPLOAD_KB, 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime'],
            'image' => ['nullable', 'file', 'max:'.self::MAX_UPLOAD_KB, 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime'],
            'photo' => ['nullable', 'file', 'max:'.self::MAX_UPLOAD_KB, 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime'],
            'media' => ['nullable', 'file', 'max:'.self::MAX_UPLOAD_KB, 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime'],
            'altText' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $file = $fileInput ?? $data['file'] ?? $data['image'] ?? $data['photo'] ?? $data['media'];

        abort_unless($file instanceof \Illuminate\Http\UploadedFile, 422, 'A valid photo or video file is required.');

        $parent = $this->parent($type, $id);
        $mediaType = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        $disk = (string) config('filesystems.default');
        if ($disk === 'local') {
            $disk = 'public';
        }
        if (app()->environment('production') && $disk !== 's3') {
            return response()->json([
                'message' => 'Image uploads are not configured for R2 object storage. The record was saved without media.',
                'error' => 'media_storage_misconfigured',
            ], 503);
        }
        try {
            $key = $file->store("media/{$type}/{$parent->getKey()}", $disk);
        } catch (Throwable $exception) {
            Log::error('Media upload failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'disk' => $disk,
                'type' => $type,
                'parent_id' => $parent->getKey(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return response()->json([
                'message' => 'The image could not be uploaded to object storage. The record was saved without media.',
                'error' => 'media_storage_unavailable',
            ], 503);
        }

        if ($key === false) {
            return response()->json([
                'message' => 'The image could not be uploaded to object storage. The record was saved without media.',
                'error' => 'media_storage_unavailable',
            ], 503);
        }

        $asset = null;
        try {
            $url = $this->resolveDiskUrl($disk, $key);

            $asset = $parent->media()->create([
                'type' => $mediaType,
                'disk' => $disk,
                'object_key' => $key,
                'url' => $url,
                'alt_text' => $data['altText'] ?? null,
                'sort_order' => $data['sortOrder'] ?? 0,
            ]);

            return (new MediaAssetResource($asset))->response()->setStatusCode(201);
        } catch (Throwable $exception) {
            Log::error('Media upload finalization failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'disk' => $disk,
                'type' => $type,
                'parent_id' => $parent->getKey(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            $asset?->delete();
            try {
                Storage::disk($disk)->delete($key);
            } catch (Throwable $cleanupException) {
                Log::warning('Failed to clean up media object after finalization failure.', [
                    'exception' => $cleanupException::class,
                    'message' => $cleanupException->getMessage(),
                    'disk' => $disk,
                    'object_key' => $key,
                ]);
            }

            return response()->json([
                'message' => 'The image was uploaded but could not be linked to the saved record.',
                'error' => 'media_finalization_failed',
            ], 500);
        }
    }

    protected function resolveDiskUrl(string $disk, string $key): ?string
    {
        $storage = Storage::disk($disk);

        if (method_exists($storage, 'url')) {
            return $storage->url($key);
        }

        return null;
    }

    public function destroy(string $type, int $id, int $mediaId): JsonResponse
    {
        $parent = $this->parent($type, $id);
        $asset = $parent->media()->findOrFail($mediaId);
        Storage::disk($asset->disk)->delete($asset->object_key);
        $asset->delete();

        return response()->json(['message' => 'Media asset deleted.']);
    }

    private function parent(string $type, int $id): Hub|Program|Story|Event|Partner|NewsPost
    {
        $model = match ($type) {
            'hubs' => Hub::class,
            'programs' => Program::class,
            'stories' => Story::class,
            'events' => Event::class,
            'partners' => Partner::class,
            'news' => NewsPost::class,
            default => abort(404),
        };

        return $model::query()->findOrFail($id);
    }
}