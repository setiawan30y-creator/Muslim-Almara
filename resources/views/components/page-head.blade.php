@props(['title', 'subtitle' => null, 'back' => null])
<header class="page-head">
    @if ($back)
        <a href="{{ $back }}" class="icon-btn page-head__back" aria-label="Kembali"><x-icon name="arrow-left" /></a>
    @endif
    <div style="min-width:0;flex:1">
        <h1>{{ $title }}</h1>
        @if ($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    {{ $slot }}
</header>
