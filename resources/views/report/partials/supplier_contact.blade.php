<script>
    $(document).ready(function() {
        var columnChartValues = [{
            y: <?php echo array_sum($due1to30Array); ?>,
            label: "1-30 Days",
            color: "#1f77b4"
        }, {
            y: <?php echo array_sum($due31to60Array); ?>,
            label: "31-60 Days",
            color: "#ff7f0e"
        }, {
            y: <?php echo array_sum($due61to90Array); ?>,
            label: "61-90 Days",
            color: " #ffbb78"
        }, {
            y: <?php echo array_sum($due91to120Array); ?>,
            label: "91-120 Days",
            color: "#d62728"
        }, {
            y: <?php echo array_sum($due121to150Array); ?>,
            label: "121-150 Days",
            color: "#98df8a"
        }, {
            y: <?php echo array_sum($due151to180Array); ?>,
            label: "151-180 Days",
            color: "#bcbd22"
        }, {
            y: <?php echo array_sum($due180plusArray); ?>,
            label: ">= 180 Days",
            color: "#f7b6d2"
        }];

        renderColumnChart(columnChartValues);

        function renderColumnChart(values) {

            var chart = new CanvasJS.Chart("columnChartsup", {
                backgroundColor: "white",
                colorSet: "colorSet3",
                title: {
                    text: "Supplier Balances - Days Outstanding Report",
                    fontFamily: "Verdana",
                    fontSize: 22,
                    fontWeight: "normal",
                },
                animationEnabled: true,
                legend: {
                    verticalAlign: "bottom",
                    horizontalAlign: "center"
                },
                theme: "theme2",
                data: [

                    {
                        indexLabelFontSize: 15,
                        indexLabelFontFamily: "Monospace",
                        indexLabelFontColor: "darkgrey",
                        indexLabelLineColor: "darkgrey",
                        indexLabelPlacement: "outside",
                        type: "column",
                        showInLegend: false,
                        legendMarkerColor: "grey",
                        dataPoints: values
                    }
                ]
            });

            chart.render();
        }
    });
</script>
<div class="container-fluid p-relative">
    <div class="row">
        <div class="col-md-12">
            <div id="columnChartsup" style="height: 360px; width: 100%;">
            </div>
        </div>
    </div>
    <div class="canvasoverlay"></div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="supplier_ageing_report_tbl">
        <thead>
            <tr>
                <th>@lang('report.contact')</th>
                <th>Current <i class="fa fa-info-circle text-info no-print" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="{{ __('messages.due_tooltip')}}" aria-hidden="true"></i></th>
                <th>1-30 Days</th>
                <th>31-60 Days</th>
                <th>61-90 Days</th>
                <th>91-120 Days</th>
                <th>121-150 Days</th>
                <th>151-180 Days</th>
                <th>>= 180 Days</th>
                <th>Total Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contacts as $key=>$row)
            @if($currentArray[$key] != '0' || $due1to30Array[$key]!= '0' || $due31to60Array[$key]!= '0' || $due61to90Array[$key]!= '0' || $due91to120Array[$key]!= '0' || $due121to150Array[$key]!= '0' || $due151to180Array[$key]!= '0' || $due180plusArray[$key]!= '0' || $totalDueArray[$key]!= '0')
            <?php $calss = '';
            if ($due180plusArray[$key] > 0 || $due180plusArray[$key] < 0) {
                $calss = 'bg-danger';
            } elseif ($due151to180Array[$key] > 0 || $due151to180Array[$key] < 0) {
                $calss = 'bg-yellow';
            } elseif ($due121to150Array[$key] > 0 || $due121to150Array[$key] < 0) {
                $calss = 'bg-yellow';
            } elseif ($due91to120Array[$key] > 0 || $due91to120Array[$key] < 0) {
                $calss = 'bg-yellow';
            } elseif ($due61to90Array[$key] > 0 || $due61to90Array[$key] < 0) {
                $calss = 'bg-yellow';
            } elseif ($due31to60Array[$key] > 0 || $due31to60Array[$key] < 0) {
                $calss = 'bg-yellow';
            } elseif ($due1to30Array[$key] > 0 || $due1to30Array[$key] < 0) {
                $calss = 'bg-blue';
            }
            ?>
            <tr class="">
                <?php $name = $row->name;
                ?>
                <td><a href="{{action('ContactController@show', [$row->id])}}" target="_blank" class="no-print">{{$name}}</a><span class="print_section">{{$name}}</span></td>
                <td>
                    @if($currentArray[$key] == 0)
                    <span class="display_currency current" data-currency_symbol=true data-orig-value="{{$currentArray[$key]}}">{{$currentArray[$key]}}</span> @else
                    <a class="getdetails display_currency current @if($currentArray[$key] < 0) text-danger @endif @if($currentArray[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$currentArray[$key]}}" data-contact_id="{{$row->id}}" data-col="1">{{$currentArray[$key]}}</a>@endif
                </td>
                <td>
                    @if($due1to30Array[$key] == 0)
                    <span class="display_currency due_1to30" data-currency_symbol=true data-orig-value="{{$due1to30Array[$key]}}">{{$due1to30Array[$key]}}</span> @else
                    <a class="getdetails display_currency due_1to30 @if($due1to30Array[$key] < 0) text-danger @endif @if($due1to30Array[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due1to30Array[$key]}}" data-contact_id="{{$row->id}}" data-col="2">{{$due1to30Array[$key]}}</a>@endif
                </td>
                <td>
                    @if($due31to60Array[$key] == 0)
                    <span class="display_currency due_31to60" data-currency_symbol=true data-orig-value="{{$due31to60Array[$key]}}">{{$due31to60Array[$key]}}</span> @else
                    <a class="getdetails display_currency due_31to60 @if($due31to60Array[$key] < 0) text-danger @endif @if($due31to60Array[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due31to60Array[$key]}}" data-contact_id="{{$row->id}}" data-col="3">{{$due31to60Array[$key]}}</a>@endif
                </td>
                <td>
                    @if($due61to90Array[$key] == 0)
                    <span class="display_currency due_61to90" data-currency_symbol=true data-orig-value="{{$due61to90Array[$key]}}">{{$due61to90Array[$key]}}</span> @else
                    <a class="getdetails display_currency due_61to90 @if($due61to90Array[$key] < 0) text-danger @endif @if($due61to90Array[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due61to90Array[$key]}}" data-contact_id="{{$row->id}}" data-col="4">{{$due61to90Array[$key]}}</a>@endif
                </td>
                <td>
                    @if($due91to120Array[$key] == 0)
                    <span class="display_currency due_91to120" data-currency_symbol=true data-orig-value="{{$due91to120Array[$key]}}">{{$due91to120Array[$key]}}</span> @else
                    <a class="getdetails display_currency due_91to120 @if($due91to120Array[$key] < 0) text-danger @endif @if($due91to120Array[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due91to120Array[$key]}}" data-contact_id="{{$row->id}}" data-col="5">{{$due91to120Array[$key]}}</a>@endif
                </td>
                <td>
                    @if($due121to150Array[$key] == 0)
                    <span class="display_currency due_121to150" data-currency_symbol=true data-orig-value="{{$due121to150Array[$key]}}">{{$due121to150Array[$key]}}</span> @else
                    <a class="getdetails display_currency due_121to150 @if($due121to150Array[$key] < 0) text-danger @endif @if($due121to150Array[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due121to150Array[$key]}}" data-contact_id="{{$row->id}}" data-col="6">{{$due121to150Array[$key]}}</a>@endif
                </td>
                <td>
                    @if($due151to180Array[$key] == 0)
                    <span class="display_currency due_151to180" data-currency_symbol=true data-orig-value="{{$due151to180Array[$key]}}">{{$due151to180Array[$key]}}</span> @else
                    <a class="getdetails display_currency due_151to180 @if($due151to180Array[$key] < 0) text-danger @endif @if($due151to180Array[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due151to180Array[$key]}}" data-contact_id="{{$row->id}}" data-col="7">{{$due151to180Array[$key]}}</a>@endif
                </td>
                <td>
                    @if($due180plusArray[$key] == 0)
                    <span class="display_currency due_180plus" data-currency_symbol=true data-orig-value="{{$due180plusArray[$key]}}">{{$due180plusArray[$key]}}</span> @else
                    <a class="getdetails display_currency due_180plus @if($due180plusArray[$key] < 0) text-danger @endif @if($due180plusArray[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$due180plusArray[$key]}}" data-contact_id="{{$row->id}}" data-col="8">{{$due180plusArray[$key]}}</a>@endif
                </td>
                <td>
                    @if($totalDueArray[$key] == 0)
                    <span class="display_currency total_due" data-currency_symbol=true data-orig-value="{{$totalDueArray[$key]}}">{{$totalDueArray[$key]}}</span> @else
                    <a class="getdetails display_currency total_due @if($totalDueArray[$key] < 0) text-danger @endif @if($totalDueArray[$key] > 0) text-success @endif" data-currency_symbol=true data-orig-value="{{$totalDueArray[$key]}}" data-contact_id="{{$row->id}}" data-col="9">{{$totalDueArray[$key]}}</a>@endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray font-17 footer-total text-center">
                <td><strong>@lang('sale.total'):</strong></td>
                <td><span class="display_currency" id="footer_total_current_sup" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_0_30" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_31_60" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_61_90" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_91_120" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_121_150" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_151_180" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_180" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup" data-currency_symbol="true"></span></td>
            </tr>
        </tfoot>
    </table>
</div>