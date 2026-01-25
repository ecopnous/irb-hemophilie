<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
        <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css">
        @stack("styles-vendor")
        <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">

            @include("components.layouts.nav")
            @include("components.layouts.header")

            <!-- Page Content -->
            <main class="nxl-container">
                {{-- {{ $slot }} --}}

                @yield("main")
            </main>
        </div>


        <script src="assets/vendors/js/vendors.min.js"></script>
        @stack('scripts-vendors')
        <script src="assets/js/common-init.min.js"></script>
        @stack('scripts-page')
        <script src="assets/js/theme-customizer-init.min.js"></script>
    </body>
</html>
