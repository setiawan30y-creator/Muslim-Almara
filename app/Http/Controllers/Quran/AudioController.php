<?php

namespace App\Http\Controllers\Quran;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\QuranAudioTrack;
use App\Services\Quran\Audio\QuranFoundationClient;
use App\Services\Quran\Audio\SampleTone;
use App\Services\Quran\QuranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Streaming rekaman Qari ALMARA (blueprint §4.12): dapat diputar tanpa login,
 * mendukung HTTP Range (geser posisi). Rekaman yang belum terbit hanya untuk tim admin.
 * @2026-Alifiandra
 */
class AudioController extends Controller
{
    public function stream(Request $request, QuranAudioTrack $track)
    {
        $published = $track->review_status === ContentStatus::Published;
        abort_unless($published || $request->user()?->hasPermission('admin.access'), 404);

        $disk = Storage::disk($track->disk);
        abort_unless($disk->exists($track->path), 404);

        $headers = [
            'Content-Type' => $track->mime,
            'Cache-Control' => $published ? 'public, max-age=86400' : 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // Disk lokal → BinaryFileResponse (Range/206 otomatis). Disk awan → URL bertanda tangan sementara.
        if (method_exists($disk, 'path') && config("filesystems.disks.{$track->disk}.driver") === 'local') {
            return response()->file($disk->path($track->path), $headers);
        }

        return redirect()->away($disk->temporaryUrl($track->path, now()->addMinutes(30)));
    }

    /** Nada uji untuk mode simulasi Quran.Foundation (QF_MOCK=true). */
    public function sample(int $surah, QuranFoundationClient $qf, QuranService $quran)
    {
        abort_unless($qf->isMock(), 404);
        $s = $quran->surah($surah);
        abort_if(! $s, 404);

        // Disimpan sebagai berkas agar mendukung HTTP Range (geser posisi) seperti audio sungguhan.
        $file = storage_path('framework/cache/almara-nada-uji-'.min($s->ayah_count, SampleTone::MAX_AYAH).'.wav');
        if (! is_file($file)) {
            @mkdir(dirname($file), 0775, true);
            file_put_contents($file, SampleTone::wav($s->ayah_count));
        }

        return response()->file($file, ['Content-Type' => 'audio/wav', 'Cache-Control' => 'public, max-age=3600']);
    }
}
