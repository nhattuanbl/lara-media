<div class="card card-flush mb-5 mb-xl-10">
    <div class="card-header pt-7">
        <h3 class="card-title align-items-start flex-column">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                <input type="text"
                       data-kt-ecommerce-product-filter="search"
                       class="form-control form-control-solid w-250px ps-12" placeholder="Search"
                       name="search" id="media-search"
                />
            </div>
        </h3>
        <div class="card-toolbar">
            <div class="d-flex flex-stack flex-wrap gap-4">
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Album</div>
                    <select id="filterAlbum" class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto"
                            data-control="select2" data-dropdown-css-class="w-150px" data-hide-search="true" data-placeholder="Select an option" >
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                        @foreach($albumGroups as $i)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Extension</div>
                    <select id="filterExt" class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto"
                            data-control="select2" data- data-hide-search="true" data-placeholder="Select an option">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                        @foreach($extGroups as $i)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Disk</div>
                    <select id="filterDisk" class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto"
                            data-control="select2" data-hide-search="true" data-placeholder="Select an option">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                        @foreach($diskGroups as $i)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center fw-bold">
                    <div class="text-muted fs-7 me-2">Model</div>
                    <select id="filterModel" class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto"
                            data-control="select2" data-hide-search="true" data-placeholder="Select an option" data-kt-table-widget-5="filter_status">
                        <option></option>
                        <option value="Show All" selected="selected">Show All</option>
                        @foreach($modelGroups as $i)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="myTable" class="table align-middle table-row-dashed fs-6 gy-3 dataTable dtr-inline collapsed">
            <thead>
            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                <th></th>
                <th>Album</th>
                <th class="pe-3 min-w-100px">Name</th>
                <th class="text-end pe-3">Disk</th>
                <th class="text-end pe-3">Model</th>
                <th class="text-end pe-0 min-w-75px">Created</th>
                <th class="text-end pe-0 min-w-75px">Updated</th>
                <th></th>
            </tr>
            </thead>
            <tbody class="fw-bold text-gray-600">
{{--            <tr>--}}
{{--                <td class="d-flex align-items-center dtr-control">--}}
{{--                    <div class="symbol symbol-50px rounded cursor-pointer"--}}
{{--                         data-bs-toggle="popover" data-bs-placement="top" title="ID"--}}
{{--                         data-bs-content="66c49c3813bd99204a01482f" data-bs-custom-class="popover-inverse">--}}
{{--                        <img src="https://ui-avatars.com/api/?background=random" alt="preview"/>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <div class="d-flex flex-column text-muted">--}}
{{--                        <div class="text-gray-800 fw-bold">Avatar</div>--}}
{{--                        <div class="fs-7">2024/08/24</div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <div class="d-flex flex-column text-muted">--}}
{{--                        <div class="text-gray-800 fw-bold">origin photo name <span class="badge badge-light-warning">mp4</span></div>--}}
{{--                        <div class="fs-7">4 other versions</div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td class="text-end">--}}
{{--                    <div class="d-flex flex-column text-muted">--}}
{{--                        <div class="text-gray-800 fw-bold">Local</div>--}}
{{--                        <div class="fs-7">500 MB</div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td class="text-end">--}}
{{--                    <div class="d-flex flex-column text-muted">--}}
{{--                        <div class="text-gray-800 fw-bold">User</div>--}}
{{--                        <div class="fs-7">2</div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td class="text-end">--}}
{{--                    <div class="d-flex flex-column text-muted">--}}
{{--                        <div class="text-gray-800 fw-bold">{{ now()->format('M j, Y') }}</div>--}}
{{--                        <div class="fs-7">{{ now()->format('h:i A') }}</div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td class="text-end">--}}
{{--                    <div class="d-flex flex-column text-muted">--}}
{{--                        <div class="text-gray-800 fw-bold">{{ now()->format('M j, Y') }}</div>--}}
{{--                        <div class="fs-7">{{ now()->format('h:i A') }}</div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--                <td class="text-center">--}}
{{--                    <a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions--}}
{{--                        <i class="ki-outline ki-down fs-5 ms-1"></i>--}}
{{--                    </a>--}}
{{--                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">--}}
{{--                        <div class="menu-item px-3">--}}
{{--                            <a href="apps/ecommerce/catalog/edit-product.html" class="menu-link px-3">Edit</a>--}}
{{--                        </div>--}}
{{--                        <div class="menu-item px-3">--}}
{{--                            <a href="#" class="menu-link px-3" data-kt-ecommerce-product-filter="delete_row">Delete</a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--            </tr>--}}
            </tbody>
        </table>
    </div>
</div>

@script
<script>
    window.getReadableFileSizeString = function(fileSizeInBytes) {
        var i = -1;
        var byteUnits = [' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
        do {
            fileSizeInBytes /= 1024;
            i++;
        } while (fileSizeInBytes > 1024);

        return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
    }

    window.mimeTypeIsVideo = function(mimeType) {
        return mimeType.startsWith('video/');
    }

    window.mimeTypeIsAudio = function(mimeType) {
        return mimeType.startsWith('audio/');
    }

    window.mimeTypeIsImage = function(mimeType) {
        return mimeType.startsWith('image/');
    }

    window.ucFirst = function(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    window.readableSeconds = function(seconds) {
        var h = Math.floor(seconds / 3600);
        var m = Math.floor(seconds % 3600 / 60);
        var s = Math.floor(seconds % 3600 % 60);
        var result = (h > 0 ? h + 'h ' : '') + (m > 0 ? m + 'm ' : '') + (s > 0 ? s + 's' : '');
        return result.trim() || seconds + 's';
    }

    let myTable = new DataTable('#myTable', {
        searchDelay: 500,
        processing: true,
        serverSide: true,
        stateSave: false,
        lengthMenu: [[15, 30, 50, 100, 500, 1000, -1], [15, 30, 50, 100, 500, 1000, 'All']],
        ajax: function(data, callback, settings) {
            let offset = data.start;
            let limit = data.length;
            let search = data.search.value;

            let sortColumnIndex = data.order && data.order.length > 0 ? data.order[0].column : null;
            let sortDirection = data.order && data.order.length > 0 ? data.order[0].dir : 'desc';
            let sortColumnName = sortColumnIndex !== null ? data.columns[sortColumnIndex].name : 'created_at';

            let filterAlbum = $('#filterAlbum').val();
            let filterExt = $('#filterExt').val();
            let filterDisk = $('#filterDisk').val();
            let filterModel = $('#filterModel').val();

            $.get('{{ route('lara-media.datatable') }}', {
                limit: limit,
                offset: offset,
                dept_name__icontains: search,
                search: search,
                sort_direction: sortDirection,
                sort_column: sortColumnName,
                filterAlbum: filterAlbum,
                filterExt: filterExt,
                filterDisk: filterDisk,
                filterModel: filterModel,
            }, function(res) {
                callback({
                    draw: data.draw,
                    recordsTotal: res.total,
                    recordsFiltered: res.total,
                    data: res.data
                });
            });
        },
        order: [[5, 'desc']],
        columns: [
            {
                targets: 0,
                orderable: false,
                data: null,
                render: function(data, type, row) {
                    $('#myTable thead tr td:first-child').removeClass('dtr-control');
                    return `
                    <div class="symbol symbol-50px rounded cursor-pointer
                    ${mimeTypeIsVideo(row.mime_type) ? ' playVideo' : ((mimeTypeIsAudio(row.mime_type) ? ' playAudio' : ''))}"
                    data-url="${row.url}">
                        ${row.preview !== null ?
                            '<img src="'+row.preview+'" alt="preview" />' :
                            (mimeTypeIsVideo(row.mime_type) ?
                                '<div class="d-flex w-50px h-50px align-items-center justify-content-center overlay-wrapper text-gray-600"><i class="ki-duotone ki-youtube fs-4x"><span class="path1"></span><span class="path2"></span></i></div>'
                                : (mimeTypeIsAudio(row.mime_type) ?
                                        '<div class="d-flex w-50px h-50px align-items-center justify-content-center overlay-wrapper text-gray-600">' +
                                            '<i class="bi bi-activity fs-2x"></i>' +
                                            '<span class="path1"></span><span class="path2"></span></i>' +
                                        '</div>' :
                                            '<img src="'+row.url+'" alt="url" />'
                                    )
                            )
                        }
                    </div>`;
                },
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('d-flex align-items-center dtr-control');
                }
            },
            {
                targets: 1,
                orderable: true,
                name: 'album',
                data: 'album',
                render: function(data, type, row) {
                    return `<div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${ucFirst(row.album)}</div>
                        <div class="fs-7">${row.path}</div>
                    </div>`;
                },
            },
            {
                targets: 2,
                orderable: true,
                name: 'name',
                data: 'name',
                render: function(data, type, row) {
                    return `<div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${row.properties.name } <span class="badge badge-light-warning">${row.name}.${row.ext}</span></div>
                        <div class="fs-7">${row.total_files - 1} other versions</div>
                    </div>`;
                },
            },
            {
                targets: 3,
                orderable: true,
                className: 'text-end',
                name: 'size',
                data: 'size',
                render: function(data, type, row) {
                    return `<td class="text-end">
                    <div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${ucFirst(row.disk)}</div>
                        <div class="fs-7">${getReadableFileSizeString(row.size)}</div>
                    </div>
                </td>`;
                },
            },
            {
                targets: 4,
                orderable: true,
                className: 'text-end',
                name: 'model_id',
                data: 'model_id',
                render: function(data, type, row) {
                    return `<div class="d-flex flex-column text-muted" data-bs-toggle="tooltip" title="${row.model_type}">
                        <div class="text-gray-800 fw-bold" >${row.model_type.split('\\').pop()}</div>
                        <div class="fs-7">${row.model_id}</div>
                    </div>`;
                },
            },
            {
                targets: 5,
                orderable: true,
                className: 'text-end',
                name: 'created_at',
                data: 'created_at',
                render: function(data, type, row) {
                    return `<div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${moment(row.created_at).format('MMM D, YYYY')}</div>
                        <div class="fs-7">${moment(row.created_at).format('h:mm A')}</div>
                    </div>`;
                },
            },
            {
                targets: 6,
                orderable: true,
                className: 'text-end',
                name: 'updated_at',
                data: 'updated_at',
                render: function(data, type, row) {
                    return `<div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${moment(row.updated_at).format('MMM D, YYYY')}</div>
                        <div class="fs-7">${moment(row.updated_at).format('h:mm A')}</div>
                    </div>`;
                },
            },
            {
                targets: 7,
                orderable: false,
                className: 'text-center',
                data: null,
                render: function(data, type, row) {
                    return `<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                        <i class="ki-outline ki-down fs-5 ms-1"></i>
                    </a>
                    <div class="w-auto menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 py-4" data-kt-menu="true">
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-to-copy="${row._id}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-message-programming fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="menu-title">Copy ID</span>
                            </a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-to-copy="${row.url}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-click fs-3">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Copy Link</span>
                            </a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-to-copy="${row.hash}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-fasten fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="menu-title">Copy Hash</span>
                            </a>
                        </div>
                        <div class="menu-item px-3">
                            <button type="button" class="btnDeleteAll btn menu-link px-3 w-100 btn-light-danger btn-active-light-danger" data-id="${row._id}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-delete-folder fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="menu-title text-danger">Delete All</span>
                            </button>
                        </div>
                        <div class="menu-item px-3">
                            <button type="button" class="btnDeleteOrigin btn menu-link px-3 w-100
                                ${(row.is_removed === true) ? 'disabled' : 'btn-light-danger btn-active-light-danger'}"
                                data-id="${row._id}"
                            >
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="menu-title ${(row.is_removed === true) ? 'text-dark' : 'text-danger'}">Delete Original</span>
                            </button>
                        </div>
                    </div>`;
                },
            },
        ],
    });

    myTable.on('draw', function () {
        $('#myTable [data-bs-toggle="popover"]').popover();
        $('[data-bs-toggle="tooltip"]').tooltip();
        KTMenu.createInstances('#myTable [data-kt-menu="true"]');

        let clipboard = new ClipboardJS('[data-to-copy]', {
            text: function(trigger) {
                return trigger.getAttribute('data-to-copy');
            }
        });

        document.querySelectorAll('[data-to-copy]').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
            });
        });

        clipboard.on('success', function(e) {
            $('.menu-icon', e.trigger).html('<i class="ki-duotone ki-double-check text-success"><span class="path1"></span> <span class="path2"></span></i>');
        });
    });

    window.debounce = function (func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    $('#media-search').keyup(debounce(function() {
        myTable.search($(this).val()).draw();
    }, 300));

    $('#filterAlbum, #filterExt, #filterDisk, #filterModel').on('change', function() {
        myTable.draw();
    });

    $('#myTable').on('click', '.playAudio', function() {

    });

    $('#myTable').on('click', '.btnDeleteOrigin', function() {
        $(this).attr('disabled', 'disabled');
        $(this).parents('tr').css('opacity', '0.3');
        $('.menu-icon', this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.post('{{ route('lara-media.index') }}/delete/' + $(this).data('id'), {
            _token: '{{ csrf_token() }}',
            version: 'original'
        }).fail(function(xhr) {
            console.log(xhr)
            alert(xhr.responseJSON.message);
        }).done(function() {
            myTable.ajax.reload();
        });
    });

    $('#myTable').on('click', '.btnDeleteAll', function() {
        $(this).attr('disabled', 'disabled');
        $(this).parents('tr').css('opacity', '0.3');
        $('.menu-icon', this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.post('{{ route('lara-media.index') }}/delete/' + $(this).data('id'), {
            _token: '{{ csrf_token() }}'
        }).fail(function(xhr) {
            console.log(xhr)
            alert(xhr.responseJSON.message);
        }).done(function() {
            myTable.ajax.reload();
        });
    });

    $('#myTable').on('click', '.dtr-control', function(e) {
        if (e.target !== this) {
            return;
        }

        var tr = $(this).closest('tr');
        var row = myTable.row(tr);
        var media = row.data();

        if (Object.keys(media.conversions).length === 0 && Object.keys(media.responsive).length === 0) {
            return;
        }

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            var cloneTr = '';

            for (const [key, value] of Object.entries(media.conversions)) {
                cloneTr += '<tr class="rounded-3">';
                cloneTr += `<td class="text-center ps-5"><div class="symbol symbol-50px rounded cursor-pointer">
                        ${media.preview ? '<img src="'+media.preview+'" alt="preview" />' :
                    (mimeTypeIsVideo(media.mime_type) ?
                            `<div class="d-flex w-50px h-50px align-items-center justify-content-center overlay-wrapper text-gray-600">
                                <i class="ki-duotone ki-youtube fs-3x"><span class="path1"></span><span class="path2"></span></i>
                            </div>` :
                            `<div class="d-flex w-50px h-50px align-items-center justify-content-center overlay-wrapper text-gray-600">
                                <i class="bi bi-activity fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>`
                    )}
                    </div></td>`;
                cloneTr += `<td><div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">
                            <span class="text-muted">Version</span> ${key}${mimeTypeIsVideo(media.mime_type) ? 'p' : ''}
                            ${mimeTypeIsVideo(media.mime_type) ? '<span class="badge badge-light-warning">'+media.properties.ext+'</span>': ''}
                        </div>
                        <div class="fs-7">Conversion took ${readableSeconds(value.took)}</div>
                    </div></td>`;
                cloneTr += `<td class="text-end"><div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${ucFirst(media.properties.conversion_disk)}</div>
                        <div class="fs-7">${getReadableFileSizeString(value.size)}</div>
                    </div></td>`;
                cloneTr += `<td class="text-end pe-5">
                        <button type="button" class="mx-1 btn-sm btn-outline btn-outline-dashed btn btn-icon btn-outline-primary" data-to-copy="${media.urls.find((i) => i.version == key).url}">
                            <i class="ki-duotone ki-disconnect fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                        </button>
                        <button type="button" class="btnDelete mx-1 btn-sm btn-outline btn-outline-dashed btn btn-icon btn-outline-danger" data-id="${media._id}" data-version="${key}">
                            <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                        </button>
                    </td>`;
                cloneTr += '</tr>';
            }

            for (const [key, value] of Object.entries(media.responsive)) {
                var preview = media.urls.find((i) => i.version == key);
                cloneTr += '<tr class="rounded-3">';
                cloneTr += `<td class="text-center ps-5"><div class="symbol symbol-50px rounded cursor-pointer">
                        <img src="${preview.url}" alt="preview" />
                    </td>`;
                cloneTr += `<td><div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">
                            <span class="text-muted">Version</span> ${key}
                        </div>
                        <div class="fs-7">Conversion took ${readableSeconds(value.took)}</div>
                    </div></td>`;
                cloneTr += `<td class="text-end"><div class="d-flex flex-column text-muted">
                        <div class="text-gray-800 fw-bold">${ucFirst(media.properties.conversion_disk)}</div>
                        <div class="fs-7">${getReadableFileSizeString(value.size)}</div>
                    </div></td>`;
                cloneTr += `<td class="text-end pe-5">
                        <button type="button" class="mx-1 btn-sm btn-outline btn-outline-dashed btn btn-icon btn-outline-primary" data-to-copy="${preview.url}">
                            <i class="ki-duotone ki-disconnect fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                        </button>
                        <button type="button" class="btnDelete mx-1 btn-sm btn-outline btn-outline-dashed btn btn-icon btn-outline-danger" data-id="${media._id}" data-version="${key}">
                            <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                        </button>
                    </td>`;
                cloneTr += '</tr>';
            }

            row.child('<table class="w-100 bg-light">' + cloneTr + '</table>').show();
            tr.addClass('shown');

            tr.next('tr').find('[data-to-copy]').each(function() {
                new ClipboardJS(this, {
                    text: function(trigger) {
                        return trigger.getAttribute('data-to-copy');
                    }
                }).on('success', function(e) {
                    $(e.trigger).html('<i class="ki-duotone ki-double-check text-success"><span class="path1"></span> <span class="path2"></span></i>')
                });
            });

            tr.next('tr').find('.btnDelete').each(function() {
                $(this).click(function() {
                    $(this).parents('tr').css('opacity', '0.3');
                    $(this).attr('disabled', 'disabled')
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                    $.post('{{ route('lara-media.index') }}/delete/' + $(this).data('id'), {
                        version: $(this).data('version'),
                        _token: '{{ csrf_token() }}'
                    }).fail(function(xhr) {
                        console.log(xhr)
                        alert(xhr.responseJSON.message);
                    }).always(function() {
                        myTable.ajax.reload(null, false);
                    });
                })
            });
        }
    });
</script>
@endscript
