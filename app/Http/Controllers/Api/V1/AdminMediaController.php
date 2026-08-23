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
use Illuminate\Support\Facades\Storage;

class AdminMediaController extends Controller
{
    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime'],
            'altText' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $parent = $this->parent($type, $id);
        $file = $data['file'];
        $mediaType = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        $disk = (string) config('filesystems.default');
        $key = $file->store("media/{$type}/{$parent->getKey()}", $disk);

        abort_if($key === false, 500, 'The media file could not be stored.');

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
    }

    private function resolveDiskUrl(string $disk, string $key): ?string
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