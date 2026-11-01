<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdzanSound;
use App\Models\Role;
use App\Services\Quran\Audio\AudioWorkflow;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin: suara adzan — unggah (draf) → disetujui → aktif dipilih pengguna.
 * Penyetuju harus berbeda dari pengunggah, kecuali peran Admin platform (tercatat "self_approved" di audit).
 * @2026-Alifiandra
 */
class AdzanController extends Controller
{
    public function index()
    {
        return view('admin.adzan.index', [
            'sounds' => AdzanSound::query()->with(['creator:id,name', 'approver:id,name'])->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('audio.upload'), 403);
        $maxKb = (int) config('almara.adzan.max_upload_mb', 20) * 1024;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(array_keys(AdzanSound::KINDS))],
            'audio' => ['required', 'file', 'max:'.$maxKb],
            'duration_seconds' => ['nullable', 'integer', 'min:10', 'max:900'],
            'license_note' => ['required', 'string', 'max:500'],
            'confirm_rights' => ['accepted'],
        ], [
            'confirm_rights.accepted' => 'Centang pernyataan bahwa rekaman ini milik sendiri atau berizin digunakan di aplikasi beriklan.',
            'audio.max' => 'Ukuran berkas melebihi '.config('almara.adzan.max_upload_mb', 20).' MB.',
        ], ['name' => 'nama', 'kind' => 'jenis', 'audio' => 'berkas audio', 'license_note' => 'keterangan izin']);

        $file = $request->file('audio');
        $kind = AudioWorkflow::sniff($file->getRealPath());
        if (! $kind) {
            throw ValidationException::withMessages(['audio' => 'Berkas bukan audio yang didukung (MP3, M4A/AAC, OGG/Opus, atau WAV).']);
        }
        $checksum = hash_file('sha256', $file->getRealPath());
        if (AdzanSound::query()->where('checksum', $checksum)->exists()) {
            throw ValidationException::withMessages(['audio' => 'Berkas yang sama sudah diunggah.']);
        }
        $duration = $kind[1] === 'wav' ? AudioWorkflow::wavDuration($file->getRealPath()) : null;

        $disk = (string) config('almara.audio.disk', 'local');
        $path = 'adzan/'.$data['kind'].'-'.Str::lower((string) Str::ulid()).'.'.$kind[1];
        Storage::disk($disk)->putFileAs('adzan', $file, basename($path));

        $sound = AdzanSound::query()->create([
            'name' => $data['name'], 'kind' => $data['kind'], 'disk' => $disk, 'path' => $path, 'mime' => $kind[0],
            'size_bytes' => (int) $file->getSize(), 'duration_seconds' => $duration ?? ($data['duration_seconds'] ?? null),
            'checksum' => $checksum, 'license_note' => $data['license_note'], 'status' => 'draft', 'created_by' => $request->user()->id,
        ]);
        Audit::log('adzan.uploaded', $sound, ['name' => $sound->name, 'kind' => $sound->kind]);

        return back()->with('status', "Rekaman \"{$sound->name}\" tersimpan sebagai draf. Dengarkan lalu setujui agar bisa dipilih pengguna.");
    }

    public function transition(Request $request, AdzanSound $sound, string $action)
    {
        $user = $request->user();
        switch ($action) {
            case 'approve':
                abort_unless($user->hasPermission('content.publish'), 403);
                $self = $sound->created_by === $user->id;
                if ($self && ! $user->hasRole(Role::ADMIN)) {
                    return back()->withErrors(['approve' => 'Rekaman harus disetujui oleh pengguna lain (bukan pengunggah).']);
                }
                $sound->update(['status' => 'published', 'approved_by' => $user->id, 'approved_at' => now()]);
                Audit::log('adzan.approved', $sound, ['self_approved' => $self]);

                return back()->with('status', "\"{$sound->name}\" aktif dan dapat dipilih pengguna.");
            case 'deactivate':
                abort_unless($user->hasPermission('content.publish'), 403);
                $sound->update(['status' => 'inactive']);
                Audit::log('adzan.deactivated', $sound);

                return back()->with('status', "\"{$sound->name}\" dinonaktifkan.");
            case 'delete':
                abort_unless($user->hasPermission('content.publish') || ($sound->created_by === $user->id && $sound->status === 'draft'), 403);
                Storage::disk($sound->disk)->delete($sound->path);
                Audit::log('adzan.deleted', null, ['id' => $sound->id, 'name' => $sound->name]);
                $sound->delete();

                return back()->with('status', 'Rekaman dihapus.');
        }
        abort(404);
    }
}
