@props(['status'])

<span class="{{ $status->badgeClass() }}">{{ $status->label() }}</span>
