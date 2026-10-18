@props(['icon' => 'search', 'title' => 'Tidak ada hasil', 'text' => null])
<div {{ $attributes->merge(['class' => 'empty']) }} role="status">
    <x-icon :name="$icon" />
    <p style="font-weight:700;margin:0;color:var(--color-text)">{{ $title }}</p>
    @if ($text)<p class="small" style="margin:4px 0 0">{{ $text }}</p>@endif
</div>
