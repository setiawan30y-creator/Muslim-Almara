{{-- Layout panel admin (blueprint §8.5) — @2026-Alifiandra --}}
@props(['title' => 'Admin'])
@php $u = auth()->user(); @endphp
<x-layouts.app :title="$title.' · Admin'" :noindex="true">
    <div class="admin">
        <nav class="admin-nav" aria-label="Menu admin">
            <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="layout" />Ringkasan</a>
            <a href="{{ route('admin.content.index', 'doa') }}" @if (request()->routeIs('admin.content.*') && request()->route('type') === 'doa') aria-current="page" @endif><x-icon name="hands" />Doa</a>
            <a href="{{ route('admin.content.index', 'dzikir') }}" @if (request()->routeIs('admin.content.*') && request()->route('type') === 'dzikir') aria-current="page" @endif><x-icon name="beads" />Dzikir</a>
            <a href="{{ route('admin.content.index', 'panduan') }}" @if (request()->routeIs('admin.content.*') && request()->route('type') === 'panduan') aria-current="page" @endif><x-icon name="learn" />Panduan</a>
            <a href="{{ route('admin.audio.index') }}" @if (request()->routeIs('admin.audio.*')) aria-current="page" @endif><x-icon name="headphones" />Audio qari</a>
            <a href="{{ route('admin.sources') }}" @if (request()->routeIs('admin.sources')) aria-current="page" @endif><x-icon name="database" />Sumber & lisensi</a>
            @if ($u->hasPermission('users.manage'))<a href="{{ route('admin.users') }}" @if (request()->routeIs('admin.users')) aria-current="page" @endif><x-icon name="users" />Pengguna</a>@endif
            @if ($u->hasPermission('settings.manage'))<a href="{{ route('admin.settings') }}" @if (request()->routeIs('admin.settings')) aria-current="page" @endif><x-icon name="sliders" />Konfigurasi</a>@endif
            @if ($u->hasPermission('audit.view'))<a href="{{ route('admin.audit') }}" @if (request()->routeIs('admin.audit')) aria-current="page" @endif><x-icon name="history" />Log audit</a>@endif
        </nav>
        <div style="min-width:0">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
