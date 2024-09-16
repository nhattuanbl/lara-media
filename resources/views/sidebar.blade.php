<div class="app-sidebar flex-column">
    <div class="app-sidebar-logo d-none d-lg-flex flex-center pt-10 mb-3">
        <a href="/lara-media">
            <img class="h-30px" alt="Logo" src="{{ asset('vendor/lara-media/favicon.png') }}"/>
        </a>
    </div>
    <div class="app-sidebar-menu d-flex flex-center overflow-hidden flex-column-fluid">
        <div class="app-sidebar-wrapper d-flex hover-scroll-overlay-y scroll-ps mx-2 my-5">
            <div class="menu menu-column menu-rounded menu-active-bg menu-title-gray-700 menu-arrow-gray-500 menu-icon-gray-500 menu-bullet-gray-500 menu-state-primary my-auto">
                <div class="menu-item here py-2">
                    <span class="menu-link menu-center">
                        <span class="menu-icon me-0">
                            <i class="ki-duotone ki-laravel fs-2x">
                                <span class="path1"></span>
                                 <span class="path2"></span>
                                 <span class="path3"></span>
                                 <span class="path4"></span>
                                 <span class="path5"></span>
                                 <span class="path6"></span>
                                 <span class="path7"></span>
                            </i>
                        </span>
                    </span>
                </div>
{{--                <div class="menu-item py-2">--}}
{{--                    <span class="menu-link menu-center">--}}
{{--                        <span class="menu-icon me-0">--}}
{{--                            <i class="ki-outline ki-notification-status fs-2x"></i>--}}
{{--                        </span>--}}
{{--                    </span>--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
    <div class="app-sidebar-footer d-flex flex-center flex-column-auto pt-6 mb-7"
         title="Clear temporary files" data-bs-toggle="tooltip" wire:click="clear">
        <button type="button" class="btn btm-sm btn-custom btn-icon">
            <i class="ki-duotone ki-basket fs-3x">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
                <span class="path4"></span>
            </i>
        </button>
    </div>
</div>
@script
<script>
    $wire.on('media:cleared', (msg) => {
        Swal.fire({
            title: msg,
            icon: "success"
        });
    })
</script>
@endscript
