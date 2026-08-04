@props(['column', 'label', 'sort', 'direction'])

@php
    $isActive = $sort === $column;
    $next = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $arrow = $isActive ? ($direction === 'asc' ? '▲' : '▼') : '↕';
@endphp

<a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => 1]) }}"
   class="sort-link {{ $isActive ? 'is-active' : '' }}"
   @if ($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    {{ $label }} <span class="sort-link__arrow" aria-hidden="true">{{ $arrow }}</span>
</a>
