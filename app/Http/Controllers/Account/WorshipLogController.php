<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Worship\WorshipTracker;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// Tracker ibadah harian (web + API) — @2026-Alifiandra
class WorshipLogController extends Controller
{
    public function __construct(private WorshipTracker $tracker) {}

    private function tz(Request $request): string
    {
        $tz = $request->user()->profile?->timezone;

        return $tz && in_array($tz, timezone_identifiers_list(), true) ? $tz : config('app.timezone');
    }

    private function date(Request $request, ?string $value): CarbonImmutable
    {
        $tz = $this->tz($request);
        if ($value === null || $value === '') {
            return CarbonImmutable::now($tz)->startOfDay();
        }
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $value), 404);
        try {
            $d = CarbonImmutable::createFromFormat('!Y-m-d', $value, $tz);
        } catch (\Throwable) {
            abort(404);
        }
        abort_unless($d && $d->format('Y-m-d') === $value && $this->tracker->editable($d, $tz), 404);

        return $d;
    }

    public function index(Request $request)
    {
        $date = $this->date($request, $request->query('tanggal'));
        $tz = $this->tz($request);
        $today = CarbonImmutable::now($tz)->startOfDay();

        return view('pages.profile.ibadah', [
            'date' => $date,
            'today' => $today,
            'data' => $this->tracker->forDate($request->user(), $date),
            'summary' => $this->tracker->summary($request->user(), $tz),
            'week' => collect(range(6, 0))->map(fn ($i) => $today->subDays($i)),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate(['tanggal' => ['required', 'string'], 'prayers' => ['nullable', 'array'], 'habits' => ['nullable', 'array']]);
        $date = $this->date($request, $request->input('tanggal'));
        $log = $this->tracker->save($request->user(), $date, $request->only(['prayers', 'habits']));

        if ($request->expectsJson()) {
            return response()->json(['data' => ['date' => $date->toDateString(), 'score' => $log->score] + $log->data]);
        }

        return redirect()->route('profile.worship', $date->isSameDay(CarbonImmutable::now($this->tz($request))) ? [] : ['tanggal' => $date->toDateString()])
            ->with('status', 'Catatan ibadah '.$date->translatedFormat('j F').' tersimpan.');
    }

    // ---- API v1 ----

    public function apiIndex(Request $request)
    {
        $tz = $this->tz($request);
        $logs = $request->user()->worshipLogs()->orderByDesc('date')->limit(WorshipTracker::BACK_DAYS + 1)->get();
        $s = $this->tracker->summary($request->user(), $tz);

        return response()->json([
            'data' => $logs->map(fn ($l) => ['date' => $l->date->toDateString(), 'score' => $l->score] + $l->data)->values(),
            'meta' => ['streak' => $s['streak'], 'complete_days_30' => $s['complete_days'], 'prayers' => WorshipTracker::PRAYERS,
                'prayer_states' => WorshipTracker::PRAYER_STATES, 'habits' => array_map(fn ($h) => $h[0], WorshipTracker::HABITS)],
        ]);
    }

    public function apiUpdate(Request $request, string $date)
    {
        $request->validate(['prayers' => ['nullable', 'array'], 'habits' => ['nullable', 'array']]);
        $d = $this->date($request, $date);
        $log = $this->tracker->save($request->user(), $d, $request->only(['prayers', 'habits']));

        return response()->json(['data' => ['date' => $d->toDateString(), 'score' => $log->score] + $log->data]);
    }
}
