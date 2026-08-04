@props(['icon' => '📄', 'title' => 'Rien à afficher', 'description' => null])

<div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true">{{ $icon }}</span>
    <p class="fw-bold mb-1" style="color: var(--text)">{{ $title }}</p>
    @if ($description)
        <p class="mb-3">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
