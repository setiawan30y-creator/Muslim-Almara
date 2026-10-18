<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SurahResource;
use App\Http\Resources\VerseResource;
use App\Models\ContentSource;
use App\Models\QuranVerse;
use App\Services\Quran\QuranService;
use Illuminate\Http\Request;

// @2026-Alifiandra
class QuranController extends ApiController
{
    public function __construct(private QuranService $quran)
    {
    }

    public function surahs()
    {
        return $this->ok(SurahResource::collection($this->quran->surahs()), ['count' => $this->quran->surahs()->count()]);
    }

    public function surah(int $number)
    {
        $s = $this->quran->surah($number);
        abort_if(! $s, 404, 'Surah tidak ditemukan.');

        return $this->ok(new SurahResource($s), ['source' => $this->source('quran-text-kemenag')]);
    }

    public function verses(Request $request)
    {
        $data = $request->validate([
            'surah' => ['required', 'integer', 'between:1,114'],
            'from' => ['nullable', 'integer', 'min:1'],
            'to' => ['nullable', 'integer', 'min:1', 'gte:from'],
            'tafsir' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,300'],
        ]);
        $p = QuranVerse::query()->with(array_merge(['surah', 'translationId'], $request->boolean('tafsir') ? ['tafsirId'] : []))
            ->whereHas('surah', fn ($q) => $q->where('number', $data['surah']))
            ->when($data['from'] ?? null, fn ($q, $v) => $q->where('ayah_number', '>=', $v))
            ->when($data['to'] ?? null, fn ($q, $v) => $q->where('ayah_number', '<=', $v))
            ->orderBy('ayah_number')->paginate($this->perPage(50, 300));

        return $this->paginated($p, VerseResource::class, ['sources' => [$this->source('quran-text-kemenag'), $this->source('quran-translation-kemenag-id')]]);
    }

    public function verse(int $id)
    {
        $v = QuranVerse::query()->with(['surah', 'translationId', 'tafsirId'])->findOrFail($id);

        return $this->ok(new VerseResource($v));
    }

    public function search(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $p = $this->quran->search((string) $request->query('q'), $this->perPage(20, 50));
        $p->setCollection($p->getCollection()->map(fn ($t) => $t->verse->setRelation('translationId', $t)));

        return $this->paginated($p, VerseResource::class, ['query' => $request->query('q'), 'scope' => 'terjemahan Indonesia']);
    }

    public function translations()
    {
        return $this->ok([[
            'language' => 'id', 'translator' => 'Kementerian Agama RI', 'source' => $this->source('quran-translation-kemenag-id'),
        ]]);
    }

    private function source(string $key): ?array
    {
        return ContentSource::query()->where('key', $key)->first(['id', 'key', 'name', 'license', 'attribution', 'version'])?->toArray();
    }
}
