@props(['place' => 'list'])
{{-- Slot iklan (dimuat lambat setelah pengunjung memilih persetujuan) — @2026-Alifiandra --}}
@php $ads = app(\App\Services\Ads\AdManager::class); @endphp
@if ($ads->shows($place))
    <aside class="ad-slot" aria-label="Iklan" data-ad-slot-wrap>
        <span class="ad-slot__label">Iklan</span>
        <ins class="adsbygoogle" style="display:block" data-ad-client="{{ $ads->client() }}" data-ad-slot="{{ $ads->slot($place) }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
    </aside>
@endif
