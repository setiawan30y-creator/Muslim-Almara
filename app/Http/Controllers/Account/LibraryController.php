<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\UserProgress\ReadingProgressService;
use Illuminate\Http\Request;

// Bookmark, catatan pribadi, dan progres tilawah. @2026-Alifiandra
class LibraryController extends Controller
{
    public function bookmarks(Request $request)
    {
        return view('pages.profile.bookmarks', [
            'items' => $request->user()->bookmarks()->with('verse.surah', 'verse.translationId')->latest()->paginate(20),
        ]);
    }

    public function notes(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $notes = $request->user()->notes()->with('verse.surah')
            ->when($q !== '', fn ($query) => $query->where('body', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'))
            ->latest('updated_at')->paginate(20)->withQueryString();

        return view('pages.profile.notes', ['notes' => $notes, 'q' => $q]);
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
