<div class="app-aside flex-column z-index-1">
    <div class="app-navbar flex-stack flex-shrink-0 p-5 pt-lg-12 pb-lg-8 px-lg-14">
        <div class="d-flex align-items-center me-5 me-lg-10">
            <div class="app-navbar-item me-4">
                <div class="cursor-pointer symbol symbol-40px h-40px w-40px bg-warning d-flex justify-content-center align-items-center">
                    {{--                                        <img src="https://ui-avatars.com/api/?name=John+Doe" alt="user" />--}}
                    <i class="ki-duotone ki-user fs-1 text-white">
                        <i class="path1"></i>
                        <i class="path2"></i>
                    </i>
                </div>
            </div>
            <div class="d-flex flex-column">
                <a href="#" class="app-navbar-user-name text-gray-900 text-hover-primary fs-5 fw-bold">{{ $username }}</a>
                <span class="app-navbar-user-info text-gray-600 fw-semibold fs-7">Media management</span>
            </div>
        </div>
        <div class="app-navbar-item">
            <div class="btn btn-icon btn-custom btn-dark w-40px h-40px app-navbar-user-btn">
                <i class="ki-outline ki-notification-on fs-1"></i>
            </div>
        </div>
    </div>
    {{--                    Recent activities--}}
    <div class="app-aside_content-wrapper hover-scroll-overlay-y mx-3 ps-5 ps-lg-11 pe-3 pe-lg-11 pb-10">
        <div class="card card-p-0 card-reset bg-transparent mb-7 mb-lg-10">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Recent activities</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Total: 8k</span>
                </h3>
                <div class="card-toolbar">
                    <a href="#" class="btn btn-sm btn-light">Tag</a>
                </div>
            </div>
            <div class="card-body pt-6">
                {{--                                Item--}}
                <div class="d-flex flex-stack">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label fs-2 fw-semibold bg-danger">
                            <i class="bi bi-film fs-1 text-white"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                        <div class="flex-grow-1 me-2">
                            <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">some movie</a>
                            <span class="text-muted fw-semibold d-block fs-7">process</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px">
                            <i class="ki-outline ki-arrow-right fs-2"></i>
                        </a>
                    </div>
                </div>
                <div class="separator separator-dashed my-4"></div>

                <div class="d-flex flex-stack">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label fs-2 fw-semibold bg-success">
                            <i class="ki-outline ki-file fs-1 text-white"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                        <div class="flex-grow-1 me-2">
                            <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">some movie</a>
                            <span class="text-muted fw-semibold d-block fs-7">process</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px">
                            <i class="ki-outline ki-arrow-right fs-2"></i>
                        </a>
                    </div>
                </div>
                <div class="separator separator-dashed my-4"></div>

                <div class="d-flex flex-stack">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label fs-2 fw-semibold bg-info">
                            <i class="ki-outline ki-picture fs-1 text-white"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                        <div class="flex-grow-1 me-2">
                            <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">some movie</a>
                            <span class="text-muted fw-semibold d-block fs-7">process</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px">
                            <i class="ki-outline ki-arrow-right fs-2"></i>
                        </a>
                    </div>
                </div>
                <div class="separator separator-dashed my-4"></div>

                <div class="d-flex flex-stack">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label fs-2 fw-semibold bg-primary">
                            <i class="ki-outline ki-microsoft fs-1 text-white"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                        <div class="flex-grow-1 me-2">
                            <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">some movie</a>
                            <span class="text-muted fw-semibold d-block fs-7">process</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px">
                            <i class="ki-outline ki-arrow-right fs-2"></i>
                        </a>
                    </div>
                </div>
                <div class="separator separator-dashed my-4"></div>
            </div>
        </div>
    </div>
</div>
