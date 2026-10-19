<?php

namespace App\Services\Quran\Audio;

use App\Enums\ContentStatus;
use App\Models\ContentReview;
use App\Models\QuranAudioTrack;
use App\Models\QuranReciter;
use App\Models\QuranSurah;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alur rekaman Qari ALMARA (blueprint §4.12):
 * unggah → karantina (disk privat, status draft) → ajukan review → disetujui → terbit.
 * - Validasi tanda tangan berkas (bukan hanya ekstensi), ukuran, durasi, checksum SHA-256.
 * - Pengunggah/pengaju tidak dapat menyetujui rekamannya sendiri.
 * - Setiap unggahan untuk qari+surah yang sama menjadi versi baru; versi terbit lama diarsipkan.
 * @2026-Alifiandra
 */
class AudioWorkflow
{
    /** Tanda tangan berkas → [mime kanonis, ekstensi]. */
    public static function sniff(string $path): ?array
    {
        $h = @fopen($path, 'rb');
        if (! $h) {
            return null;
        }
        $head = (string) fread($h, 16);
        fclose($h);
        if (strlen($head) < 12) {
            return null;
        }

        return match (true) {
            str_starts_with($head, 'ID3'), ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0 => ['audio/mpeg', 'mp3'],
            substr($head, 4, 4) === 'ftyp' => ['audio/mp4', 'm4a'],
            str_starts_with($head, 'OggS') => ['audio/ogg', 'ogg'],
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WAVE' => ['audio/wav', 'wav'],
            default => null,
        };
    }

    /** Durasi WAV (detik) dari header; format lain memakai durasi dari metadata peramban. */
    public static function wavDuration(string $path): ?int
    {
        $data = @file_get_contents($path, false, null, 0, 4096);
        if (! $data || ! str_starts_with($data, 'RIFF')) {
            return null;
        }
        $fmt = strpos($data, 'fmt ');
        $dat = strpos($data, 'data');
        if ($fmt === false || $dat === false) {
            return null;
        }
        $byteRate = unpack('V', substr($data, $fmt + 16, 4))[1] ?? 0;
        $size = unpack('V', substr($data, $dat + 4, 4))[1] ?? 0;
        if ($byteRate <= 0) {
            return null;
        }
        $size = min($size, max(0, filesize($path) - $dat - 8));

        return (int) max(1, round($size / $byteRate));
    }

    /**
     * Format per baris: "ayat mulai selesai", waktu dalam detik atau m:ss(.d). Contoh: "1 0:00 0:06.5".
     * @return array<int, array{ayah_number: int, start_ms: int, end_ms: int}>
     */
    public static function parseSegments(?string $text, int $ayahCount, ?int $durationSeconds): array
    {
        $out = [];
        $lines = preg_split('/\R/u', trim((string) $text)) ?: [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/[\s;,\t]+/', $line);
            $row = $i + 1;
            if (count($parts) !== 3 || ! ctype_digit($parts[0])) {
                throw ValidationException::withMessages(['segments' => "Baris {$row}: format harus \"ayat mulai selesai\"."]);
            }
            [$ayah, $start, $end] = [(int) $parts[0], self::toMs($parts[1]), self::toMs($parts[2])];
            if ($ayah < 1 || $ayah > $ayahCount) {
                throw ValidationException::withMessages(['segments' => "Baris {$row}: ayat {$ayah} di luar jumlah ayat surah ({$ayahCount})."]);
            }
            if ($start === null || $end === null || $end <= $start) {
                throw ValidationException::withMessages(['segments' => "Baris {$row}: waktu tidak valid (selesai harus setelah mulai)."]);
            }
            if ($durationSeconds && $end > ($durationSeconds + 1) * 1000) {
                throw ValidationException::withMessages(['segments' => "Baris {$row}: waktu melebihi durasi rekaman."]);
            }
            if (isset($out[$ayah])) {
                throw ValidationException::withMessages(['segments' => "Baris {$row}: ayat {$ayah} tercantum dua kali."]);
            }
            $out[$ayah] = ['ayah_number' => $ayah, 'start_ms' => $start, 'end_ms' => $end];
        }
        ksort($out);

        return array_values($out);
    }

    private static function toMs(string $v): ?int
    {
        if (preg_match('/^(\d+):([0-5]?\d(?:\.\d{1,3})?)$/', $v, $m)) {
            return (int) round(((int) $m[1] * 60 + (float) $m[2]) * 1000);
        }
        if (preg_match('/^\d+(?:\.\d{1,3})?$/', $v)) {
            return (int) round((float) $v * 1000);
        }

        return null;
    }

    /** @param array{reciter_id: int, surah_number: int, duration_seconds?: ?int, notes?: ?string, segments?: ?string} $data */
    public function upload(UploadedFile $file, array $data, User $editor): QuranAudioTrack
    {
        $tmp = $file->getRealPath();
        $kind = self::sniff($tmp);
        if (! $kind) {
            throw ValidationException::withMessages(['audio' => 'Berkas bukan audio yang didukung (MP3, M4A/AAC, OGG/Opus, atau WAV).']);
        }
        $duration = $kind[1] === 'wav' ? self::wavDuration($tmp) : null;
        $duration ??= isset($data['duration_seconds']) ? (int) $data['duration_seconds'] : null;
        $max = (int) config('almara.audio.max_duration_minutes', 240) * 60;
        if (! $duration || $duration < 1) {
            throw ValidationException::withMessages(['duration_seconds' => 'Durasi rekaman tidak terbaca. Isi durasi (detik) secara manual.']);
        }
        if ($duration > $max) {
            throw ValidationException::withMessages(['duration_seconds' => 'Durasi melebihi batas '.intdiv($max, 60).' menit.']);
        }

        $surah = QuranSurah::query()->where('number', $data['surah_number'])->firstOrFail();
        $segments = self::parseSegments($data['segments'] ?? null, $surah->ayah_count, $duration);
        $reciter = QuranReciter::query()->findOrFail($data['reciter_id']);
        $disk = (string) config('almara.audio.disk', 'local');
        $checksum = hash_file('sha256', $tmp);

        $dup = QuranAudioTrack::query()->where('reciter_id', $reciter->id)->where('surah_number', $surah->number)->where('checksum', $checksum)
            ->whereNot('review_status', ContentStatus::Archived->value)->first();
        if ($dup) {
            throw ValidationException::withMessages(['audio' => "Berkas yang sama sudah diunggah (versi {$dup->version})."]);
        }

        $path = sprintf('quran-audio/%s/%03d-%s.%s', $reciter->slug, $surah->number, Str::lower((string) Str::ulid()), $kind[1]);
        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        try {
            return DB::transaction(function () use ($reciter, $surah, $disk, $path, $file, $kind, $duration, $checksum, $data, $editor, $segments) {
                $version = (int) QuranAudioTrack::query()->where('reciter_id', $reciter->id)->where('surah_number', $surah->number)->lockForUpdate()->max('version') + 1;
                $track = QuranAudioTrack::query()->create([
                    'reciter_id' => $reciter->id,
                    'surah_number' => $surah->number,
                    'version' => $version,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                    'mime' => $kind[0],
                    'size_bytes' => $file->getSize(),
                    'duration_seconds' => $duration,
                    'checksum' => $checksum,
                    'review_status' => ContentStatus::Draft,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $editor->id,
                ]);
                if ($segments) {
                    $track->segments()->createMany($segments);
                }
                Audit::log('audio.uploaded', $track, ['reciter' => $reciter->slug, 'surah' => $surah->number, 'version' => $version, 'checksum' => $checksum], $editor);

                return $track;
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    public function updateSegments(QuranAudioTrack $track, ?string $text, User $editor): void
    {
        if (! $editor->hasPermission('audio.upload')) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk tindakan ini.');
        }
        if (! in_array($track->review_status, [ContentStatus::Draft], true)) {
            throw ValidationException::withMessages(['segments' => 'Penanda ayat hanya dapat diubah saat rekaman berstatus draf.']);
        }
        $segments = self::parseSegments($text, (int) $track->surah?->ayah_count, $track->duration_seconds);
        DB::transaction(function () use ($track, $segments, $editor) {
            $track->segments()->delete();
            $track->segments()->createMany($segments);
            $track->forceFill(['created_by' => $editor->id])->save(); // penyunting terakhir tidak boleh menyetujui
            Audit::log('audio.segments_updated', $track, ['count' => count($segments)], $editor);
        });
    }

    public function transition(QuranAudioTrack $track, string $action, User $actor, ?string $notes = null): QuranAudioTrack
    {
        $need = match ($action) {
            'submit', 'delete' => 'audio.upload',
            'approve', 'reject' => 'content.review',
            'publish', 'suspend' => 'content.publish',
            'approve-publish' => 'content.review',
            default => abort(404),
        };
        if (! $actor->hasPermission($need) || ($action === 'approve-publish' && ! $actor->hasPermission('content.publish'))) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk tindakan ini.');
        }
        $allowed = match ($action) {
            'submit', 'delete' => [ContentStatus::Draft],
            'approve', 'reject', 'approve-publish' => [ContentStatus::Review],
            'publish' => [ContentStatus::Approved, ContentStatus::Suspended],
            'suspend' => [ContentStatus::Published, ContentStatus::Approved],
        };
        if (! in_array($track->review_status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => "Tindakan tidak dapat dilakukan pada status \"{$track->review_status->label()}\"."]);
        }
        if (in_array($action, ['approve', 'approve-publish'], true) && in_array($actor->id, array_filter([$track->created_by, $track->submitted_by]), true)) {
            throw ValidationException::withMessages(['status' => 'Pengunggah atau pengaju tidak dapat menyetujui rekamannya sendiri.']);
        }

        if ($action === 'delete') {
            DB::transaction(function () use ($track, $actor) {
                Audit::log('audio.deleted', $track, ['surah' => $track->surah_number, 'version' => $track->version], $actor);
                $track->delete();
            });
            Storage::disk($track->disk)->delete($track->path);

            return $track;
        }

        return DB::transaction(function () use ($track, $action, $actor, $notes) {
            switch ($action) {
                case 'submit':
                    $track->review_status = ContentStatus::Review;
                    $track->submitted_by = $actor->id;
                    break;
                case 'approve':
                case 'approve-publish':
                    $track->review_status = $action === 'approve' ? ContentStatus::Approved : ContentStatus::Published;
                    $track->reviewed_by = $actor->id;
                    $track->reviewed_at = now();
                    break;
                case 'reject':
                    $track->review_status = ContentStatus::Draft;
                    $track->submitted_by = null;
                    break;
                case 'publish':
                    $track->review_status = ContentStatus::Published;
                    break;
                case 'suspend':
                    $track->review_status = ContentStatus::Suspended;
                    break;
            }
            if ($track->review_status === ContentStatus::Published) {
                $track->published_at = now();
                // Hanya satu versi terbit per qari+surah; versi lama diarsipkan (tetap ada untuk jejak audit).
                QuranAudioTrack::query()->where('reciter_id', $track->reciter_id)->where('surah_number', $track->surah_number)
                    ->whereKeyNot($track->id)->where('review_status', ContentStatus::Published->value)
                    ->update(['review_status' => ContentStatus::Archived->value]);
            }
            $track->save();

            if (in_array($action, ['approve', 'approve-publish', 'reject'], true)) {
                ContentReview::query()->create([
                    'content_type' => 'audio',
                    'content_id' => $track->id,
                    'content_version' => $track->version,
                    'reviewer_id' => $actor->id,
                    'decision' => $action === 'reject' ? 'rejected' : 'approved',
                    'notes' => $notes,
                ]);
            }
            Audit::log("audio.{$action}", $track, ['surah' => $track->surah_number, 'version' => $track->version, 'status' => $track->review_status->value, 'notes' => $notes], $actor);

            return $track;
        });
    }
}
