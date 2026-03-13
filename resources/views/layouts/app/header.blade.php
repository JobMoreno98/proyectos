<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/loader.css') }}">
    @stack('styles')

</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <div id="page-loader"
        style="position:fixed;inset:0;z-index:9999;background:#f8fafc;;display:none;flex-direction:column;align-items:center;justify-content:center;">

        <svg class="pl" viewBox="0 0 240 240">
            <circle class="pl__ring pl__ring--a" cx="120" cy="120" r="105" fill="none" stroke="#000"
                stroke-width="20" stroke-dasharray="0 660" stroke-dashoffset="-330" stroke-linecap="round"></circle>
            <circle class="pl__ring pl__ring--b" cx="120" cy="120" r="35" fill="none" stroke="#000"
                stroke-width="20" stroke-dasharray="0 220" stroke-dashoffset="-110" stroke-linecap="round"></circle>
            <circle class="pl__ring pl__ring--c" cx="85" cy="120" r="70" fill="none" stroke="#000"
                stroke-width="20" stroke-dasharray="0 440" stroke-linecap="round"></circle>
            <circle class="pl__ring pl__ring--d" cx="155" cy="120" r="70" fill="none" stroke="#000"
                stroke-width="20" stroke-dasharray="0 440" stroke-linecap="round"></circle>
        </svg>

        <div
            style="margin-top:24px;color:#255ff4;font-weight:600;font-family:sans-serif;font-size:18px;letter-spacing:1px;">
            Cargando...
        </div>

    </div>



    <flux:header class="print:hidden border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

        <x-app-logo href="{{ route('dashboard') }}" />

        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </flux:navbar.item>

            <x-navbar> </x-navbar>

        </flux:navbar>

        <flux:spacer />


        <x-desktop-user-menu />
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar collapsible="mobile" sticky
        class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" />
            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <x-nav-bar-mobile />
        </flux:sidebar.nav>

        <flux:spacer />

    </flux:sidebar>

    {{ $slot }}
    @fluxScripts
    <script src="{{ asset('js/validateForm.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.loaderInitialized) return;
            window.loaderInitialized = true;

            const showLoader = () => {
                const loader = document.getElementById('page-loader');
                if (loader) loader.style.display = 'flex';
            };

            const hideLoader = () => {
                const loader = document.getElementById('page-loader');
                if (loader) loader.style.display = 'none';
            };

            window.testLoader = showLoader;
            window.hideLoader = hideLoader;

            document.addEventListener('livewire:navigate', showLoader);
            document.addEventListener('livewire:navigated', hideLoader);

            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({
                    respond
                }) => {
                    showLoader();
                    respond(() => hideLoader());
                });
            });

            window.addEventListener('beforeunload', showLoader);
            window.addEventListener('pageshow', () => hideLoader());
            window.addEventListener('popstate', () => hideLoader());
        });
    </script>

    <script src="{{ asset('js/questionDependent.js') }}"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    @stack('js')

</body>

</html>
