<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="{{ asset('vendor/lara-media/favicon.ico') }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title ?? 'Lara Media' }}</title>
        @livewireStyles

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

        <link href="{{ asset('vendor/lara-media/css/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('vendor/lara-media/plugins/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('vendor/lara-media/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

        <style>
            body, .popover {
                font-family: 'Noto Sans', sans-serif;
            }
            select, option {
                font: -moz-pull-down-menu;
            }
            #myTable tr[data-dt-row] > td {
                padding-top: 0;
                padding-bottom: 0;
            }
            .select2-selection {
                border: none !important;
            }
        </style>
    </head>
    <body class="app-default" data-kt-app-sidebar-fixed="true" data-kt-app-aside-fixed="true">
    <div class="d-flex flex-column flex-root app-root h-100">
        <div class="app-page flex-column flex-column-fluid">
            <div class="app-wrapper flex-column flex-row-fluid">
                <livewire:lara-media::side-bar />
                <div class="app-main flex-column flex-row-fluid">
                    <div class="d-flex flex-column flex-column-fluid">
                        <div class="app-content flex-column-fluid pt-16">
                            <div class="app-container container-fluid">
                                <div class="row gx-5 gx-xl-10">
                                    <livewire:lara-media::album-report />
                                    <livewire:lara-media::storage-report lazy="on-load"/>
                                    <livewire:lara-media::media-table />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="app-footer">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3 justify-content-end">
                            <a class="btn btn-link btn-color-gray-500 btn-active-color-primary me-5 mb-2" href="https://github.com" target="_blank">Github</a>
                        </div>
                    </div>
                </div>
                <livewire:lara-media::a-side />
            </div>
        </div>
    </div>

    <script>var hostUrl = "vendor/lara-media/";</script>
    <script src="{{ asset('vendor/lara-media/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/lara-media/js/datatables.bundle.js') }}"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/fonts/notosans-sc.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
    <script src="https://unpkg.com/wavesurfer.js@7"></script>

    <script>
        const themeColors = {
            danger: 'F8285A',
            success: '17C653',
            primary: '1B84FF',
            info: '7239EA',
            warning: 'F6C000',
            dark: '1E2129',
            secondary: 'F1F1F4',
            light: 'F9F9F9',

            primaryActive: '056EE9',
            secondaryActive: 'C4CADA',
            successActive: '04B440',
            infoActive: '5014D0',
            warningActive: 'DEAD00',
            dangerActive: 'D81A48',
            darkActive: '111318',
            lightActive: 'F1F1F4',

            primaryLight: 'E9F3FF',
            secondaryLight: 'F9F9F9',
            successLight: 'DFFFEA',
            infoLight: 'F8F5FF',
            warningLight: 'FFF8DD',
            dangerLight: 'FFEEF3',
            darkLight: 'F9F9F9',
            lightLight: 'ffffff',

            primaryInverse: 'ffffff',
            secondaryInverse: '252F4A',
            lightInverse: '252F4A',
            successInverse: 'ffffff',
            infoInverse: 'ffffff',
            warningInverse: 'ffffff',
            dangerInverse: 'ffffff',
            darkInverse: 'ffffff',

            gray100: 'F9F9F9',
            gray200: 'F1F1F4',
            gray300: 'DBDFE9',
            gray400: 'C4CADA',
            gray500: '99A1B7',
            gray600: '78829D',
            gray700: '4B5675',
            gray800: '252F4A',
            gray900: '071437',
        };
    </script>
    <script src="{{ asset('vendor/lara-media/plugins/plugins.bundle.js') }}"></script>
    <script src="{{ asset('vendor/lara-media/js/scripts.bundle.js') }}"></script>
    @livewireScripts
    </body>
</html>

