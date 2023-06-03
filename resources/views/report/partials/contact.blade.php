@extends('layouts.app')
@section('title', __('report.customer') . ' - ' . __('report.supplier') . ' ' . __('report.reports'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.customer')}} & {{ __('report.supplier')}} Balances - Days Outstanding</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <!-- <div class="col-md-3 cs_report_filter">
                <div class="form-group">
                    {!! Form::label('cg_customer_group_id', __( 'lang_v1.customer_group_name' ) . ':') !!}
                    {!! Form::select('cnt_customer_group_id', $customer_group, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'cnt_customer_group_id']); !!}
                </div>
            </div>

            <div class="col-md-3 cs_report_filter">
                <div class="form-group">
                    {!! Form::label('type', __( 'lang_v1.type' ) . ':') !!}
                    {!! Form::select('contact_type', $types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'contact_type']); !!}
                </div>
            </div> -->

            <div class="col-md-3" id="location_filter_sup">
                <div class="form-group">
                    {!! Form::label('location_id_sup', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id_sup', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3" id="location_filter_cus" style="display: none;">
                <div class="form-group">
                    {!! Form::label('location_id_cus', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id_cus', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3" id="customer_filter" style="display: none;">
                <div class="form-group">
                    {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3" id="supplier_filter">
                <div class="form-group">
                    {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                    {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                </div>
            </div>
            <div class="col-md-3" id="date_filter">
                <div class="form-group">
                        <label for="supplier_id">Run at Date:</label>
                        <input type="date" class="form-control" name="data_f" id="date_f" value=""/>
                </div>
            </div>



            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <!-- <li class="active">
                        <a href="#cus_sup_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes" aria-hidden="true"></i>Customers & Suppliers Reports</a>
                    </li> -->
                    <li class="active">
                        <a href="#sup_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i>Suppliers Balances - Days Outstanding</a>
                    </li>
                    <li>
                        <a href="#cus_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i>Customers Balances - Days Outstanding</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- <div class="tab-pane active" id="cus_sup_report">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="supplier_report_tbl">
                                <thead>
                                    <tr>
                                        <th>@lang('report.contact')</th>
                                        <th>@lang('report.total_purchase')</th>
                                        <th>@lang('lang_v1.total_purchase_return')</th>
                                        <th>@lang('report.total_sell')</th>
                                        <th>@lang('lang_v1.total_sell_return')</th>
                                        <th>@lang('lang_v1.opening_balance_due')</th>
                                        <th>@lang('report.total_due') &nbsp;&nbsp;<i class="fa fa-info-circle text-info no-print" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="{{ __('messages.due_tooltip')}}" aria-hidden="true"></i></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td><strong>@lang('sale.total'):</strong></td>
                                        <td><span class="display_currency" id="footer_total_purchase" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_total_purchase_return" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_total_sell" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_total_sell_return" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_total_opening_bal_due" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_total_due" data-currency_symbol="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div> -->
                    <div class="tab-pane active" id="sup_report">
                    </div>
                    <div class="tab-pane" id="cus_report">
                    </div>
                    <p>An aging report, also called an accounts receivable aging report, is a record of overdue invoices from a specific time period that is used to measure the financial health of the company and its customers. Aging reports display overdue payments.</p>
                
                </div>
                

            </div>

        </div>
    </div>
</section>
<div class="modal fade view_product_detail_model border-top-model-popup" id="view_product_detail_model" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<!-- /.content -->

@endsection
@section('css')
<style>
    .ageingfilter {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-bottom: 20px;
    }

    .select2-container {
        width: 100% !important;
    }

    .canvasjs-chart-credit {
        display: none !important;
    }

    #customer_ageing_report_tbl_wrapper,
    #supplier_ageing_report_tbl_wrapper {
        margin-top: 30px;
    }

    .canvasoverlay {
        position: absolute;
        right: 0;
        width: 83px;
        height: 10px;
        background-color: #fff;
        bottom: 0;
    }

    .p-relative {
        position: relative;
    }

    table a {
        cursor: pointer;
    }
    .bg-blue {
        background-color: transparent !important;
    }

    /* .bg-blue {
        background-color: #98ddfc !important;
    }

    .bg-danger {
        background-color: #f2dede !important;
    }

    .bg-yellow {
        background-color: #ffefae !important;
    color: #000 !important;
    } */
</style>
@endsection
@section('javascript')
<script src='https://canvasjs.com/assets/script/canvasjs.min.js'></script>
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script>
    var sup_report_data_table_init = false;
    var cus_report_data_table_init = false;
    getSupplierAgeing();
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        if ($(e.target).attr('href') == '#sup_report') {
            $('.cs_report_filter').hide();
            $('#location_filter_sup').show();
            $('#supplier_filter').show();
            $('#customer_filter').hide();
            $('#location_filter_cus').hide();

            getSupplierAgeing();

        } else if ($(e.target).attr('href') == '#cus_report') {
            $('.cs_report_filter').hide();
            $('#location_filter_cus').show();
            $('#location_filter_sup').hide();
            $('#customer_filter').show();
            $('#supplier_filter').hide();
            getCustomerAgeing();

        } else {
            $('.cs_report_filter').show();
            $('#location_filter_cus').hide();
            $('#location_filter_sup').hide();
            $('#customer_filter').hide();
            $('#supplier_filter').hide();
            supplier_report_tbl.ajax.reload();
        }
    });


    function getCustomerAgeing() {
        var data = {
            contact_type: 'customer',
            location_id: $('#location_id_cus').val(),
            customer_id: $('#customer_id').val(),
            date: $('#date_f').val(),
        };
        $.ajax({
            method: 'GET',
            url: "{{route('supplier-ageing')}}",
            dataType: 'html',
            data: data,
            success: function(html) {
                $('#cus_report').html(html);

                $('#customer_ageing_report_tbl').DataTable({
                    dom: '<"ageingfilter"lBfr>tip',
                    buttons: [{
                        extend: 'csvHtml5',
                        text: '<i class="fa fa-file-csv"></i> Export to CSV',
                        titleAttr: 'CSV',
                        className: 'btn btn-default buttons-csv buttons-html5 btn-sm'
                    }, {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Export to Excel',
                        titleAttr: 'Excel',
                        className: 'btn btn-default buttons-excel buttons-html5 btn-sm'
                    }, {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        titleAttr: 'Print',
                        className: 'btn btn-default buttons-print buttons-html5 btn-sm'
                    }, {
                        extend: 'colvis',
                        text: '<i class="fa fa-columns"></i> Column visibility',
                        titleAttr: 'colvis',
                        className: 'btn btn-default buttons-collection buttons-colvis btn-sm'
                    }, {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> Export to PDF',
                        titleAttr: 'Excel',
                        className: 'btn btn-default buttons-pdf buttons-html5 btn-sm'
                    }],
                    fnDrawCallback: function(oSettings) {

                        var current_cus = sum_table_col($('#customer_ageing_report_tbl'), 'current_cus');
                        $('#footer_total_current_cus').text(current_cus);

                        var due_1to30_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_1to30_cus');
                        $('#footer_total_due_cus_0_30').text(due_1to30_cus);

                        var due_31to60_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_31to60_cus');
                        console.log(due_31to60_cus);
                        $('#footer_total_due_cus_31_60').text(due_31to60_cus);

                        var due_61to90_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_61to90_cus');
                        $('#footer_total_due_cus_61_90').text(due_61to90_cus);

                        var due_91to120_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_91to120_cus');
                        $('#footer_total_due_cus_91_120').text(due_91to120_cus);

                        var due_121to150_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_121to150_cus');
                        $('#footer_total_due_cus_121_150').text(due_121to150_cus);

                        var due_151to180_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_151to180_cus');
                        $('#footer_total_due_cus_151_180').text(due_151to180_cus);

                        var due_180plus_cus = sum_table_col($('#customer_ageing_report_tbl'), 'due_180plus_cus');
                        $('#footer_total_due_cus_180').text(due_180plus_cus);

                        var total_due_cus = sum_table_col($('#customer_ageing_report_tbl'), 'total_due_cus');
                        $('#footer_total_due_cus').text(total_due_cus);
                        __currency_convert_recursively($('#customer_ageing_report_tbl'));
                    },
                });
            },
        });
    }

    function getSupplierAgeing() {
        var data = {
            contact_type: 'supplier',
            location_id: $('#location_id_sup').val(),
            supplier_id: $('#supplier_id').val(),
            date: $('#date_f').val(),
        };
        $.ajax({
            method: 'GET',
            url: "{{route('supplier-ageing')}}",
            dataType: 'html',
            data: data,
            success: function(html) {
                $('#sup_report').html(html);

                var supplier_ageing_report_tbl = $('#supplier_ageing_report_tbl').DataTable({
                    dom: '<"ageingfilter"lBfr>tip',
                    buttons: [{
                        extend: 'csvHtml5',
                        text: '<i class="fa fa-file-csv"></i> Export to CSV',
                        titleAttr: 'CSV',
                        className: 'btn btn-default buttons-csv buttons-html5 btn-sm'
                    }, {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Export to Excel',
                        titleAttr: 'Excel',
                        className: 'btn btn-default buttons-excel buttons-html5 btn-sm'
                    }, {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        titleAttr: 'Print',
                        className: 'btn btn-default buttons-print buttons-html5 btn-sm'
                    }, {
                        extend: 'colvis',
                        text: '<i class="fa fa-columns"></i> Column visibility',
                        titleAttr: 'colvis',
                        className: 'btn btn-default buttons-collection buttons-colvis btn-sm'
                    }, {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> Export to PDF',
                        titleAttr: 'Excel',
                        className: 'btn btn-default buttons-pdf buttons-html5 btn-sm'
                    }],
                    fnDrawCallback: function(oSettings) {

                        var current = sum_table_col($('#supplier_ageing_report_tbl'), 'current');
                        $('#footer_total_current_sup').text(current);

                        var total_purchase = sum_table_col($('#supplier_ageing_report_tbl'), 'total_purchase');
                        $('#footer_total_purchase_sup').text(total_purchase);

                        var total_purchase_return = sum_table_col($('#supplier_ageing_report_tbl'), 'total_purchase_return');
                        $('#footer_total_purchase_return_sup').text(total_purchase_return);

                        var total_sell = sum_table_col($('#supplier_ageing_report_tbl'), 'total_invoice');
                        $('#footer_total_sell_sup').text(total_sell);

                        var total_sell_return = sum_table_col($('#supplier_ageing_report_tbl'), 'total_sell_return');
                        $('#footer_total_sell_return_sup').text(total_sell_return);

                        var total_opening_bal_due = sum_table_col($('#supplier_ageing_report_tbl'), 'opening_balance_due');
                        $('#footer_total_opening_bal_due_sup').text(total_opening_bal_due);

                        var total_due = sum_table_col($('#supplier_ageing_report_tbl'), 'total_due');
                        $('#footer_total_due_sup').text(total_due);

                        var due_1to30 = sum_table_col($('#supplier_ageing_report_tbl'), 'due_1to30');
                        $('#footer_total_due_sup_0_30').text(due_1to30);

                        var due_31to60 = sum_table_col($('#supplier_ageing_report_tbl'), 'due_31to60');
                        $('#footer_total_due_sup_31_60').text(due_31to60);

                        var due_61to90 = sum_table_col($('#supplier_ageing_report_tbl'), 'due_61to90');
                        $('#footer_total_due_sup_61_90').text(due_61to90);

                        var due_91to120 = sum_table_col($('#supplier_ageing_report_tbl'), 'due_91to120');
                        $('#footer_total_due_sup_91_120').text(due_91to120);

                        var due_121to150 = sum_table_col($('#supplier_ageing_report_tbl'), 'due_121to150');
                        $('#footer_total_due_sup_121_150').text(due_121to150);

                        var due_151to180 = sum_table_col($('#supplier_ageing_report_tbl'), 'due_151to180');
                        $('#footer_total_due_sup_151_180').text(due_151to180);

                        var due_180plus = sum_table_col($('#supplier_ageing_report_tbl'), 'due_180plus');
                        $('#footer_total_due_sup_180').text(due_180plus);

                        __currency_convert_recursively($('#supplier_ageing_report_tbl'));
                    },
                });
                supplier_ageing_report_tbl.buttons().container().appendTo('#sup_report .col-sm-6:eq(0)');
            },
        });
    }

    $('#location_filter_cus .form-control').change(function() {
        getCustomerAgeing();
    });
    $('#location_filter_sup .form-control').change(function() {
        getSupplierAgeing();
    });
    $('#customer_filter .form-control').change(function() {
        getCustomerAgeing();
    });
    $('#supplier_filter .form-control').change(function() {
        getSupplierAgeing();
    });
    $('#date_filter .form-control').change(function() {
        if ($("#sup_report").hasClass('active')) {
            getSupplierAgeing();
        }else{
            getCustomerAgeing();
        }
        
    });

    $(document).on('click', '.getdetails', function(e) {
        e.preventDefault();
        $('.view_product_detail_model').html('');
        $('.view_product_detail_model').modal('show');
        $('.view_product_detail_model').html('<div class="loader"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
        $.ajax({
            url: "/reports/get-ageing-details",
            method: 'POST',
            data: {
                contact_id: $(this).attr("data-contact_id"),
                col: $(this).attr("data-col"),
                date: $('#date_f').val(),
            },
            success: function(data) {
                $('.view_product_detail_model').html(data.details);
                __currency_convert_recursively($('.view_product_detail_model'));
                $('#ageing_detail_report').DataTable({
                    dom: 'Bfrtip',
                    info: false,
                    bSort: false,
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Export to Excel',
                        titleAttr: 'Excel',
                        className: 'btn btn-default buttons-excel buttons-html5 btn-sm'
                    }, {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        titleAttr: 'Excel',
                        className: 'btn btn-default buttons-print buttons-html5 btn-sm'
                    }],
                    pageLength: 20,
                    fnDrawCallback: function(oSettings) {
                        var total_ageing_due = sum_table_col($('#ageing_detail_report'), 'total_ageing_due');
                        $('#footer_total_ageing_due').text(total_ageing_due);

                        var total_opening_balance_due = sum_table_col($('#ageing_detail_report'), 'total_opening_balance_due');
                        $('#footer_total_opening_balance_due').text(total_opening_balance_due);

                        var total_sell_return = sum_table_col($('#ageing_detail_report'), 'total_sell_return');
                        $('#footer_total_sell_return').text(total_sell_return);

                        var total_invoice = sum_table_col($('#ageing_detail_report'), 'total_invoice');
                        $('#footer_total_invoice').text(total_invoice);

                        var total_purchase = sum_table_col($('#ageing_detail_report'), 'total_purchase');
                        $('#footer_total_purchase').text(total_purchase);

                        var total_purchase_return = sum_table_col($('#ageing_detail_report'), 'total_purchase_return');
                        $('#footer_total_purchase_return').text(total_purchase_return);

                        __currency_convert_recursively($('#ageing_detail_report'));

                    },
                });
            }
        });


    });
</script>
@endsection