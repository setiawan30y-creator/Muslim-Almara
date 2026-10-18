{{-- Kalender Hijriah — @2026-Alifiandra --}}
@php
    $prev = $month->subMonth()->format('Y-m');
    $next = $month->addMonth()->format('Y-m');
@endphp
<x-layouts.app title="Kalender Hijriah" description="Kalender Masehi dan Hijriah {{ $month->translatedFormat('F Y') }} beserta hari besar Islam.">
    <x-page-head title="Kalender Hijriah" subtitle="{{ $month->translatedFormat('F Y') }} · {{ $hijriRange }}" />

    <div class="layout-side">
        <section class="card" aria-labelledby="kal-h">
            <div class="section-head">
                <h2 id="kal-h">{{ $month->translatedFormat('F Y') }}</h2>
                <div style="display:flex;gap:6px">
                    <a class="icon-btn" href="{{ route('calendar', ['bulan' => $prev]) }}" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" /></a>
                    <a class="chip" href="{{ route('calendar') }}">Hari ini</a>
                    <a class="icon-btn" href="{{ route('calendar', ['bulan' => $next]) }}" aria-label="Bulan berikutnya"><x-icon name="chevron-right" /></a>
                </div>
            </div>
            <div class="cal" role="grid" aria-label="Kalender {{ $month->translatedFormat('F Y') }}">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dow)
                    <div class="cal__dow" role="columnheader">{{ $dow }}</div>
                @endforeach
                @for ($i = 0; $i < $offset; $i++)
                    <div class="cal__day cal__blank" aria-hidden="true"></div>
                @endfor
                @foreach ($days as $d)
                    <div role="gridcell" class="cal__day {{ $d['date']->isSameDay($today) ? 'is-today' : '' }} {{ $d['date']->isFriday() ? 'is-friday' : '' }} {{ $d['event'] ? 'has-event' : '' }}"
                         aria-label="{{ $d['date']->translatedFormat('l, j F Y') }}, {{ $d['hijri']['label'] }}{{ $d['event'] ? ', '.$d['event'] : '' }}">
                        <span class="g">{{ $d['date']->day }}</span>
                        <span class="h">{{ $d['hijri']['day'] }}{{ $d['hijri']['day'] === 1 || $loop->first ? ' '.$d['hijri']['month_short'] : '' }}</span>
                        @if ($d['event'])<span class="ev">{{ $d['event'] }}</span>@endif
                    </div>
                @endforeach
            </div>
            <p class="small muted" style="margin:12px 0 0">Tanggal Hijriah dihitung secara aritmetika (urfi) dan dapat berbeda ±1 hari dengan penetapan resmi melalui rukyat/sidang isbat.</p>
        </section>

        <aside class="card" aria-labelledby="hb-h">
            <h2 id="hb-h">Hari besar mendatang</h2>
            <ul class="events">
                @foreach ($events as $e)
                    <li>
                        <span class="date"><b>{{ $e['tanggal']->format('j') }}</b><small>{{ $e['tanggal']->translatedFormat('M y') }}</small></span>
                        <span>
                            <span class="title">{{ $e['nama'] }}</span>
                            <span class="sub" style="display:block">{{ $e['hijri'] }} · {{ $e['tanggal']->translatedFormat('l') }} · {{ $e['sisa_hari'] === 0 ? 'hari ini' : $e['sisa_hari'].' hari lagi' }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-layouts.app>
