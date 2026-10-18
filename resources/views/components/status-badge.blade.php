@props(['status'])
<span class="badge badge--{{ $status->tone() }}">{{ $status->label() }}</span>
