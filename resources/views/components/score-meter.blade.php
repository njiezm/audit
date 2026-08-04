@props(['score' => null, 'showLabel' => true, 'compact' => false])

@php
    $value = $score !== null ? (float) $score : null;
    $color = \App\Support\ScoreScale::color($value);
    $percent = $value !== null ? min(100, max(0, ($value / \App\Support\ScoreScale::MAX) * 100)) : 0;
@endphp

@if ($value === null)
    <span class="text-muted small">—</span>
@else
    <div {{ $attributes->merge(['class' => 'score-meter']) }}
         role="img"
         aria-label="Score {{ number_format($value, 1, ',', '') }} sur 5 — {{ \App\Support\ScoreScale::label($value) }}">
        <span class="score-meter__value" style="color: {{ $color }}">{{ number_format($value, 1, ',', '') }}</span>
        <span class="score-meter__track">
            <span class="score-meter__fill" style="width: {{ $percent }}%; background: {{ $color }}"></span>
        </span>
        @if ($showLabel && ! $compact)
            <span class="small text-muted d-none d-lg-inline">{{ \App\Support\ScoreScale::label($value) }}</span>
        @endif
    </div>
@endif
