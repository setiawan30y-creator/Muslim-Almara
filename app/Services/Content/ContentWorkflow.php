<?php

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Models\ContentReview;
use App\Models\DhikrItem;
use App\Models\DuaItem;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur publikasi konten agama (blueprint §5.3, §8.5):
 * draft → review → approved → published; dapat ditolak (kembali ke draft) atau ditangguhkan.
 * Aturan: pembuat/pengaju tidak dapat menyetujui konten yang sama; setiap perubahan teks
 * menaikkan versi, mengembalikan status ke draft, dan tercatat di audit.
 *
 * @2026-Alifiandra
 */
class ContentWorkflow
{
    public const TYPES = ['doa' => DuaItem::class, 'dzikir' => DhikrItem::class, 'panduan' => \App\Models\GuideItem::class];

    /** Kolom teks yang perubahannya wajib ditinjau ulang. */
    public const REVIEWED_FIELDS = ['title', 'arabic_text', 'transliteration', 'translation', 'source_reference', 'source_kind', 'count_target', 'context_note', 'category_id', 'body', 'example_ref', 'quiz_json'];

    public static function model(string $type): string
    {
        return self::TYPES[$type] ?? abort(404);
    }

    /** @param array<string, mixed> $data */
    public function create(string $type, array $data, User $editor): Model
    {
        $class = self::model($type);

        return DB::transaction(function () use ($class, $data, $editor, $type) {
            $item = $class::query()->create($data + [
                'review_status' => ContentStatus::Draft,
                'content_version' => 1,
                'created_by' => $editor->id,
                'translation_status' => 'verified',
            ]);
            Audit::log("content.{$type}.created", $item, ['title' => $item->title], $editor);

            return $item;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Model $item, array $data, User $editor, string $type): Model
    {
        return DB::transaction(function () use ($item, $data, $editor, $type) {
            $before = $item->only(self::REVIEWED_FIELDS);
            $item->fill($data);
            $changed = array_intersect(array_keys($item->getDirty()), self::REVIEWED_FIELDS);
            if ($changed) {
                $item->content_version = $item->content_version + 1;
                $item->review_status = ContentStatus::Draft;
                $item->created_by = $editor->id; // penyunting terakhir tidak boleh menyetujui versi ini
                $item->submitted_by = null;
                $item->reviewed_by = null;
                $item->reviewed_at = null;
                $item->published_at = null;
            }
            $item->save();
            Audit::log("content.{$type}.updated", $item, [
                'version' => $item->content_version,
                'changed' => array_values($changed),
                'before' => array_intersect_key($before, array_flip($changed)), // jejak versi lama
            ], $editor);

            return $item;
        });
    }

    public function transition(Model $item, string $action, User $actor, string $type, ?string $notes = null): Model
    {
        $status = $item->review_status;

        $need = match ($action) {
            'submit' => 'content.edit',
            'approve', 'reject' => 'content.review',
            'publish', 'suspend' => 'content.publish',
            'approve-publish' => 'content.review',
            default => abort(404),
        };
        if (! $actor->hasPermission($need) || ($action === 'approve-publish' && ! $actor->hasPermission('content.publish'))) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk tindakan ini.');
        }

        $allowed = match ($action) {
            'submit' => [ContentStatus::Draft],
            'approve', 'reject', 'approve-publish' => [ContentStatus::Review],
            'publish' => [ContentStatus::Approved, ContentStatus::Suspended],
            'suspend' => [ContentStatus::Published, ContentStatus::Approved],
        };
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => "Tindakan tidak dapat dilakukan pada status \"{$status->label()}\"."]);
        }

        if (in_array($action, ['approve', 'approve-publish'], true)) {
            if (in_array($actor->id, array_filter([$item->created_by, $item->submitted_by]), true)) {
                throw ValidationException::withMessages(['status' => 'Pembuat atau pengaju konten tidak dapat menyetujui kontennya sendiri.']);
            }
            // Bacaan Arab wajib disertai transliterasi & arti (Standar Wajib Bacaan); panduan tanpa bacaan cukup penjelasan.
            $needsReading = $type !== 'panduan' || trim((string) $item->arabic_text) !== '';
            if ($needsReading && (trim((string) $item->translation) === '' || trim((string) $item->transliteration) === '')) {
                throw ValidationException::withMessages(['status' => 'Konten wajib memiliki transliterasi dan arti Bahasa Indonesia sebelum disetujui.']);
            }
            if ($type === 'panduan' && trim((string) $item->body) === '') {
                throw ValidationException::withMessages(['status' => 'Materi panduan wajib memiliki penjelasan sebelum disetujui.']);
            }
        }

        return DB::transaction(function () use ($item, $action, $actor, $type, $notes) {
            switch ($action) {
                case 'submit':
                    $item->review_status = ContentStatus::Review;
                    $item->submitted_by = $actor->id;
                    break;
                case 'approve':
                case 'approve-publish':
                    $item->review_status = $action === 'approve' ? ContentStatus::Approved : ContentStatus::Published;
                    $item->reviewed_by = $actor->id;
                    $item->reviewed_at = now();
                    if ($action === 'approve-publish') {
                        $item->published_at = now();
                    }
                    break;
                case 'reject':
                    $item->review_status = ContentStatus::Draft;
                    $item->submitted_by = null;
                    break;
                case 'publish':
                    $item->review_status = ContentStatus::Published;
                    $item->published_at = now();
                    break;
                case 'suspend':
                    $item->review_status = ContentStatus::Suspended;
                    break;
            }
            $item->save();

            if (in_array($action, ['approve', 'approve-publish', 'reject'], true)) {
                ContentReview::query()->create([
                    'content_type' => $type,
                    'content_id' => $item->id,
                    'content_version' => $item->content_version,
                    'reviewer_id' => $actor->id,
                    'decision' => $action === 'reject' ? 'rejected' : 'approved',
                    'notes' => $notes,
                ]);
            }
            Audit::log("content.{$type}.{$action}", $item, ['version' => $item->content_version, 'status' => $item->review_status->value, 'notes' => $notes], $actor);

            return $item;
        });
    }
}
