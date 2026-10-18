<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContentSource;
use App\Models\DhikrItem;
use App\Models\DuaItem;
use App\Models\User;

// @2026-Alifiandra
class DashboardController extends Controller
{
    public function __invoke()
    {
        $count = fn ($class) => $class::query()->selectRaw('review_status, count(*) c')->groupBy('review_status')->pluck('c', 'review_status');

        return view('admin.dashboard', [
            'doa' => $count(DuaItem::class),
            'dzikir' => $count(DhikrItem::class),
            'users' => User::query()->count(),
            'sourcesUnreviewed' => ContentSource::query()->whereNull('reviewed_at')->count(),
            'recent' => AuditLog::query()->with('actor:id,name')->latest('created_at')->limit(8)->get(),
            'statuses' => ContentStatus::cases(),
        ]);
    }
}
