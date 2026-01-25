@props(['title', 'icon'])

<li class="nxl-item nxl-hasmenu">
    <a href="javascript:void(0);" class="nxl-link">
        <span class="nxl-micon"><i class="{{ $icon }}"></i></span>
        <span class="nxl-mtext">{{ $title }}</span><span class="nxl-arrow"><i
                class="feather-chevron-right"></i></span>
    </a>
    <ul class="nxl-submenu">
        {{ $slot }}
    </ul>
</li>
