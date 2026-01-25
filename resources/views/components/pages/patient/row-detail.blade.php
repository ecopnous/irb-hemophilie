@props(['title', 'value'])

<div class="row g-0 mb-4">
    <div class="col-sm-6 text-muted">{{ $title }}:</div>
    <div class="col-sm-6 fw-semibold">{{ $value ? $title : 'N/A' }}</div>
</div>
