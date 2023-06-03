@extends('layouts.app')
@section('title', __('sale.products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('sale.products')
        <small>@lang('lang_v1.manage_products')</small>
    </h1>
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
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('product.product_type') . ':') !!}
                    {!! Form::select('type', ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('category_id', __('category.category') . ':') !!}
                    {!! Form::select('category_id', $categories, null, ['placeholder' => __('lang_v1.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_category_id']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                    {!! Form::select('sub_category', array(), null, ['placeholder' => __('lang_v1.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_sub_category_id']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_id', __('product.unit') . ':') !!}
                    {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_unit_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('tax_id', __('product.tax') . ':') !!}
                    {!! Form::select('tax_id', $taxes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_tax_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('brand_id', __('product.brand') . ':') !!}
                    {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="active_state">&nbsp</label>
                    {!! Form::select('active_state', ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'active_state', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>


            <!-- include module filter -->
            @if(!empty($pos_module_data))
            @foreach($pos_module_data as $key => $value)
            @if(!empty($value['view_path']))
            @includeIf($value['view_path'], ['view_data' => $value['view_data']])
            @endif
            @endforeach
            @endif

            <div class="col-md-3">
                <div class="form-group">
                    <br>
                    <label>
                        {!! Form::checkbox('not_for_selling', 1, false, ['class' => 'input-icheck', 'id' => 'not_for_selling']); !!} <strong>@lang('lang_v1.not_for_selling')</strong>
                    </label>
                </div>
            </div>
            <div class="col-md-9 col-xs-12" id="pdatefilter" style="display:none;">
                <div class="row">
                    <div class="form-group pull-right">
                        <div class="input-group">
                            <button type="button" class="btn btn-primary" id="profit_loss_date_filter">
                                <span>
                                    <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                                </span>
                                <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    @can('product.view')
    <div class="row">
        <div class="col-md-12">
            <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes" aria-hidden="true"></i> @lang('lang_v1.all_products')</a>
                    </li>

                    <li>
                        <a href="#product_stock_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i> @lang('report.stock_report')</a>
                    </li>
                    <li>
                        <a href="#product_profitibility_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i>Product Profitability</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="product_list_tab">
                        @can('product.create')
                        <a class="btn btn-primary pull-right" href="{{action('ProductController@create')}}">
                            <i class="fa fa-plus"></i> @lang('messages.add')</a>
                        <br><br>
                        @endcan
                        @include('product.partials.product_list')
                    </div>

                    <div class="tab-pane" id="product_stock_report">
                        @include('report.partials.stock_report_table')
                    </div>
                    <div class="tab-pane" id="product_profitibility_report">

                        @include('report.partials.product_profitibility_report_table')

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    <input type="hidden" id="is_rack_enabled" value="{{$rack_enabled}}">

    <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="opening_stock_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('product.partials.edit_product_location_modal')

</section>
<!-- /.content -->
<div class="modal fade view_product_detail_model border-top-model-popup" id="view_product_detail_model" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('javascript')
<style>
    .minus {
        color: #FB2171;
    }

    .plus {
        color: #10BA66;
    }

    .pbg-blue {
        background-color: #5dade2 !important;
    }

    .pbg-red {
        background-color: #f1948a !important;
    }

    .pbg-green {
        background-color: #47f10d !important;
    }
    .bg-yellow-light{
        background-color: #DAF7A6 !important;
    }
    .heighlightround{
        height: 10px;
    width: 10px;
    display: inline-block;
    float: left;
    border-radius: 50%;
    margin-top: 7px;
    margin-right: 5px;
    }
</style>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        product_table = $('#product_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [
                [3, 'asc']
            ],
            "ajax": {
                "url": "/products",
                "data": function(d) {
                    d.type = $('#product_list_filter_type').val();
                    d.category_id = $('#product_list_filter_category_id').val();
                    d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                    d.brand_id = $('#product_list_filter_brand_id').val();
                    d.unit_id = $('#product_list_filter_unit_id').val();
                    d.tax_id = $('#product_list_filter_tax_id').val();
                    d.active_state = $('#active_state').val();
                    d.not_for_selling = $('#not_for_selling').is(':checked');
                    d.location_id = $('#location_id').val();
                    if ($('#repair_model_id').length == 1) {
                        d.repair_model_id = $('#repair_model_id').val();
                    }
                    d = __datatable_ajax_callback(d);
                }
            },
            columnDefs: [{
                "targets": [0, 1, 2],
                "orderable": false,
                "searchable": false
            }],
            columns: [{
                    data: 'mass_delete'
                },
                {
                    data: 'image',
                    name: 'products.image'
                },
                {
                    data: 'action',
                    name: 'action'
                },
                {
                    data: 'product',
                    name: 'products.name'
                },
                {
                    data: 'product_locations',
                    name: 'product_locations'
                },
                @can('view_purchase_price') {
                    data: 'purchase_price',
                    name: 'max_purchase_price',
                    searchable: false
                },
                @endcan
                @can('access_default_selling_price') {
                    data: 'selling_price',
                    name: 'max_price',
                    searchable: false
                },
                @endcan {
                    data: 'current_stock',
                    searchable: false
                },
                {
                    data: 'type',
                    name: 'products.type'
                },
                {
                    data: 'category',
                    name: 'c1.name'
                },
                {
                    data: 'brand',
                    name: 'brands.name'
                },
                {
                    data: 'tax',
                    name: 'tax_rates.name',
                    searchable: false
                },
                {
                    data: 'sku',
                    name: 'products.sku'
                },
                {
                    data: 'product_custom_field1',
                    name: 'products.product_custom_field1'
                },
                {
                    data: 'product_custom_field2',
                    name: 'products.product_custom_field2'
                },
                {
                    data: 'product_custom_field3',
                    name: 'products.product_custom_field3'
                },
                {
                    data: 'product_custom_field4',
                    name: 'products.product_custom_field4'
                }

            ],
            createdRow: function(row, data, dataIndex) {
                if ($('input#is_rack_enabled').val() == 1) {
                    var target_col = 0;
                    @can('product.delete')
                    target_col = 1;
                    @endcan
                    $(row).find('td:eq(' + target_col + ') div').prepend('<i style="margin:auto;" class="fa fa-plus-circle text-success cursor-pointer no-print rack-details" title="' + LANG.details + '"></i>&nbsp;&nbsp;');
                }
                $(row).find('td:eq(0)').attr('class', 'selectable_td');
            },
            fnDrawCallback: function(oSettings) {
                __currency_convert_recursively($('#product_table'));
            },
        });
        // Array to track the ids of the details displayed rows
        var detailRows = [];

        $('#product_table tbody').on('click', 'tr i.rack-details', function() {
            var i = $(this);
            var tr = $(this).closest('tr');
            var row = product_table.row(tr);
            var idx = $.inArray(tr.attr('id'), detailRows);

            if (row.child.isShown()) {
                i.addClass('fa-plus-circle text-success');
                i.removeClass('fa-minus-circle text-danger');

                row.child.hide();

                // Remove from the 'open' array
                detailRows.splice(idx, 1);
            } else {
                i.removeClass('fa-plus-circle text-success');
                i.addClass('fa-minus-circle text-danger');

                row.child(get_product_details(row.data())).show();

                // Add to the 'open' array
                if (idx === -1) {
                    detailRows.push(tr.attr('id'));
                }
            }
        });

        $('table#product_table tbody').on('click', 'a.delete-product', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).attr('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                product_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $(document).on('click', '#delete-selected', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#selected_rows').val(selected_rows);
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $('form#mass_delete_form').submit();
                    }
                });
            } else {
                $('input#selected_rows').val('');
                swal('@lang("lang_v1.no_row_selected")');
            }
        });

        $(document).on('click', '#deactivate-selected', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#selected_products').val(selected_rows);
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var form = $('form#mass_deactivate_form')

                        var data = form.serialize();
                        $.ajax({
                            method: form.attr('method'),
                            url: form.attr('action'),
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    product_table.ajax.reload();
                                    form
                                        .find('#selected_products')
                                        .val('');
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            } else {
                $('input#selected_products').val('');
                swal('@lang("lang_v1.no_row_selected")');
            }
        })

        $(document).on('click', '#edit-selected', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#selected_products_for_edit').val(selected_rows);
                $('form#bulk_edit_form').submit();
            } else {
                $('input#selected_products').val('');
                swal('@lang("lang_v1.no_row_selected")');
            }
        })

        $('table#product_table tbody').on('click', 'a.activate-product', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            $.ajax({
                method: "get",
                url: href,
                dataType: "json",
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        product_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('change', '#product_list_filter_type, #product_list_filter_category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id, #active_state, #repair_model_id, #product_list_filter_sub_category_id',
            function() {
                if ($("#product_list_tab").hasClass('active')) {
                    product_table.ajax.reload();
                }

                if ($("#product_stock_report").hasClass('active')) {
                    stock_report_table.ajax.reload();
                }
                if ($("#product_profitibility_report").hasClass('active')) {
                    $('#product_profitibility_report_table').DataTable().ajax.reload();
                }
            });

        $(document).on('ifChanged', '#not_for_selling', function() {
            if ($("#product_list_tab").hasClass('active')) {
                product_table.ajax.reload();
            }

            if ($("#product_stock_report").hasClass('active')) {
                stock_report_table.ajax.reload();
            }
            if ($("#product_profitibility_report").hasClass('active')) {
                $('#product_profitibility_report_table').DataTable().ajax.reload();
            }
        });

        $('#product_location').select2({
            dropdownParent: $('#product_location').closest('.modal')
        });
    });

    $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal',
        function() {
            var div = $(this).find('#view_product_stock_details');
            if (div.length) {
                $.ajax({
                    url: "{{action('ReportController@getStockReport')}}" + '?for=view_product&product_id=' + div.data('product_id'),
                    dataType: 'html',
                    success: function(result) {
                        div.html(result);
                        __currency_convert_recursively(div);
                    },
                });
            }
            __currency_convert_recursively($(this));
        });
    var data_table_initailized = false;
    var profitibility_table_initailized = false;

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        $('#pdatefilter').hide();
        if ($(e.target).attr('href') == '#product_stock_report') {

            if (!data_table_initailized) {
                //Stock report table
                var stock_report_cols = [{
                        data: 'sku',
                        name: 'variations.sub_sku'
                    },
                    {
                        data: 'product',
                        name: 'p.name'
                    },
                    {
                        data: 'category_name',
                        name: 'c1.name'
                    },
                    {
                        data: 'sub_category_name',
                        name: 'c2.name'
                    },
                    {
                        data: 'unit_price',
                        name: 'variations.sell_price_inc_tax'
                    },
                    {
                        data: 'stock',
                        name: 'stock',
                        searchable: false
                    },
                    {
                        data: 'stock_price',
                        name: 'stock_price',
                        searchable: false
                    },
                    {
                        data: 'stock_value_by_sale_price',
                        name: 'stock_value_by_sale_price',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'potential_profit',
                        name: 'potential_profit',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'total_sold',
                        name: 'total_sold',
                        searchable: false
                    },
                    {
                        data: 'total_transfered',
                        name: 'total_transfered',
                        searchable: false
                    },
                    {
                        data: 'total_adjusted',
                        name: 'total_adjusted',
                        searchable: false
                    }
                ];
                if ($('th.current_stock_mfg').length) {
                    stock_report_cols.push({
                        data: 'total_mfg_stock',
                        name: 'total_mfg_stock',
                        searchable: false
                    });
                }

                stock_report_table = $('#stock_report_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/reports/stock-report',
                        data: function(d) {
                            d.location_id = $('#location_id').val();
                            d.category_id = $('#product_list_filter_category_id').val();
                            d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                            d.brand_id = $('#product_list_filter_brand_id').val();
                            d.unit_id = $('#product_list_filter_unit_id').val();
                            d.type = $('#product_list_filter_type').val();
                            d.active_state = $('#active_state').val();
                            d.not_for_selling = $('#not_for_selling').is(':checked');
                            if ($('#repair_model_id').length == 1) {
                                d.repair_model_id = $('#repair_model_id').val();
                            }
                        }
                    },
                    columns: stock_report_cols,
                    fnDrawCallback: function(oSettings) {
                        $('#footer_total_stock').html(__sum_stock($('#stock_report_table'), 'current_stock'));
                        $('#footer_total_sold').html(__sum_stock($('#stock_report_table'), 'total_sold'));
                        $('#footer_total_transfered').html(
                            __sum_stock($('#stock_report_table'), 'total_transfered')
                        );
                        $('#footer_total_adjusted').html(
                            __sum_stock($('#stock_report_table'), 'total_adjusted')
                        );
                        var total_stock_price = sum_table_col($('#stock_report_table'), 'total_stock_price');
                        var total_stock_value_by_sale_price = sum_table_col($('#stock_report_table'), 'stock_value_by_sale_price');
                        $('#footer_stock_value_by_sale_price').text(total_stock_value_by_sale_price);

                        var total_potential_profit = sum_table_col($('#stock_report_table'), 'potential_profit');
                        $('#footer_potential_profit').text(total_potential_profit);

                        $('#footer_total_stock_price').text(total_stock_price);
                        __currency_convert_recursively($('#stock_report_table'));
                    },
                });
                data_table_initailized = true;
            } else {
                stock_report_table.ajax.reload();
            }
        } else if ($(e.target).attr('href') == '#product_profitibility_report') {
            $('#pdatefilter').show();
            if (!profitibility_table_initailized) {
                //Stock report table
                console.log(profitibility_table_initailized);
                var product_profitibility_report_cols = [{
                        data: 'category_name',
                        name: 'c1.name'
                    }, {
                        data: 'sub_category_name',
                        name: 'c2.name'
                    },
                    {
                        data: 'sku',
                        name: 'products.sku'
                    },
                    {
                        data: 'products_name',
                        name: 'products_name'
                    },
                    {
                        data: 'sell_qty',
                        name: 'sell_qty'
                    },
                    {
                        data: 'unit_sale_price',
                        name: 'unit_sale_price'
                    },
                    {
                        data: 'totaltax',
                        name: 'totaltax',
                    },
                    {
                        data: 'total_sale_without_tax',
                        name: 'total_sale_without_tax'
                    },
                    {
                        data: 'total_sale_gross',
                        name: 'total_sale_gross'
                    },
                    {
                        data: 'p_sale_gross',
                        name: 'p_sale_gross'
                    },
                    {
                        data: 'unit_cost_inc_tax',
                        name: 'unit_cost_inc_tax'
                    },
                    {
                        data: 'avg_unit_cost_inc_tax',
                        name: 'avg_unit_cost_inc_tax'
                    },
                    {
                        data: 'total_avg_cost',
                        name: 'total_avg_cost'
                    },
                    {
                        data: 'gross_profit',
                        name: 'gross_profit'
                    },
                    {
                        data: 'p_gross_profit',
                        name: 'p_gross_profit'
                    },
                    {
                        data: 'p_gross_margin',
                        name: 'p_gross_margin'
                    },
                ];
                product_profitibility_report_table = $('#product_profitibility_report_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/reports/product-profitibility-report',
                        data: function(d) {
                            d.location_id = $('#location_id').val();
                            d.category_id = $('#product_list_filter_category_id').val();
                            d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                            d.brand_id = $('#product_list_filter_brand_id').val();
                            d.unit_id = $('#product_list_filter_unit_id').val();
                            d.type = $('#product_list_filter_type').val();
                            d.active_state = $('#active_state').val();
                            d.not_for_selling = $('#not_for_selling').is(':checked');
                            if ($('#repair_model_id').length == 1) {
                                d.repair_model_id = $('#repair_model_id').val();
                            }
                            if ($('#profit_loss_date_filter').length == 1) {
                                d.start_date = $('#profit_loss_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_loss_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            } else {
                                d.start_date = null;
                                d.end_date = null;
                            }
                        }
                    },
                    columns: product_profitibility_report_cols,
                    fnDrawCallback: function(oSettings) {
                        var total_tax = sum_table_col($('#product_profitibility_report_table'), 'total_tax');
                        $('#footer_total_tax').text(total_tax);
                        var total_sale_without_tax = sum_table_col($('#product_profitibility_report_table'), 'total_sale_without_tax');
                        $('#footer_total_sale_without_tax').text(total_sale_without_tax);
                        var total_sale_gross = sum_table_col($('#product_profitibility_report_table'), 'total_sale_gross');
                        $('#footer_total_sale_gross').text(total_sale_gross);
                        var total_avg_cost = sum_table_col($('#product_profitibility_report_table'), 'total_avg_cost');
                        $('#footer_total_avg_cost').text(total_avg_cost);
                        var avg_unit_cost_inc_tax = sum_table_col($('#product_profitibility_report_table'), 'avg_unit_cost_inc_tax');
                        $('#footer_avg_unit_cost_inc_tax').text(avg_unit_cost_inc_tax);
                        var gross_profit = sum_table_col($('#product_profitibility_report_table'), 'gross_profit');
                        $('#footer_gross_profit').text(gross_profit);

                        __currency_convert_recursively($('#product_profitibility_report_table'));

                    },
                });
                profitibility_table_initailized = true;
            } else {
                $('#product_profitibility_report_table').DataTable().ajax.reload();
            }
        } else {
            product_table.ajax.reload();
        }
    });

    function getSelectedRows() {
        var selected_rows = [];
        var i = 0;
        $('.row-select:checked').each(function() {
            selected_rows[i++] = $(this).val();
        });

        return selected_rows;
    }

    $(document).on('click', '.update_product_location', function(e) {
        e.preventDefault();
        var selected_rows = getSelectedRows();

        if (selected_rows.length > 0) {
            $('input#selected_products').val(selected_rows);
            var type = $(this).data('type');
            var modal = $('#edit_product_location_modal');
            if (type == 'add') {
                modal.find('.remove_from_location_title').addClass('hide');
                modal.find('.add_to_location_title').removeClass('hide');
            } else if (type == 'remove') {
                modal.find('.add_to_location_title').addClass('hide');
                modal.find('.remove_from_location_title').removeClass('hide');
            }

            modal.modal('show');
            modal.find('#product_location').select2({
                dropdownParent: modal
            });
            modal.find('#product_location').val('').change();
            modal.find('#update_type').val(type);
            modal.find('#products_to_update_location').val(selected_rows);
        } else {
            $('input#selected_products').val('');
            swal('@lang("lang_v1.no_row_selected")');
        }
    });

    $(document).on('submit', 'form#edit_product_location_form', function(e) {
        e.preventDefault();
        $(this)
            .find('button[type="submit"]')
            .attr('disabled', true);
        var data = $(this).serialize();

        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                    $('div#edit_product_location_modal').modal('hide');
                    toastr.success(result.msg);
                    product_table.ajax.reload();
                    $('form#edit_product_location_form')
                        .find('button[type="submit"]')
                        .attr('disabled', false);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('change', '#product_list_filter_category_id', function() {
        get_sub_categories();
    });
    // $(document).on('change', '#product_list_filter_sub_category_id', function() {
    //     product_table.ajax.reload();
    //     stock_report_table.ajax.reload();
    //     $('#product_profitibility_report_table').DataTable().ajax.reload();
    // });

    function get_sub_categories() {
        var cat = $('#product_list_filter_category_id').val();
        $.ajax({
            method: 'POST',
            url: '/products/get_sub_categories',
            dataType: 'html',
            data: {
                cat_id: cat
            },
            success: function(result) {
                if (result) {
                    $('#product_list_filter_sub_category_id').html(result);
                }
            },
        });
    }
    if ($('#profit_loss_date_filter').length == 1) {
        $('#profit_loss_date_filter').daterangepicker(dateRangeSettings, function(start_date, end_date) {
            $('#profit_loss_date_filter span').html(
                start_date.format(moment_date_format) + ' ~ ' + end_date.format(moment_date_format)
            );
            $('#product_profitibility_report_table').DataTable().ajax.reload();
        });
        $('#profit_loss_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#profit_loss_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
    }
    $('#profit_loss_location_filter').change(function() {
        console.log(start_date);
        console.log(end_date);
        $('#product_profitibility_report_table').DataTable().ajax.reload();
    });
    $('#product_profitibility_report_table_filter .form-control').change(function() {
        $('#product_profitibility_report_table').DataTable().ajax.reload();
    });
    $(document).on('click', '.getProductDetail', function(e) {
        e.preventDefault();
        $('.view_product_detail_model').html('');
        $('.view_product_detail_model').modal('show');
        console.log('dsfsf');
        $('.view_product_detail_model').html('<div class="loader"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
        var repair_model_id = null;
        if ($('#repair_model_id').length == 1) {
            repair_model_id = $('#repair_model_id').val();
        }
        var start_date = null;
        var end_date = null;
        if ($('#profit_loss_date_filter').length == 1) {
            start_date = $('#profit_loss_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('#profit_loss_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        $.ajax({
            url: "/reports/get-product-profit-details",
            method: 'POST',
            data: {
                product_id: $(this).attr("data-products_id"),
                location_id: $('#location_id').val(),
                category_id: $('#product_list_filter_category_id').val(),
                sub_category_id: $('#product_list_filter_sub_category_id').val(),
                brand_id: $('#product_list_filter_brand_id').val(),
                unit_id: $('#product_list_filter_unit_id').val(),
                type: $('#product_list_filter_type').val(),
                active_state: $('#active_state').val(),
                not_for_selling: $('#not_for_selling').is(':checked'),
                repair_model_id: repair_model_id,
                start_date: start_date,
                end_date: end_date,
            },
            success: function(data) {
                $('.view_product_detail_model').html(data.productdetails);
                __currency_convert_recursively($('.view_product_detail_model'));
                $('#product_profit_detail_report').DataTable({
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
                        var popup_footer_total_quantity = sum_table_col($('#product_profit_detail_report'), 'popup_total_quantity');
                        $('#popup_footer_total_quantity').text(popup_footer_total_quantity);
                        var popup_footer_total_tax = sum_table_col($('#product_profit_detail_report'), 'popup_total_tax');
                        $('#popup_footer_total_tax').text(popup_footer_total_tax);
                        var popup_footer_total_gross = sum_table_col($('#product_profit_detail_report'), 'popup_total_gross');
                        $('#popup_footer_total_gross').text(popup_footer_total_gross);
                        __currency_convert_recursively($('#product_profit_detail_report'));

                    },
                });
            }
        });


    });
</script>
@endsection