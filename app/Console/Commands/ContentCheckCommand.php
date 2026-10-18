<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\ContentSource;
use App\Models\DhikrItem;
use App\Models\DuaItem;
use Illuminate\Console\Command;

// @2026-Alifiandra
class ContentCheckCommand extends Command
{
    protected $signature = 'almara:content:check-licenses';

    protected $description = 'Daftar sumber konten tanpa lisensi/review dan konten agama yang belum ditinjau.';

    public function handle(): int
    {
        $rows = ContentSource::query()->orderBy('key')->get()->map(fn ($s) => [
            $s->key, $s->license ? 'ada' : 'BELUM', $s->version ?? '-', $s->reviewed_at?->format('Y-m-d') ?? 'belum ditinjau',
        ]);
        $this->table(['Sumber', 'Lisensi', 'Versi', 'Review sumber'], $rows);

        foreach ([DuaItem::class => 'Doa', DhikrItem::class => 'Dzikir'] as $class => $label) {
            $pending = $class::query()->where('review_status', '!=', ContentStatus::Published->value)->count();
            $noReviewer = $class::query()->published()->whereNull('reviewed_by')->count();
            $this->line("{$label}: {$pending} menunggu/terbit belum, {$noReviewer} terbit tanpa reviewer tercatat.");
        }

        return self::SUCCESS;
    }
}
