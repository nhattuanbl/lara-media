<div class="card card-flush mb-5 mb-xl-10">
    <div class="card-header pt-7">
        <h3 class="card-title align-items-start flex-column">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                <input type="text" data-kt-ecommerce-product-filter="search" class="form-control form-control-solid w-250px ps-12" placeholder="Search" />
            </div>
        </h3>
        <div class="card-toolbar">
            <div class="d-flex flex-stack flex-wrap gap-4">
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Album</div>
                    <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                    </select>
                </div>
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Extension</div>
                    <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                    </select>
                </div>
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Disk</div>
                    <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option" data-kt-table-widget-5="filter_status">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                    </select>
                </div>
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Model</div>
                    <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option" data-kt-table-widget-5="filter_status">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive">
        {{ $model }}
        <table id="myTable" class="table align-middle table-row-dashed fs-6 gy-3 dataTable dtr-inline collapsed">
            <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th></th>
                <th>Album / Path</th>
                <th class="pe-3 min-w-100px">Name / Ext</th>
                <th class="text-end pe-3">Disk / Size</th>
                <th class="text-end pe-3">Model / ID</th>
                <th class="text-end pe-0 min-w-75px">Created</th>
                <th class="text-end pe-0 min-w-75px">Updated</th>
                <th></th>
            </tr>
            </thead>
            <tbody class="fw-bold text-gray-600">
            <tr>
                <td class="d-flex align-items-center dtr-control">
                    <div class="symbol symbol-50px rounded cursor-pointer"
                         data-bs-toggle="popover" data-bs-placement="top" title="ID"
                         data-bs-content="66c49c3813bd99204a01482f" data-bs-custom-class="popover-inverse">
                        <img src="https://ui-avatars.com/api/?background=random" alt="preview"/>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">Avatar</div>
                        <div class="fs-7">2024/08/24</div>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">origin photo name <span class="badge badge-light-warning">mp4</span></div>
                        <div class="fs-7">4 other versions</div>
                    </div>
                </td>
                <td class="text-end">
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">Local</div>
                        <div class="fs-7">500 MB</div>
                    </div>
                </td>
                <td class="text-end">
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">User</div>
                        <div class="fs-7">2</div>
                    </div>
                </td>
                <td class="text-end">
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">{{ now()->format('M j, Y') }}</div>
                        <div class="fs-7">{{ now()->format('h:i A') }}</div>
                    </div>
                </td>
                <td class="text-end">
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">{{ now()->format('M j, Y') }}</div>
                        <div class="fs-7">{{ now()->format('h:i A') }}</div>
                    </div>
                </td>
                <td class="text-center">
                    <a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                        <i class="ki-outline ki-down fs-5 ms-1"></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                        <div class="menu-item px-3">
                            <a href="apps/ecommerce/catalog/edit-product.html" class="menu-link px-3">Edit</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-ecommerce-product-filter="delete_row">Delete</a>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

@assets
<link href="{{ asset('vendor/lara-media/css/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endassets

@script
<script>
    // var initDatatable = function () {
    var dt = new DataTable('#myTable', {
        searchDelay: 500,
        processing: true,
        serverSide: true,
        // order: [[5, 'desc']],
        stateSave: true,
        ajax: {
            url: '{{ route('lara-media.datatable') }}',
            type: 'GET',
        },
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                // render: function (data)
            },
        ],
    });

    table = dt.$;
    dt.on('draw', function () {
        initToggleToolbar();
        toggleToolbars();
        handleDeleteRows();
        KTMenu.createInstances();
    });

    var handleSearchDatatable = function () {
        const filterSearch = document.querySelector('[data-kt-docs-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            dt.search(e.target.value).draw();
        });
    }
</script>
@endscript
