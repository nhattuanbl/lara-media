<div class="col-xxl-6 mb-5 mb-xl-10">
    <div class="card card-flush h-xl-100">
        <div class="card-header py-7">
            <div class="m-0">
                <div class="d-flex align-items-center mb-2">
                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">{{ $sum_total_size }}</span>
                    <span class="badge badge-light-primary fs-base">
{{--                        <i class="ki-outline ki-arrow-up fs-5 text-success ms-n1"></i>--}}
                        {{ $sum_total_unit }}
                    </span>
                </div>
                <span class="fs-6 fw-semibold text-gray-500">Storage usage</span>
            </div>
            <div class="card-toolbar">
                <button class="btn btn-icon btn-color-gray-500 btn-active-color-primary justify-content-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-overflow="true">
                    <i class="ki-outline ki-dots-square fs-1 text-gray-500 me-n1"></i>
                </button>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">View By</div>
                    </div>
                    <div class="separator mb-3 opacity-75"></div>
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3" wire:click.prevent="viewDay">Day</a>
                    </div>
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3" wire:click.prevent="viewMonth">Month</a>
                    </div>
                    <div class="separator mt-3 opacity-75"></div>
                    <div class="menu-item px-3">
                        <div class="menu-content px-3 py-3">
                            <button type="button" class="btn btn-primary btn-sm px-4">Export</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0 pb-1" wire:init.lazy="shitLazy">
            <div id="capacityChart" class="h-400px min-h-auto"></div>
        </div>
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endassets

@script
<script>
    $wire.on('lazyLoaded', () => {
        setTimeout(() => {
            initApex()
        }, 50)

        KTMenu.createInstances();

    });

    function initApex()
    {
        new ApexCharts(document.getElementById('capacityChart'), {
            series: [{
                name: 'Capacity',
                data: @json($capacityChart)
            }, {
                name: 'Media',
                data: @json($filesChart)
            }],

            chart: {
                fontFamily: 'inherit',
                type: 'area',
                height: '100%',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {

            },
            legend: {
                show: false
            },
            dataLabels: {
                enabled: false
            },
            fill: {
                type: 'solid',
                opacity: 1
            },
            stroke: {
                curve: 'smooth',
                show: true,
                width: 3,
                colors: ['#1B84FF', '#17C653']
            },
            xaxis: {
                categories: @json($xAxis),
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: '#99A1B7',
                        fontSize: '12px'
                    }
                },
                crosshairs: {
                    position: 'front',
                    stroke: {
                        color: '#7239EA',
                        width: 1,
                        dashArray: 3
                    }
                },
                tooltip: {
                    enabled: true,
                    formatter: undefined,
                    offsetY: 0,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return Math.round(val) + ' MB'
                    },
                    style: {
                        colors: '#99A1B7',
                        fontSize: '12px'
                    }
                }
            },
            states: {
                normal: {
                    filter: {
                        type: 'none',
                        value: 0
                    }
                },
                hover: {
                    filter: {
                        type: 'none',
                        value: 0
                    }
                },
                active: {
                    allowMultipleDataPointsSelection: false,
                    filter: {
                        type: 'none',
                        value: 0
                    }
                }
            },
            tooltip: {
                style: {
                    fontSize: '12px'
                },
                y: {
                    formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                        return seriesIndex === 0 ? value + ' MB' : value
                    }
                },
            },
            colors: ['#E9F3FF', '#DFFFEA'],
            grid: {
                borderColor: '#F1F1F4',
                strokeDashArray: 3,
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            markers: {
                strokeColor: '#7239EA',
                strokeWidth: 3
            }
        }).render();
    }

</script>
@endscript
