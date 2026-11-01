{{-- Banner persetujuan iklan + konfigurasi pemuatan skrip — @2026-Alifiandra --}}
@php $ads = app(\App\Services\Ads\AdManager::class); @endphp
@if ($ads->enabled())
    <div class="consent" data-ads-consent role="dialog" aria-labelledby="consent-title" @if ($ads->consent()) hidden @endif>
        <div class="consent__body">
            <b id="consent-title">Iklan &amp; privasi</b>
            <p class="small">Aplikasi ini gratis dan dibiayai iklan. Iklan tidak ditampilkan di dalam bacaan Al-Qur'an, doa, dan dzikir. Izinkan iklan yang dipersonalisasi (memakai cookie mitra iklan) atau pilih iklan umum saja. <a href="{{ route('privacy') }}#iklan">Selengkapnya</a></p>
        </div>
        <div class="consent__actions">
            <button type="button" class="btn btn--outline btn--sm" data-consent-choice="npa">Iklan umum saja</button>
            <button type="button" class="btn btn--sm" data-consent-choice="personalized">Izinkan</button>
        </div>
    </div>
    <script>window.ALMARA_ADS = @json(['client' => $ads->client(), 'consent' => $ads->consent(), 'cookie' => \App\Services\Ads\AdManager::CONSENT_COOKIE]);</script>
@endif
