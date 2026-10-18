<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

// Validasi unggah rekaman qari (blueprint §4.12). @2026-Alifiandra
class AudioUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('audio.upload');
    }

    public function rules(): array
    {
        $maxKb = (int) config('almara.audio.max_upload_mb', 150) * 1024;

        return [
            'reciter_id' => ['required', 'integer', 'exists:quran_reciters,id'],
            'surah_number' => ['required', 'integer', 'between:1,114'],
            'audio' => ['required', 'file', 'max:'.$maxKb, 'mimetypes:audio/mpeg,audio/mp3,audio/mp4,audio/x-m4a,audio/aac,audio/ogg,audio/opus,application/ogg,audio/wav,audio/x-wav,audio/wave,video/mp4'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:'.((int) config('almara.audio.max_duration_minutes', 240) * 60)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'segments' => ['nullable', 'string', 'max:20000'],
            'confirm_rights' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.mimetypes' => 'Format harus MP3, M4A/AAC, OGG/Opus, atau WAV.',
            'audio.uploaded' => 'Unggah gagal. Kemungkinan berkas melebihi batas PHP (upload_max_filesize / post_max_size).',
            'audio.max' => 'Ukuran berkas melebihi batas '.config('almara.audio.max_upload_mb', 150).' MB.',
            'confirm_rights.accepted' => 'Centang pernyataan bahwa rekaman ini milik platform / memiliki izin penggunaan.',
        ];
    }

    public function attributes(): array
    {
        return ['reciter_id' => 'qari', 'surah_number' => 'surah', 'audio' => 'berkas audio', 'duration_seconds' => 'durasi'];
    }
}
