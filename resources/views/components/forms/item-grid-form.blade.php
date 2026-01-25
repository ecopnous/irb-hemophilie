@props(["title"])

<div class="row mb-4 align-items-center">
    <div class="col-lg-4">
        <label class="fw-semibold">{{ $title }}: </label>
    </div>
    <div class="col-lg-8">
        {{ $slot }}
    </div>
</div>