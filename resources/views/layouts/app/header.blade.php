<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <div id="page-loader"
        style="position:fixed;inset:0;z-index:9999;background:white;display:none;flex-direction:column;align-items:center;justify-content:center;">

        <div
            style="width:50px;height:50px;border:6px solid #c7d2fe;border-top:6px solid #4f46e5;border-radius:50%;animation:spin 1s linear infinite;">
        </div>

        <div style="margin-top:16px;color:#4f46e5;font-weight:600;font-family:sans-serif;font-size:16px;">
            Cargando...
        </div>

    </div>
    <style>
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <flux:header class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
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
    <script>
        function initPageLoader() {

            const loader = document.getElementById('page-loader');

            if (!loader) return;

            function show() {
                loader.style.display = 'flex';
            }

            function hide() {
                loader.style.display = 'none';
            }

            window.testLoader = show;
            window.hideLoader = hide;

            document.addEventListener('livewire:navigate', show);
            document.addEventListener('livewire:navigated', hide);
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({
                    respond
                }) => {
                    show();
                    respond(() => hide());
                });
            });
            window.addEventListener('beforeunload', show);
        }
        document.addEventListener('livewire:navigated', initPageLoader);
        document.addEventListener('DOMContentLoaded', initPageLoader);
    </script>
    <script src="{{ asset('js/validateForm.js') }}"></script>
    @fluxScripts
    <script src="{{ asset('js/questionDependent.js') }}"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    @stack('js')

</body>

</html>
