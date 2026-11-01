<?php

namespace App\Http\Controllers\Worship;

use App\Http\Controllers\Controller;
use App\Models\AdzanSound;
use App\Services\Adzan\AdzanAudio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Suara adzan untuk pemutar di perangkat pengguna — @2026-Alifiandra
class AdzanController extends Controller
{
    public function audio(Request $request, AdzanSound $sound)
    {
        $published = $sound->status === 'published';
        abort_unless($published || $request->user()?->hasPermission('admin.access'), 404);
        $disk = Storage::disk($sound->disk);
        abort_unless($disk->exists($sound->path), 404);
        $headers = ['Content-Type' => $sound->mime, 'Cache-Control' => $published ? 'public, max-age=604800' : 'no-store, private', 'X-Content-Type-Options' => 'nosniff'];
        if (config("filesystems.disks.{$sound->disk}.driver") === 'local') {
            return response()->file($disk->path($sound->path), $headers);
        }

        return redirect()->away($disk->temporaryUrl($sound->path, now()->addMinutes(30)));
    }

    public function chime()
    {
        $file = storage_path('app/adzan-chime-v1.wav');
        if (! is_file($file)) {
            @file_put_contents($file, AdzanAudio::chimeWav());
        }

        return response()->file($file, ['Content-Type' => 'audio/wav', 'Cache-Control' => 'public, max-age=2592000']);
    }

    public function sounds(AdzanAudio $adzan)
    {
        return response()->json(['data' => $adzan->sounds(), 'meta' => ['defaults' => $adzan->defaults()]]);
    }
}
