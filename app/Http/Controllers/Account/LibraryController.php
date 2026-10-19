<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\UserProgress\ReadingProgressService;
use Illuminate\Http\Request;

// Bookmark, catatan pribadi, dan progres tilawah. @2026-Alifiandra
class LibraryController extends Controller
{
    /** Bookmark & koleksi tematik. */
    public function bookmarks(Request $request)
    {
        $user = $request->user();
        $collections = $user->collections()->withCount('bookmarks')->orderBy('name')->get();
        $filter = $request->query('koleksi');
        $active = $filter === 'tanpa' ? 'tanpa' : $collections->firstWhere('id', (int) $filter);

        return view('pages.profile.bookmarks', [
            'items' => $user->bookmarks()->with('verse.surah', 'verse.translationId', 'collection:id,name')
                ->when($active === 'tanpa', fn ($q) => $q->whereNull('collection_id'))
                ->when($active instanceof \App\Models\UserCollection, fn ($q) => $q->where('collection_id', $active->id))
                ->latest()->paginate(20)->withQueryString(),
            'collections' => $collections,
            'active' => $active,
            'total' => $user->bookmarks()->count(),
        ]);
    }

    public function storeCollection(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:255']], [], ['name' => 'nama koleksi']);
        abort_if($request->user()->collections()->count() >= 50, 422, 'Maksimal 50 koleksi.');
        $c = $request->user()->collections()->create($data);

        return redirect()->route('profile.bookmarks', ['koleksi' => $c->id])->with('status', "Koleksi \"{$c->name}\" dibuat.");
    }

    public function destroyCollection(Request $request, \App\Models\UserCollection $collection)
    {
        abort_unless($collection->user_id === $request->user()->id, 404);
        $collection->delete(); // bookmark tetap ada, hanya dilepas dari koleksi

        return redirect()->route('profile.bookmarks')->with('status', 'Koleksi dihapus. Bookmark di dalamnya tetap tersimpan.');
    }

    public function moveBookmark(Request $request, \App\Models\UserBookmark $bookmark)
    {
        abort_unless($bookmark->user_id === $request->user()->id, 404);
        $data = $request->validate(['collection_id' => ['nullable', 'integer']]);
        $cid = $data['collection_id'] ?? null;
        abort_if($cid && ! $request->user()->collections()->whereKey($cid)->exists(), 404);
        $bookmark->update(['collection_id' => $cid]);

        return back()->with('status', 'Bookmark dipindahkan.');
    }

    /** Jurnal: catatan per ayat & catatan umum, dengan tag tema. */
    public function notes(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $tag = mb_strtolower(trim((string) $request->query('tag')));
        $user = $request->user();
        $notes = $user->notes()->with('verse.surah')
            ->when($q !== '', fn ($query) => $query->where('body', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'))
            ->when($tag !== '', fn ($query) => $query->where(fn ($w) => $w->where('tags', $tag)->orWhere('tags', 'like', $tag.',%')->orWhere('tags', 'like', '%,'.$tag)->orWhere('tags', 'like', '%,'.$tag.',%')))
            ->latest('updated_at')->paginate(20)->withQueryString();
        $tags = $user->notes()->whereNotNull('tags')->pluck('tags')->flatMap(fn ($t) => explode(',', $t))->countBy()->sortDesc()->take(30);

        return view('pages.profile.notes', ['notes' => $notes, 'q' => $q, 'tag' => $tag, 'tags' => $tags]);
    }

    public function storeNote(Request $request)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'tags' => ['nullable', 'string', 'max:200']], [], ['body' => 'isi catatan']);
        $request->user()->notes()->create(['body' => $data['body'], 'tags' => \App\Models\UserNote::normalizeTags($data['tags'] ?? null)]);

        return redirect()->route('profile.notes')->with('status', 'Catatan jurnal disimpan.');
    }

    public function updateNoteTags(Request $request, \App\Models\UserNote $note)
    {
        abort_unless($note->user_id === $request->user()->id, 404);
        $data = $request->validate(['tags' => ['nullable', 'string', 'max:200']]);
        $note->update(['tags' => \App\Models\UserNote::normalizeTags($data['tags'] ?? null)]);

        return back()->with('status', 'Tag diperbarui.');
    }

    public function progress(Request $request, ReadingProgressService $progress)
    {
        $user = $request->user();

        return view('pages.profile.progress', [
            'stats' => $progress->stats($user),
            'goal' => $user->activeReadingGoal,
            'lastRead' => $user->lastRead()->with('verse.surah')->first(),
            'tracking' => $user->profile?->track_reading ?? true,
        ]);
    }

    public function saveGoal(Request $request)
    {
        $data = $request->validate([
            'daily_target' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'khatam_target_date' => ['nullable', 'date', 'after:today'],
            'track_reading' => ['nullable', 'boolean'],
        ], [], ['daily_target' => 'target harian', 'khatam_target_date' => 'target khatam']);

        $user = $request->user();
        $user->profile()->updateOrCreate([], ['track_reading' => $request->boolean('track_reading')]);
        $user->readingGoals()->where('status', 'active')->update(['status' => 'ended']);
        if (! empty($data['daily_target'])) {
            $user->readingGoals()->create([
                'daily_target' => $data['daily_target'],
                'start_date' => today(),
                'khatam_target_date' => $data['khatam_target_date'] ?? null,
                'status' => 'active',
            ]);
        }

        return back()->with('status', 'Target tilawah disimpan.');
    }
}
