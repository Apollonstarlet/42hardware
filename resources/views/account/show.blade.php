@extends('layouts.app')
@section('title', __('account.account_book'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('account.account_book')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-sm-4 col-xs-6">
            <div class="box box-solid">
                <div class="box-body">
                    <table class="table">
                        <tr>
                            <th>@lang('account.account_name'): </th>
                            <td>{{$account->name}}</td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.account_type'):</th>
                            <td>@if(!empty($account->account_type->parent_account)) {{$account->account_type->parent_account->name}} - @endif {{$account->account_type->name ?? ''}}</td>
                        </tr>
                        <tr>
                            <th>@lang('account.account_number'):</th>
                            <td>{{$account->account_number}}</td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.balance'):</th>
                            <td><span id="account_balance"></span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-sm-8 col-xs-12">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title"> <i class="fa fa-filter" aria-hidden="true"></i> @lang('report.filters'):</h3>
                </div>
                <div class="box-body">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('transaction_date_range', __('report.date_range') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                {!! Form::text('transaction_date_range', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('report.date_range')]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('transaction_type', __('account.transaction_type') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fas fa-exchange-alt"></i></span>
                                {!! Form::select('transaction_type', ['' => __('messages.all'),'debit' => __('account.debit'), 'credit' => __('account.credit')], '', ['class' => 'form-control']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('user_id', __('report.user') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('business_id', __('business.business_location') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>

                                {!! Form::select('business_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('reference_no', __('Reference No') . ':') !!}
                            <input type="text" class="form-control" value="" id="reference_no" placeholder="Enter Reference No" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('customer_id', __('Customer') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="box">
                <div class="box-body">
                    @can('account.access')
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="account_book">
                            <thead>
                                <tr>
                                    <th>@lang( 'messages.date' )</th>
                                    <th>@lang( 'lang_v1.description' )</th>
                                    <th>@lang( 'lang_v1.added_by' )</th>
                                    <th>@lang('account.debit')</th>
                                    <th>@lang('account.credit')</th>
                                    <th>@lang( 'messages.action' )</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 text-center footer-total">
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th><span class="display_currency" id="footer_total_debit_amount" data-currency_symbol="true"></span></th>
                                    <th><span class="display_currency" id="footer_total_credit_amount" data-currency_symbol="true"></span></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade account_model" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        update_account_balance();

        dateRangeSettings.startDate = moment().subtract(6, 'days');
        dateRangeSettings.endDate = moment();
        $('#transaction_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#transaction_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));

                account_book.ajax.reload();
            }
        );

        // Account Book
        account_book = $('#account_book').DataTable({
            processing: true,
            //serverSide: true,
            ajax: {
                url: '{{action("AccountController@show",[$account->id])}}',
                data: function(d) {
                    var start = '';
                    var end = '';
                    if ($('#transaction_date_range').val()) {
                        start = $('input#transaction_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        end = $('input#transaction_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                    var transaction_type = $('select#transaction_type').val();
                    var user_id = $('#user_id').val();
                    var business_id = $('#business_id').val();
                    var supplier_id = $('#supplier_id').val();
                    var reference_no = $('#reference_no').val();
                    var customer_id = $('#customer_id').val();
                    d.start_date = start;
                    d.end_date = end;
                    d.type = transaction_type;
                    d.user_id = user_id;
                    d.business_id = business_id;
                    d.supplier_id = supplier_id;
                    d.reference_no = reference_no;
                    d.customer_id = customer_id;
                }
            },
            columns: [{
                    data: 'operation_date',
                    name: 'operation_date',
                },
                {
                    data: 'sub_type',
                    name: 'sub_type',
                },
                {
                    data: 'added_by',
                    name: 'added_by',
                },
                {
                    data: 'debit',
                    name: 'amount',
                },
                {
                    data: 'credit',
                    name: 'amount',
                },
                {
                    data: 'action',
                    name: 'action',
                }
            ],
            "fnDrawCallback": function(oSettings) {
                var credit_amount = sum_table_col($('#account_book'), 'credit_amount');
                console.log(credit_amount);
                $('#footer_total_credit_amount').text(credit_amount);
                var debit_amount = sum_table_col($('#account_book'), 'debit_amount');
                $('#footer_total_debit_amount').text(debit_amount);
                var accountbalance = credit_amount - debit_amount;
                $('span#account_balance').text(__currency_trans_from_en(accountbalance, true));
                __currency_convert_recursively($('#account_book'));
            }
        });

        $('#transaction_type').change(function() {
            account_book.ajax.reload();
        });
        $('#user_id').change(function() {
            account_book.ajax.reload();
        });
        $('#business_id').change(function() {
            account_book.ajax.reload();
        });
        $('#supplier_id').change(function() {
            account_book.ajax.reload();
        });
        $('#customer_id').change(function() {
            account_book.ajax.reload();
        });
        $('#reference_no').keyup(function() {
            account_book.ajax.reload();
        });
        $('#transaction_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#transaction_date_range').val('');
            account_book.ajax.reload();
        });

    });

    $(document).on('click', '.delete_account_transaction', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    url: href,
                    dataType: "json",
                    success: function(result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            account_book.ajax.reload();
                            update_account_balance();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });

    function update_account_balance(argument) {
        $('span#account_balance').html('<i class="fas fa-sync fa-spin"></i>');
        $.ajax({
            url: '{{action("AccountController@getAccountBalance", [$account->id])}}',
            dataType: "json",
            success: function(data) {
                $('span#account_balance').text(__currency_trans_from_en(data.balance, true));
            }
        });
    }
</script>
@endsection