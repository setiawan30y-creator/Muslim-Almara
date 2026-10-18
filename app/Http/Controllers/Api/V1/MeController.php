<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccentColor;
use App\Enums\QuranReadingMode;
use App\Enums\TextSize;
use App\Enums\ThemeMode;
use App\Http\Resources\BookmarkResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\UserResource;
use App\Models\QuranVerse;
use App\Models\UserBookmark;
use App\Models\UserNote;
use App\Services\Account\PersonalDataService;
use App\Services\Preferences\PreferenceService;
use App\Services\UserProgress\ReadingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Endpoint data pribadi /api/v1/me/* — semua dibatasi ke pemilik akun (Policy + relasi user).
 * @2026-Alifiandra
 */
class MeController extends ApiController
{
    public function show(Request $request)
    {
        return $this->ok(new UserResource($request->user()->load('roles')));
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $request->user()->update($data);

        return $this->ok(new UserResource($request->user()->load('roles')));
    }

    // ------------------------------------------------------------- bookmark

    public function bookmarks(Request $request)
    {
        return $this->paginated($request->user()->bookmarks()->with('verse.surah')->latest()->paginate($this->perPage()), BookmarkResource::class);
    }

    public function storeBookmark(Request $request)
    {
        $data = $request->validate(['verse_id' => ['required', 'integer', 'exists:quran_verses,id'], 'label' => ['nullable', 'string', 'max:100']]);
        $b = $request->user()->bookmarks()->firstOrCreate(['verse_id' => $data['verse_id']], ['label' => $data['label'] ?? null]);

        return $this->ok(new BookmarkResource($b->load('verse.surah')), [], $b->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyBookmark(Request $request, UserBookmark $bookmark)
    {
        $this->authorize('delete', $bookmark);
        $bookmark->delete();

        return $this->ok(['deleted' => true]);
    }

    // -------------------------------------------------------------- catatan

    public function notes(Request $request)
    {
        $data = $request->validate(['verse_id' => ['nullable', 'integer']]);

        return $this->paginated($request->user()->notes()->with('verse.surah')
            ->when($data['verse_id'] ?? null, fn ($q, $v) => $q->where('verse_id', $v))
            ->latest('updated_at')->paginate($this->perPage()), NoteResource::class);
    }

    public function storeNote(Request $request)
    {
        $data = $request->validate(['verse_id' => ['nullable', 'integer', 'exists:quran_verses,id'], 'body' => ['required', 'string', 'max:5000']]);
        $n = $request->user()->notes()->create($data);

        return $this->ok(new NoteResource($n->load('verse.surah')), [], 201);
    }

    public function updateNote(Request $request, UserNote $note)
    {
        $this->authorize('update', $note);
        $note->update($request->validate(['body' => ['required', 'string', 'max:5000']]));

        return $this->ok(new NoteResource($note->load('verse.surah')));
    }

    public function destroyNote(Request $request, UserNote $note)
    {
        $this->authorize('delete', $note);
        $note->delete();

        return $this->ok(['deleted' => true]);
    }

    // ------------------------------------------------------------- progres

    public function readingProgress(Request $request, ReadingProgressService $progress)
    {
        $last = $request->user()->lastRead()->with('verse.surah')->first();

        return $this->ok([
            'last_read' => $last ? ['verse_id' => $last->verse_id, 'surah' => $last->verse->surah->number, 'ayah' => $last->verse->ayah_number, 'updated_at' => $last->updated_at?->toIso8601String()] : null,
            'stats' => $progress->stats($request->user()),
        ]);
    }

    public function updateReadingProgress(Request $request, ReadingProgressService $progress)
    {
        $data = $request->validate([
            'verse_id' => ['required', 'integer', 'exists:quran_verses,id'],
            'mark_read' => ['nullable', 'boolean'],
            'from_ayah' => ['nullable', 'integer', 'min:1'],
        ]);
        $verse = QuranVerse::query()->findOrFail($data['verse_id']);
        $count = 0;
        if ($request->boolean('mark_read')) {
            $count = $progress->markRead($request->user(), $verse, $data['from_ayah'] ?? null);
        } else {
            $progress->setLastRead($request->user(), $verse);
        }

        return $this->ok(['recorded' => $count, 'stats' => $progress->stats($request->user())]);
    }

    public function readingGoals(Request $request)
    {
        return $this->ok($request->user()->activeReadingGoal?->only(['daily_target', 'start_date', 'khatam_target_date', 'status']));
    }

    public function updateReadingGoals(Request $request)
    {
        $data = $request->validate(['daily_target' => ['required', 'integer', 'min:1', 'max:1000'], 'khatam_target_date' => ['nullable', 'date', 'after:today']]);
        $user = $request->user();
        $user->readingGoals()->where('status', 'active')->update(['status' => 'ended']);
        $goal = $user->readingGoals()->create($data + ['start_date' => today(), 'status' => 'active']);

        return $this->ok($goal->only(['daily_target', 'start_date', 'khatam_target_date', 'status']));
    }

    // ---------------------------------------------------------- preferensi

    public function appearance(PreferenceService $prefs)
    {
        return $this->ok($prefs->appearance(), ['allowed' => [
            'theme_mode' => array_column(ThemeMode::cases(), 'value'),
            'accent_color' => array_column(AccentColor::cases(), 'value'),
            'interface_text_size' => array_map(fn ($s) => $s->value, TextSize::interface()),
            'reading_text_size' => array_column(TextSize::cases(), 'value'),
        ]]);
    }

    public function updateAppearance(Request $request, PreferenceService $prefs)
    {
        $sizes = array_column(TextSize::cases(), 'value');
        $data = $request->validate([
            'theme_mode' => ['required', Rule::enum(ThemeMode::class)],
            'accent_color' => ['required', Rule::enum(AccentColor::class)],
            'interface_text_size' => ['required', Rule::in(array_map(fn ($s) => $s->value, TextSize::interface()))],
            'reading_text_size' => ['required', Rule::in($sizes)],
            'translation_text_size' => ['required', Rule::in($sizes)],
        ]);

        return $this->ok($prefs->saveAppearance($data));
    }

    public function quranPreferences(PreferenceService $prefs)
    {
        return $this->ok($prefs->quran(), ['mushaf_available' => PreferenceService::mushafAvailable()]);
    }

    public function updateQuranPreferences(Request $request, PreferenceService $prefs)
    {
        $data = $request->validate([
            'reading_mode' => ['sometimes', Rule::enum(QuranReadingMode::class)],
            'show_translation' => ['sometimes', 'boolean'],
            'show_transliteration' => ['sometimes', 'boolean'],
            'show_tafsir' => ['sometimes', 'boolean'],
            'audio_mode' => ['sometimes', Rule::in(['surah', 'verse'])],
            'reciter' => ['sometimes', 'nullable', 'string', 'regex:'.\App\Services\Quran\Audio\AudioCatalog::KEY_PATTERN],
        ]);

        return $this->ok($prefs->saveQuran($data));
    }

    // -------------------------------------------------------- data & akun

    public function export(Request $request, PersonalDataService $data)
    {
        return $this->ok($data->export($request->user()));
    }

    public function deleteAccount(Request $request, PersonalDataService $data)
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $user = $request->user();
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }
        $data->delete($user);

        return $this->ok(['deleted' => true]);
    }
}
