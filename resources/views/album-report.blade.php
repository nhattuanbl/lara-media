<div class="col-xxl-6 mb-5 mb-xl-10">
    <div class="card card-flush h-xl-100">
        <div class="card-header py-7">
            <div class="m-0">
                <div class="d-flex align-items-center mb-2">
                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">{{ $sumFiles }} Media </span>
                    <span class="badge badge-light-danger fs-base">
{{--                        <i class="ki-outline ki-arrow-up fs-5 text-danger ms-n1"></i>--}}
                        {{ $sumVersions }} Versions
                    </span>
                </div>
                <span class="fs-6 fw-semibold text-gray-500">Albums usage</span>
            </div>
        </div>
        <div class="card-body pt-0 pb-1" wire:init.lazy="shitLazy">
            <div id="albumChart" class="h-400px w-auto"></div>
        </div>
    </div>
</div>

@assets
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
<script src="https://cdn.amcharts.com/lib/5/fonts/notosans-sc.js"></script>
@endassets

@script
<script>
    $wire.on('lazyLoaded', () => {
        setTimeout(() => {
            initAm5()
        }, 50)
    });

    function initAm5()
    {
        am5.ready(function () {
            am5.addLicense("AM5C378450700");
            var root = am5.Root.new("albumChart");

            var myTheme = am5.Theme.new(root);
            myTheme.rule("Label").setAll({
                fontFamily: "Noto Sans, sans-serif",
                fontSize: 11,
                fontWeight: 500,
            });

            root.setThemes([
                am5themes_Animated.new(root),
                myTheme
            ]);

            var chart = root.container.children.push(am5percent.PieChart.new(root, {
                layout: root.verticalLayout
            }));

            var series = chart.series.push(am5percent.PieSeries.new(root, {
                alignLabels: true,
                calculateAggregates: true,
                valueField: "value",
                categoryField: "category",
            }));

            series.get("colors").set("colors", Object.entries(themeColors)
                .filter(([key]) => !key.startsWith('gray'))
                .map(([, hex]) => am5.color('#' + hex))
            );

            series.slices.template.setAll({
                strokeWidth: 3,
                stroke: am5.color(0xffffff)
            });

            series.slices.template.set("tooltipText", "{category}: {value}");
            series.labelsContainer.set("paddingTop", 30)

            series.slices.template.adapters.add("radius", function (radius, target) {
                var dataItem = target.dataItem;
                var high = series.getPrivate("valueHigh");

                if (dataItem) {
                    var value = target.dataItem.get("valueWorking", 0);
                    return radius * value / high
                }
                return radius;
            });

            series.data.setAll(@json($chartData));

            var legend = chart.children.push(am5.Legend.new(root, {
                centerX: am5.p50,
                x: am5.p50,
                marginTop: 15,
                marginBottom: 15
            }));

            legend.data.setAll(series.dataItems);

            series.appear(1000, 100);
        });
    }
</script>
@endscript
