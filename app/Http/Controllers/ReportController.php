<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;

use App\Charts\CommonChart;
use App\Contact;

use App\CustomerGroup;
use App\ExpenseCategory;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use Datatables;
use DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Shows profit\loss of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfitLoss(Request $request)
    {

        if (!auth()->user()->can('profit_loss_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');
            $reporttype = '1';
            if ($request->get('reporttype') != null) {
                $reporttype = $request->get('reporttype');
            }
            $displaytype = '1';
            if ($request->get('displaytype') != null) {
                $displaytype = $request->get('displaytype');
            }
            /*mayank */
            if (isset($start_date) || isset($end_date)) {
                $start_date = $start_date;
                $end_date = $end_date;
            } else {
                $start_date = \Carbon::now()->startOfMonth()->format('Y-m-d');
                $end_date = \Carbon::now()->endOfMonth()->format('Y-m-d');
            }
            $frommonth = date('m', strtotime($start_date));
            $tomonth = date('m', strtotime($end_date));
            $fromYear = date('Y', strtotime($start_date));
            $toYear = date('Y', strtotime($end_date));

            $monthArray = array();
            $year = array();
            $day = array();

            //dd($dates);
            $dates = array($start_date);
            while (end($dates) < $end_date) {
                $dates[] = date('Y-m-d', strtotime(end($dates) . ' +1 day'));
            }
            $i = date("Ym", strtotime($start_date));
            while ($i <= date("Ym", strtotime($end_date))) {
                $str = str_split($i, 4);
                $year[] = (int)$str[0];
                $monthArray[] = (int)$str[1];
                if (substr($i, 4, 2) == "12")
                    $i = (date("Y", strtotime($i . "01")) + 1) . "01";
                else
                    $i++;
            }

            $expanseReport = [];
            $salesReport = [];
            $salesLocationReport = [];
            $costOfGoods = [];
            $shippingReport = [];
            $taxReport = [];
            $data = [];
            $expense_categories = ExpenseCategory::orderBy('id', 'ASC')->pluck('id')->toArray();
            $expenseCategoriesName = ExpenseCategory::orderBy('id', 'ASC')->pluck('name')->toArray();
            array_push($expenseCategoriesName, "Other");
            $expenseCategoriesIds = ExpenseCategory::orderBy('id', 'ASC')->pluck('id')->toArray();
            if ($location_id) {
                $locationIds = BusinessLocation::orderBy('id', 'ASC')->where('id', $location_id)->pluck('id')->toArray();
                $locationNames = BusinessLocation::orderBy('id', 'ASC')->where('id', $location_id)->pluck('name')->toArray();
            } else {
                $locationIds = BusinessLocation::orderBy('id', 'ASC')->pluck('id')->toArray();
                $locationNames = BusinessLocation::orderBy('id', 'ASC')->pluck('name')->toArray();
            }

            if ($displaytype == '2') {
                foreach ($dates as $key => $day) {
                    /*Expanse Calcuation */
                    $getExpanses = Transaction::select('transactions.expense_category_id', DB::raw('IFNULL(sum(final_total),0) as total_expense'))->join('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')->where('transactions.business_id', $business_id)->where('type', 'expense')
                        ->whereDate('transaction_date', $day);
                    if ($location_id) {
                        $getExpanses = $getExpanses->where('location_id', $location_id);
                    }
                    $getExpanses = $getExpanses->groupBy('expense_category_id')->get()->toArray();
                    /*Other Expanse */
                    $getOtherExpanses = Transaction::select('transactions.expense_category_id', DB::raw('IFNULL(sum(final_total),0) as total_expense'))->where('transactions.business_id', $business_id)->where('type', 'expense')->where('transactions.expense_category_id', null)
                        ->whereDate('transaction_date', $day);
                    if ($location_id) {
                        $getOtherExpanses = $getOtherExpanses->where('location_id', $location_id);
                    }
                    $getOtherExpanses = $getOtherExpanses->groupBy('expense_category_id')->get()->first();
                    if ($getOtherExpanses) {
                        $otherExpanses = array('expense_category_id' => '100000', 'total_expense' => $getOtherExpanses->total_expense);
                    } else {
                        $otherExpanses = array('expense_category_id' => '100000', 'total_expense' => '0');
                    }
                    $expenseReportCatIds = Transaction::select('transactions.expense_category_id')->leftjoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')->where('transactions.business_id', $business_id)->where('type', 'expense')->whereDate('transaction_date', $day);
                    if ($location_id) {
                        $expenseReportCatIds = $expenseReportCatIds->where('location_id', $location_id);
                    }
                    $expenseReportCatIds = $expenseReportCatIds->groupBy('expense_category_id')->pluck('expense_category_id')->toArray();

                    $diff_result = array_diff($expense_categories, $expenseReportCatIds);
                    if (count($diff_result) > 0) {
                        foreach ($diff_result as $dr) {
                            $empty_details = array('expense_category_id' => $dr, 'total_expense' => '0');
                            array_push($getExpanses, $empty_details);
                        }
                    }
                    array_push($getExpanses, $otherExpanses);
                    $keys = array_column($getExpanses, 'expense_category_id');
                    array_multisort($keys, SORT_ASC, $getExpanses);
                    $expanseReport[] = $getExpanses;


                    /*Sales Calcuation */

                    $salesLocationReportData = [];
                    foreach ($locationIds as $l) {
                        $getSalesLData = $this->getProfitLossSalesData($business_id);
                        $getSalesLDataArray = $getSalesLData->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id)->whereDate('transactions.transaction_date', $day)->where('transactions.location_id', $l)->groupBy('transactions.id')->get()->toArray();
                        $getSalesL = array_sum(array_column($getSalesLDataArray, 'gross_amount'));
                        /*Due Sales Calcuation*/
                        $getDueSalesLData = $this->getProfitLossSalesData($business_id);
                        $getDueSalesLDataArray = $getDueSalesLData->where('transactions.business_id', $business_id)->whereDate('transaction_date', $day)->where('payment_status', '!=', 'paid')->where('transactions.location_id', $l)->groupBy('transactions.id')->get()->toArray();
                        $getDueSalesL = array_sum(array_column($getDueSalesLDataArray, 'gross_amount'));
                        if ($reporttype == '2') {
                            $getSalesL = $getSalesL + $getDueSalesL;
                        }
                        $SalesLDetails = array('location_id' => $l, 'gross_amount' => $getSalesL);
                        array_push($salesLocationReportData, $SalesLDetails);
                    }
                    $salesLocationReport[] = $salesLocationReportData;

                    $getSalesData = $this->getProfitLossSalesData($business_id);
                    $getSalesData = $getSalesData->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id)->whereDate('transactions.transaction_date', $day);
                    if ($location_id) {
                        $getSalesData = $getSalesData->where('transactions.location_id', $location_id);
                    }
                    $getSalesDataArray = $getSalesData->groupBy('transactions.id')->get()->toArray();
                    $getSales = 0;
                    $getSales = array_sum(array_column($getSalesDataArray, 'gross_amount'));

                    /*Due Sales Calcuation*/
                    $getDueSalesData = $this->getProfitLossSalesData($business_id);
                    $getDueSalesData = $getDueSalesData->where('transactions.business_id', $business_id)->whereDate('transaction_date', $day)->where('payment_status', '!=', 'paid');

                    if ($location_id) {
                        $getDueSalesData = $getDueSalesData->where('transactions.location_id', $location_id);
                    }
                    $getDueSalesDataArray = $getDueSalesData->groupBy('transactions.id')->get()->toArray();
                    $getDueSales = 0;
                    $getDueSales = array_sum(array_column($getDueSalesDataArray, 'gross_amount'));
                    if ($reporttype == '2') {
                        $getSales = $getSales + $getDueSales;
                    }
                    $salesReport[] = $getSales;

                    /*Cost Of Goods */
                    $costOfGoodsTotal = 0;
                    $costOfGoodsTotalSalesDue = 0;

                    $getSalescodData = $this->getProfitLossSalesData($business_id);
                    $getSalescodData = $getSalescodData->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id)->whereDate('transactions.transaction_date', $day);
                    if ($location_id) {
                        $getSalescodData = $getSalescodData->where('transactions.location_id', $location_id);
                    }
                    $getSalescodDataArray = $getSalescodData->groupBy('transaction_sell_lines.id')->get()->toArray();

                    foreach ($getSalescodDataArray as $cog) {
                        $total_avg_cost = 0;
                        $total_avg_cost = $cog['default_purchase_price'] * $cog['sell_qty'];
                        if ($cog['avg_unit_cost_inc_tax']) {
                            $total_avg_cost = $cog['avg_unit_cost_inc_tax'] * $cog['sell_qty'];
                        }
                        $costOfGoodsTotal += $total_avg_cost;
                    }

                    $getDueSalescodData = $this->getProfitLossSalesData($business_id);
                    $getDueSalescodData = $getDueSalescodData->where('transactions.business_id', $business_id)->whereDate('transaction_date', $day)->where('payment_status', '!=', 'paid');

                    if ($location_id) {
                        $getDueSalescodData = $getDueSalescodData->where('transactions.location_id', $location_id);
                    }
                    $getDueSalescodDataArray = $getDueSalescodData->groupBy('transaction_sell_lines.id')->get()->toArray();

                    foreach ($getDueSalescodDataArray as $dcog) {
                        $total_avg_cost = 0;
                        $total_avg_cost = $dcog['default_purchase_price'] * $dcog['sell_qty'];
                        if ($dcog['avg_unit_cost_inc_tax']) {
                            $total_avg_cost = $dcog['avg_unit_cost_inc_tax'] * $dcog['sell_qty'];
                        }
                        $costOfGoodsTotalSalesDue += $total_avg_cost;
                    }
                    if ($reporttype == '2') {
                        $costOfGoodsTotal = $costOfGoodsTotalSalesDue + $costOfGoodsTotal;
                    }
                    $costOfGoods[] = $costOfGoodsTotal;

                    /*Tax Calcuation */
                    $totalTax = 0;
                    $totalTax = array_sum(array_column($getSalesDataArray, 'total_tax'));
                    $totalDueSalesTax = array_sum(array_column($getDueSalesDataArray, 'total_tax'));
                    if ($reporttype == '2') {
                        $totalTax = $totalTax + $totalDueSalesTax;
                    }
                    $taxReport[] = $totalTax;

                    /*Shipping Calcuation */
                    $getShipping = Transaction::where('transactions.business_id', $business_id)->where('type', 'sell')->whereDate('transaction_date', $day);
                    if ($location_id) {
                        $getShipping = $getShipping->where('location_id', $location_id);
                    }
                    $getShipping = $getShipping->sum('shipping_charges');

                    $shippingReport[] = $getShipping;
                }

                $data['expanseReport'] = $expanseReport;
                $data['month'] = $dates;
                $data['startYear'] = $year[0];
                $data['endYear'] = $year[count($year) - 1];
                $data['expense_categories'] = $expense_categories;
                $data['expenseCategoriesName'] = $expenseCategoriesName;
                $data['expenseCategoriesIds'] = $expenseCategoriesIds;
                $data['salesReport'] = $salesReport;
                $data['shippingReport'] = $shippingReport;
                $data['taxReport'] = $taxReport;
                $data['costOfGoods'] = $costOfGoods;
                $data['reporttype'] = $reporttype;
                $data['displaytype'] = $displaytype;
                $data['salesLocationReport'] = $salesLocationReport;
                $data['locationIds'] = $locationIds;
                $data['locationNames'] = $locationNames;
                $data['locationwise'] = 0;
                $data['start_date'] = $start_date;
                $data['end_date'] = $end_date;
                //dd($data);
                return view('report.partials.profit_loss_details_day', compact('data'))->render();
            } else {
                foreach ($monthArray as $key => $month) {
                    /*Expanse Calcuation */
                    $getExpanses = Transaction::select('transactions.expense_category_id', DB::raw('IFNULL(sum(final_total),0) as total_expense'))->join('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')->where('transactions.business_id', $business_id)->where('type', 'expense')
                        ->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month);
                    if ($location_id) {
                        $getExpanses = $getExpanses->where('location_id', $location_id);
                    }
                    $getExpanses = $getExpanses->groupBy('expense_category_id')->get()->toArray();

                    /*Other Expanse */
                    $getOtherExpanses = Transaction::select('transactions.expense_category_id', DB::raw('IFNULL(sum(final_total),0) as total_expense'))->where('transactions.business_id', $business_id)->where('type', 'expense')->where('transactions.expense_category_id', null)
                        ->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month);
                    if ($location_id) {
                        $getOtherExpanses = $getOtherExpanses->where('location_id', $location_id);
                    }
                    $getOtherExpanses = $getOtherExpanses->groupBy('expense_category_id')->get()->first();
                    if ($getOtherExpanses) {
                        $otherExpanses = array('expense_category_id' => '100000', 'total_expense' => $getOtherExpanses->total_expense);
                    } else {
                        $otherExpanses = array('expense_category_id' => '100000', 'total_expense' => '0');
                    }

                    $expenseReportCatIds = Transaction::select('transactions.expense_category_id')->leftjoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')->where('transactions.business_id', $business_id)->where('type', 'expense')->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month);
                    if ($location_id) {
                        $expenseReportCatIds = $expenseReportCatIds->where('location_id', $location_id);
                    }
                    $expenseReportCatIds = $expenseReportCatIds->groupBy('expense_category_id')->pluck('expense_category_id')->toArray();

                    $diff_result = array_diff($expense_categories, $expenseReportCatIds);
                    if (count($diff_result) > 0) {
                        foreach ($diff_result as $dr) {
                            $empty_details = array('expense_category_id' => $dr, 'total_expense' => '0');
                            array_push($getExpanses, $empty_details);
                        }
                    }
                    array_push($getExpanses, $otherExpanses);

                    $keys = array_column($getExpanses, 'expense_category_id');
                    array_multisort($keys, SORT_ASC, $getExpanses);
                    $expanseReport[] = $getExpanses;
                    /*Sales Calcuation */

                    $salesLocationReportData = [];
                    foreach ($locationIds as $l) {
                        $getSalesLData = $this->getProfitLossSalesData($business_id);
                        $getSalesLDataArray = $getSalesLData->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id)->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month)->where('transactions.location_id', $l)->groupBy('transactions.id')->get()->toArray();
                        $getSalesL = array_sum(array_column($getSalesLDataArray, 'gross_amount'));
                        /*Due Sales Calcuation*/
                        $getDueSalesLData = $this->getProfitLossSalesData($business_id);
                        $getDueSalesLDataArray = $getDueSalesLData->where('transactions.business_id', $business_id)->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month)->where('payment_status', '!=', 'paid')->where('transactions.location_id', $l)->groupBy('transactions.id')->get()->toArray();
                        $getDueSalesL = array_sum(array_column($getDueSalesLDataArray, 'gross_amount'));
                        if ($reporttype == '2') {
                            $getSalesL = $getSalesL + $getDueSalesL;
                        }
                        $SalesLDetails = array('location_id' => $l, 'gross_amount' => $getSalesL);
                        array_push($salesLocationReportData, $SalesLDetails);
                    }
                    $salesLocationReport[] = $salesLocationReportData;


                    $getSalesData = $this->getProfitLossSalesData($business_id);
                    $getSalesData = $getSalesData->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id)->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month);
                    if ($location_id) {
                        $getSalesData = $getSalesData->where('transactions.location_id', $location_id);
                    }

                    $getSalesDataArray = $getSalesData->groupBy('transactions.id')->get()->toArray();
                    $getSales = 0;
                    $getSales = array_sum(array_column($getSalesDataArray, 'gross_amount'));

                    /*Due Sales Calcuation*/
                    $getDueSalesData = $this->getProfitLossSalesData($business_id);
                    $getDueSalesData = $getDueSalesData->where('transactions.business_id', $business_id)->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month)->where('payment_status', '!=', 'paid');
                    if ($location_id) {
                        $getDueSalesData = $getDueSalesData->where('transactions.location_id', $location_id);
                    }
                    $getDueSalesDataArray = $getDueSalesData->groupBy('transactions.id')->get()->toArray();
                    $getDueSales = 0;
                    $getDueSales = array_sum(array_column($getDueSalesDataArray, 'gross_amount'));

                    if ($reporttype == '2') {
                        $getSales = $getSales + $getDueSales;
                    }
                    $salesReport[] = $getSales;

                    /*Cost Of Goods */
                    $costOfGoodsTotal = 0;
                    $costOfGoodsTotalSalesDue = 0;

                    $getSalescodData = $this->getProfitLossSalesData($business_id);
                    $getSalescodData = $getSalescodData->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id)->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month);
                    if ($location_id) {
                        $getSalescodData = $getSalescodData->where('transactions.location_id', $location_id);
                    }

                    $getSalesDatacodArray = $getSalescodData->groupBy('transaction_sell_lines.id')->get()->toArray();

                    foreach ($getSalesDatacodArray as $cog) {
                        $total_avg_cost = 0;
                        $total_avg_cost = $cog['default_purchase_price'] * $cog['sell_qty'];
                        if ($cog['avg_unit_cost_inc_tax']) {
                            $total_avg_cost = $cog['avg_unit_cost_inc_tax'] * $cog['sell_qty'];
                        }
                        $costOfGoodsTotal += $total_avg_cost;
                    }

                    $getDueSalescodData = $this->getProfitLossSalesData($business_id);
                    $getDueSalescodData = $getDueSalescodData->where('transactions.business_id', $business_id)->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month)->where('payment_status', '!=', 'paid');
                    if ($location_id) {
                        $getDueSalescodData = $getDueSalescodData->where('transactions.location_id', $location_id);
                    }
                    $getDueSalescodDataArray = $getDueSalescodData->groupBy('transaction_sell_lines.id')->get()->toArray();

                    foreach ($getDueSalescodDataArray as $dcog) {
                        $total_avg_cost = 0;
                        $total_avg_cost = $dcog['default_purchase_price'] * $dcog['sell_qty'];
                        if ($dcog['avg_unit_cost_inc_tax']) {
                            $total_avg_cost = $dcog['avg_unit_cost_inc_tax'] * $dcog['sell_qty'];
                        }
                        $costOfGoodsTotalSalesDue += $total_avg_cost;
                    }
                    if ($reporttype == '2') {
                        $costOfGoodsTotal = $costOfGoodsTotalSalesDue + $costOfGoodsTotal;
                    }

                    $costOfGoods[] = $costOfGoodsTotal;

                    /*Tax Calcuation */
                    $totalTax = 0;
                    $totalTax = array_sum(array_column($getSalesDataArray, 'total_tax'));
                    $totalDueSalesTax = array_sum(array_column($getDueSalesDataArray, 'total_tax'));
                    if ($reporttype == '2') {
                        $totalTax = $totalTax + $totalDueSalesTax;
                    }
                    $taxReport[] = $totalTax;

                    /*Shipping Calcuation */
                    $getShipping = Transaction::where('transactions.business_id', $business_id)->where('type', 'sell')->whereYear('transaction_date', '=', $year[$key])->whereMonth('transaction_date', '=', $month);
                    if ($location_id) {
                        $getShipping = $getShipping->where('location_id', $location_id);
                    }
                    $getShipping = $getShipping->sum('shipping_charges');

                    $shippingReport[] = $getShipping;
                }
                $data['expanseReport'] = $expanseReport;
                $data['month'] = $monthArray;
                $data['year'] = $year;
                $data['startYear'] = $year[0];
                $data['endYear'] = $year[count($year) - 1];
                $data['expense_categories'] = $expense_categories;
                $data['expenseCategoriesName'] = $expenseCategoriesName;
                $data['expenseCategoriesIds'] = $expenseCategoriesIds;
                $data['salesReport'] = $salesReport;
                $data['shippingReport'] = $shippingReport;
                $data['taxReport'] = $taxReport;
                $data['costOfGoods'] = $costOfGoods;
                $data['reporttype'] = $reporttype;
                $data['displaytype'] = $displaytype;
                $data['salesLocationReport'] = $salesLocationReport;
                $data['locationIds'] = $locationIds;
                $data['locationNames'] = $locationNames;
                $data['locationwise'] = 0;
                $data['start_date'] = $start_date;
                $data['end_date'] = $end_date;
                //dd($data);
                return view('report.partials.profit_loss_details', compact('data'))->render();
            }
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('report.profit_loss', compact('business_locations'));
    }
    public function getProfitLossSalesData($business_id)
    {
        $getSalesData = Product::Join('transaction_sell_lines', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->Join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->Join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftjoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('products.type', '!=', 'modifier')
            ->select(
                //DB::raw('IFNULL(round(sum((transaction_sell_lines.unit_price_before_discount + transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity)),0) as gross_amount'),
                'transactions.final_total as gross_amount',
                DB::raw('IFNULL(sum((transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity),0) as total_tax'),
                DB::raw('IFNULL(sum(transaction_sell_lines.quantity),0) as sell_qty'),
                'v.default_purchase_price as default_purchase_price',
                DB::raw("(SELECT ROUND(AVG(purchase_price),2) AS avg_unit_cost_inc_tax FROM `purchase_lines` WHERE products.`business_id`=$business_id AND purchase_lines.`product_id`=products.id GROUP BY products.id) as avg_unit_cost_inc_tax"),
                'v.sell_price_inc_tax as sell_price_inc_tax',
                'v.default_sell_price as default_sell_price',
                'business_locations.id',
                'transactions.id'
            );
        return $getSalesData;
    }
    

    /**
     * Shows product report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchaseSell(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start_date, $end_date, $location_id);

            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $transaction_types = [
                'purchase_return', 'sell_return'
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];
            $total_sell_return_inc_tax = $transaction_totals['total_sell_return_inc_tax'];

            $difference = [
                'total' => $sell_details['total_sell_inc_tax'] + $total_sell_return_inc_tax - $purchase_details['total_purchase_inc_tax'] - $total_purchase_return_inc_tax,
                'due' => $sell_details['invoice_due'] - $purchase_details['purchase_due']
            ];

            return [
                'purchase' => $purchase_details,
                'sell' => $sell_details,
                'total_purchase_return' => $total_purchase_return_inc_tax,
                'total_sell_return' => $total_sell_return_inc_tax,
                'difference' => $difference
            ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.purchase_sell')
            ->with(compact('business_locations'));
    }

    /**
     * Shows report for Supplier
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerSuppliers(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $contacts = Contact::where('contacts.business_id', $business_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->active()
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id',
                    'contacts.type as contact_type'
                );
            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('customer_group_id'))) {
                $contacts->where('contacts.customer_group_id', $request->input('customer_group_id'));
            }

            if (!empty($request->input('contact_type'))) {
                $contacts->whereIn('contacts.type', [$request->input('contact_type'), 'both']);
            }

            return Datatables::of($contacts)
                ->editColumn('name', function ($row) {
                    $name = $row->name;
                    if (!empty($row->supplier_business_name)) {
                        $name .= ', ' . $row->supplier_business_name;
                    }
                    return '<a href="' . action('ContactController@show', [$row->id]) . '" target="_blank" class="no-print">' .
                        $name .
                        '</a><span class="print_section">' . $name . '</span>';
                })
                ->editColumn('total_purchase', function ($row) {
                    return '<span class="display_currency total_purchase" data-orig-value="' . $row->total_purchase . '" data-currency_symbol = true>' . $row->total_purchase . '</span>';
                })
                ->editColumn('total_purchase_return', function ($row) {
                    return '<span class="display_currency total_purchase_return" data-orig-value="' . $row->total_purchase_return . '" data-currency_symbol = true>' . $row->total_purchase_return . '</span>';
                })
                ->editColumn('total_sell_return', function ($row) {
                    return '<span class="display_currency total_sell_return" data-orig-value="' . $row->total_sell_return . '" data-currency_symbol = true>' . $row->total_sell_return . '</span>';
                })
                ->editColumn('total_invoice', function ($row) {
                    return '<span class="display_currency total_invoice" data-orig-value="' . $row->total_invoice . '" data-currency_symbol = true>' . $row->total_invoice . '</span>';
                })
                ->addColumn('due', function ($row) {
                    $due = ($row->total_invoice - $row->invoice_received - $row->total_sell_return + $row->sell_return_paid) - ($row->total_purchase - $row->total_purchase_return + $row->purchase_return_received - $row->purchase_paid);

                    if ($row->contact_type == 'supplier') {
                        $due -= $row->opening_balance - $row->opening_balance_paid;
                    } else {
                        $due += $row->opening_balance - $row->opening_balance_paid;
                    }

                    return '<span class="display_currency total_due" data-orig-value="' . $due . '" data-currency_symbol=true data-highlight=true>' . $due . '</span>';
                })
                ->addColumn(
                    'opening_balance_due',
                    '<span class="display_currency opening_balance_due" data-currency_symbol=true data-orig-value="{{$opening_balance - $opening_balance_paid}}">{{$opening_balance - $opening_balance_paid}}</span>'
                )
                ->removeColumn('supplier_business_name')
                ->removeColumn('invoice_received')
                ->removeColumn('purchase_paid')
                ->removeColumn('id')
                ->rawColumns(['total_purchase', 'total_invoice', 'due', 'name', 'total_purchase_return', 'total_sell_return', 'opening_balance_due'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $types = [
            '' => __('lang_v1.all'),
            'customer' => __('report.customer'),
            'supplier' => __('report.supplier')
        ];
        $labels = [];
        $values = [];
        $chart = new CommonChart;
        $chart->labels($labels)
            ->dataset(__('report.total_unit_sold'), 'column', $values);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $suppliers = Contact::suppliersDropdown($business_id);
        return view('report.contact')
            ->with(compact('chart', 'customer_group', 'types', 'business_locations', 'customers', 'suppliers'));
    }

    /**
     * Shows product stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        if ($this->moduleUtil->isModuleInstalled('Manufacturing') && (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            $show_manufacturing_data = true;
        } else {
            $show_manufacturing_data = false;
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('business_locations as l', 'vld.location_id', '=', 'l.id')
                ->leftJoin('categories as c1', 'p.category_id', '=', 'c1.id')
                ->leftJoin('categories as c2', 'p.sub_category_id', '=', 'c2.id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";

                //If filter by location then hide products not available in that location
                $query->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                    ->where(function ($q) use ($location_id) {
                        $q->where('pl.location_id', $location_id);
                    });
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->join('mfg_recipes as mr', 'mr.variation_id', '=', 'variations.id');
            }

            $active_state = request()->get('active_state', null);
            if ($active_state == 'active') {
                $query->where('p.is_inactive', 0);
            }
            if ($active_state == 'inactive') {
                $query->where('p.is_inactive', 1);
            }
            $not_for_selling = request()->get('not_for_selling', null);
            if ($not_for_selling == 'true') {
                $query->where('p.not_for_selling', 1);
            }

            if (!empty(request()->get('repair_model_id'))) {
                $query->where('p.repair_model_id', request()->get('repair_model_id'));
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            $pl_query_string = $this->productUtil->get_pl_quantity_sum_string('pl');

            if (request()->input('for') == 'view_product' && !empty(request()->input('product_id'))) {
                $location_filter = 'AND transactions.location_id=l.id';
            }

            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("(SELECT SUM( COALESCE(pl.quantity - ($pl_query_string), 0) * purchase_price_inc_tax) FROM transactions 
                        JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id
                        WHERE transactions.status='received' $location_filter 
                        AND (pl.variation_id=variations.id)) as stock_price"),
                DB::raw("(SELECT price_inc_tax from variation_group_prices as vgp WHERE vgp.variation_id=variations.id AND vgp.price_group_id=l.selling_price_group_id) as group_price"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.name as product',
                'p.type',
                'p.id as product_id',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name',
                'l.name as location_name',
                'l.id as location_id',
                'variations.id as variation_id',
                'c1.name as category_name',
                'c2.name as sub_category_name',
            );

            if ($show_manufacturing_data) {
                $pl_query_string = $this->productUtil->get_pl_quantity_sum_string('PL');
                $products->addSelect(
                    DB::raw("(SELECT COALESCE(SUM(PL.quantity - ($pl_query_string)), 0) FROM transactions 
                        JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='production_purchase' $location_filter 
                        AND (PL.variation_id=variations.id)) as total_mfg_stock")
                );
            }
            $products->groupBy('variations.id');

            //To show stock details on view product modal
            if (request()->input('for') == 'view_product' && !empty(request()->input('product_id'))) {
                $products->where('p.id', request()->input('product_id'))
                    ->groupBy('l.id');

                $product_stock_details = $products->get();

                return view('product.partials.product_stock_details')->with(compact('product_stock_details'));
            }
            $datatable =  Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float)$stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('category_name', function ($row) {
                    $name = $row->category_name;
                    return $name;
                })
                ->editColumn('sub_category_name', function ($row) {
                    $name = $row->sub_category_name;
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float)$row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float)$row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->editColumn('stock_price', function ($row) {
                    $html = '<span class="display_currency total_stock_price" data-currency_symbol=true data-orig-value="'
                        . $row->stock_price . '">'
                        . $row->stock_price . '</span>';

                    return $html;
                })
                ->editColumn('stock_value_by_sale_price', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    $unit_selling_price = (float)$row->group_price > 0 ? $row->group_price : $row->unit_price;
                    $stock_price = $stock * $unit_selling_price;
                    return  '<span class="stock_value_by_sale_price display_currency" data-orig-value="' . (float)$stock_price . '" data-currency_symbol=true > ' . (float)$stock_price . '</span>';
                })
                ->addColumn('potential_profit', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    $unit_selling_price = (float)$row->group_price > 0 ? $row->group_price : $row->unit_price;
                    $stock_price_by_sp = $stock * $unit_selling_price;
                    $potential_profit = $stock_price_by_sp - $row->stock_price;

                    return  '<span class="potential_profit display_currency" data-orig-value="' . (float)$potential_profit . '" data-currency_symbol=true > ' . (float)$potential_profit . '</span>';
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id');

            $raw_columns  = [
                'unit_price', 'total_transfered', 'total_sold',
                'total_adjusted', 'stock', 'stock_price', 'stock_value_by_sale_price', 'potential_profit'
            ];

            if ($show_manufacturing_data) {
                $datatable->editColumn('total_mfg_stock', function ($row) {
                    $total_mfg_stock = 0;
                    if ($row->total_mfg_stock) {
                        $total_mfg_stock =  (float)$row->total_mfg_stock;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_mfg_stock" data-currency_symbol=false  data-orig-value="' . $total_mfg_stock . '" data-unit="' . $row->unit . '" >' . $total_mfg_stock . '</span> ' . $row->unit;
                });
                $raw_columns[] = 'total_mfg_stock';
            }

            return $datatable->rawColumns($raw_columns)->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations', 'show_manufacturing_data'));
    }

    /**
     * Shows product stock details
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockDetails(Request $request)
    {
        //Return the details in ajax call
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');
            $product_id = $request->input('product_id');
            $query = Product::leftjoin('units as u', 'products.unit_id', '=', 'u.id')
                ->join('variations as v', 'products.id', '=', 'v.product_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->leftjoin('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
                ->where('products.business_id', $business_id)
                ->where('products.id', $product_id)
                ->whereNull('v.deleted_at');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            $product_details =  $query->select(
                'products.name as product',
                'u.short_name as unit',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku as sub_sku',
                'v.sell_price_inc_tax',
                DB::raw("SUM(vld.qty_available) as stock"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity - TSL.quantity_returned, -1* TPL.quantity) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                        LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND (TSL.variation_id=v.id OR TPL.variation_id=v.id)) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=v.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=v.id)) as total_adjusted")
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.variation_id=v.id) as total_sold")
            )
                ->groupBy('v.id')
                ->get();

            return view('report.stock_details')
                ->with(compact('product_details'));
        }
    }

    /**
     * Shows tax report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxReport(Request $request)
    {
        if (!auth()->user()->can('tax_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            $input_tax_details = $this->transactionUtil->getInputTax($business_id, $start_date, $end_date, $location_id);

            $input_tax = view('report.partials.tax_details')->with(['tax_details' => $input_tax_details])->render();

            $output_tax_details = $this->transactionUtil->getOutputTax($business_id, $start_date, $end_date, $location_id);

            $expense_tax_details = $this->transactionUtil->getExpenseTax($business_id, $start_date, $end_date, $location_id);

            $output_tax = view('report.partials.tax_details')->with(['tax_details' => $output_tax_details])->render();

            $expense_tax = view('report.partials.tax_details')->with(['tax_details' => $expense_tax_details])->render();

            return [
                'input_tax' => $input_tax,
                'output_tax' => $output_tax,
                'expense_tax' => $expense_tax,
                'tax_diff' => $output_tax_details['total_tax'] - $input_tax_details['total_tax'] - $expense_tax_details['total_tax']
            ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.tax_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows trending products
     *
     * @return \Illuminate\Http\Response
     */
    public function getTrendingProducts(Request $request)
    {
        if (!auth()->user()->can('trending_product_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $filters = request()->only(['category', 'sub_category', 'brand', 'unit', 'limit', 'location_id', 'product_type']);

        $date_range = request()->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        }

        $products = $this->productUtil->getTrendingProducts($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($products as $product) {
            $values[] = (float) $product->total_unit_sold;
            $labels[] = $product->product . ' (' . $product->unit . ')';
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->dataset(__('report.total_unit_sold'), 'column', $values);

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.trending_products')
            ->with(compact('chart', 'categories', 'brands', 'units', 'business_locations'));
    }

    public function getTrendingProductsAjax()
    {
        $business_id = request()->session()->get('user.business_id');
    }
    /**
     * Shows expense report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getExpenseReport(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['category', 'location_id']);

        $date_range = $request->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        } else {
            $filters['start_date'] = \Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = \Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $expenses = $this->transactionUtil->getExpenseReport($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($expenses as $expense) {
            $values[] = (float) $expense->total_expense;
            $labels[] = !empty($expense->category) ? $expense->category : __('report.others');
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->title(__('report.expense_report'))
            ->dataset(__('report.total_expense'), 'column', $values);

        $categories = ExpenseCategory::where('business_id', $business_id)
            ->pluck('name', 'id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.expense_report')
            ->with(compact('chart', 'categories', 'business_locations', 'expenses'));
    }

    /**
     * Shows stock adjustment report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockAdjustmentReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query =  Transaction::where('business_id', $business_id)
                ->where('type', 'stock_adjustment');

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('location_id', $permitted_locations);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }
            $location_id = $request->get('location_id');
            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $stock_adjustment_details = $query->select(
                DB::raw("SUM(final_total) as total_amount"),
                DB::raw("SUM(total_amount_recovered) as total_recovered"),
                DB::raw("SUM(IF(adjustment_type = 'normal', final_total, 0)) as total_normal"),
                DB::raw("SUM(IF(adjustment_type = 'abnormal', final_total, 0)) as total_abnormal")
            )->first();
            return $stock_adjustment_details;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_adjustment_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows register report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegisterReport(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'users as u',
                'u.id',
                '=',
                'cash_registers.user_id'
            )
                ->leftJoin(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )
                ->where('cash_registers.business_id', $business_id)
                ->select(
                    'cash_registers.*',
                    DB::raw(
                        "CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) as user_name"
                    ),
                    'bl.name as location_name'
                );

            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }
            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $registers->whereDate('cash_registers.created_at', '>=', $start_date)
                    ->whereDate('cash_registers.created_at', '<=', $end_date);
            }
            return Datatables::of($registers)
                ->editColumn('total_card_slips', function ($row) {
                    if ($row->status == 'close') {
                        return $row->total_card_slips;
                    } else {
                        return '';
                    }
                })
                ->editColumn('total_cheques', function ($row) {
                    if ($row->status == 'close') {
                        return $row->total_cheques;
                    } else {
                        return '';
                    }
                })
                ->editColumn('closed_at', function ($row) {
                    if ($row->status == 'close') {
                        return $this->productUtil->format_date($row->closed_at, true);
                    } else {
                        return '';
                    }
                })
                ->editColumn('created_at', function ($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                ->editColumn('closing_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency" data-currency_symbol="true">' .
                            $row->closing_amount . '</span>';
                    } else {
                        return '';
                    }
                })
                ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$id])}}" class="btn btn-xs btn-info btn-modal" 
                    data-container=".view_register"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</button>')
                ->filterColumn('user_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['action', 'user_name', 'closing_amount'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);

        return view('report.register_report')
            ->with(compact('users'));
    }

    /**
     * Shows sales representative report
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesRepresentativeReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $users = User::allUsersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.sales_representative')
            ->with(compact('users', 'business_locations'));
    }

    /**
     * Shows sales representative total expense
     *
     * @return json
     */
    public function getSalesRepresentativeTotalExpense(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            $filters = $request->only(['expense_for', 'location_id', 'start_date', 'end_date']);

            $total_expense = $this->transactionUtil->getExpenseReport($business_id, $filters, 'total');

            return $total_expense;
        }
    }

    /**
     * Shows sales representative total sales
     *
     * @return json
     */
    public function getSalesRepresentativeTotalSell(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $created_by = $request->get('created_by');

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start_date, $end_date, $location_id, $created_by);

            //Get Sell Return details
            $transaction_types = [
                'sell_return'
            ];
            $sell_return_details = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id,
                $created_by
            );

            $total_sell_return = !empty($sell_return_details['total_sell_return_exc_tax']) ? $sell_return_details['total_sell_return_exc_tax'] : 0;
            $total_sell = $sell_details['total_sell_exc_tax'] - $total_sell_return;

            return [
                'total_sell_exc_tax' => $sell_details['total_sell_exc_tax'],
                'total_sell_return_exc_tax' => $total_sell_return,
                'total_sell' => $total_sell
            ];
        }
    }

    /**
     * Shows sales representative total commission
     *
     * @return json
     */
    public function getSalesRepresentativeTotalCommission(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $commission_agent = $request->get('commission_agent');

            $sell_details = $this->transactionUtil->getTotalSellCommission($business_id, $start_date, $end_date, $location_id, $commission_agent);

            //Get Commision
            $commission_percentage = User::find($commission_agent)->cmmsn_percent;
            $total_commission = $commission_percentage * $sell_details['total_sales_with_commission'] / 100;

            return [
                'total_sales_with_commission' =>
                $sell_details['total_sales_with_commission'],
                'total_commission' => $total_commission,
                'commission_percentage' => $commission_percentage
            ];
        }
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //TODO:: Need to display reference number and edit expiry date button

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = PurchaseLine::leftjoin(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                ->leftjoin(
                    'products as p',
                    'purchase_lines.product_id',
                    '=',
                    'p.id'
                )
                ->leftjoin(
                    'variations as v',
                    'purchase_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->leftjoin(
                    'product_variations as pv',
                    'v.product_variation_id',
                    '=',
                    'pv.id'
                )
                ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                //->whereNotNull('p.expiry_period')
                //->whereNotNull('p.expiry_period_type')
                //->whereNotNull('exp_date')
                ->where('p.enable_stock', 1);
            // ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + quantity_adjusted + quantity_returned');

            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id)
                    //If filter by location then hide products not available in that location
                    ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                    ->where(function ($q) use ($location_id) {
                        $q->where('pl.location_id', $location_id);
                    });
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            if (!empty($request->input('exp_date_filter'))) {
                $query->whereDate('exp_date', '<=', $request->input('exp_date_filter'));
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('t.type', 'production_purchase');
            }

            $report = $query->select(
                'p.name as product',
                'p.sku',
                'p.type as product_type',
                'v.name as variation',
                'pv.name as product_variation',
                'l.name as location',
                'mfg_date',
                'exp_date',
                'u.short_name as unit',
                DB::raw("SUM(COALESCE(quantity, 0) - COALESCE(quantity_sold, 0) - COALESCE(quantity_adjusted, 0) - COALESCE(quantity_returned, 0)) as stock_left"),
                't.ref_no',
                't.id as transaction_id',
                'purchase_lines.id as purchase_line_id',
                'purchase_lines.lot_number'
            )
                ->having('stock_left', '>', 0)
                ->groupBy('purchase_lines.exp_date')
                ->groupBy('purchase_lines.lot_number');

            return Datatables::of($report)
                ->editColumn('name', function ($row) {
                    if ($row->product_type == 'variable') {
                        return $row->product . ' - ' .
                            $row->product_variation . ' - ' . $row->variation;
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('mfg_date', function ($row) {
                    if (!empty($row->mfg_date)) {
                        return $this->productUtil->format_date($row->mfg_date);
                    } else {
                        return '--';
                    }
                })
                // ->editColumn('exp_date', function ($row) {
                //     if (!empty($row->exp_date)) {
                //         $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                //         $carbon_now = \Carbon::now();
                //         if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                //             return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                //         } else {
                //             return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                //         }
                //     } else {
                //         return '--';
                //     }
                // })
                ->editColumn('ref_no', function ($row) {
                    return '<button type="button" data-href="' . action('PurchaseController@show', [$row->transaction_id])
                        . '" class="btn btn-link btn-modal" data-container=".view_modal"  >' . $row->ref_no . '</button>';
                })
                ->editColumn('stock_left', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency stock_left" data-currency_symbol=false data-orig-value="' . $row->stock_left . '" data-unit="' . $row->unit . '" >' . $row->stock_left . '</span> ' . $row->unit;
                })
                ->addColumn('edit', function ($row) {
                    $html =  '<button type="button" class="btn btn-primary btn-xs stock_expiry_edit_btn" data-transaction_id="' . $row->transaction_id . '" data-purchase_line_id="' . $row->purchase_line_id . '"> <i class="fa fa-edit"></i> ' . __("messages.edit") .
                        '</button>';

                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) < 0) {
                            $html .=  ' <button type="button" class="btn btn-warning btn-xs remove_from_stock_btn" data-href="' . action('StockAdjustmentController@removeExpiredStock', [$row->purchase_line_id]) . '"> <i class="fa fa-trash"></i> ' . __("lang_v1.remove_from_stock") .
                                '</button>';
                        }
                    }

                    return $html;
                })
                ->rawColumns(['exp_date', 'ref_no', 'edit', 'stock_left'])
                ->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $view_stock_filter = [
            \Carbon::now()->subDay()->format('Y-m-d') => __('report.expired'),
            \Carbon::now()->addWeek()->format('Y-m-d') => __('report.expiring_in_1_week'),
            \Carbon::now()->addDays(15)->format('Y-m-d') => __('report.expiring_in_15_days'),
            \Carbon::now()->addMonth()->format('Y-m-d') => __('report.expiring_in_1_month'),
            \Carbon::now()->addMonths(3)->format('Y-m-d') => __('report.expiring_in_3_months'),
            \Carbon::now()->addMonths(6)->format('Y-m-d') => __('report.expiring_in_6_months'),
            \Carbon::now()->addYear()->format('Y-m-d') => __('report.expiring_in_1_year')
        ];

        return view('report.stock_expiry_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations', 'view_stock_filter'));
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReportEditModal(Request $request, $purchase_line_id)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $purchase_line = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'products as p',
                    'purchase_lines.product_id',
                    '=',
                    'p.id'
                )
                ->where('purchase_lines.id', $purchase_line_id)
                ->where('t.business_id', $business_id)
                ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                ->first();

            if (!empty($purchase_line)) {
                if (!empty($purchase_line->exp_date)) {
                    $purchase_line->exp_date = date('m/d/Y', strtotime($purchase_line->exp_date));
                }
            }

            return view('report.partials.stock_expiry_edit_modal')
                ->with(compact('purchase_line'));
        }
    }

    /**
     * Update product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Return the details in ajax call
            if ($request->ajax()) {
                DB::beginTransaction();

                $input = $request->only(['purchase_line_id', 'exp_date']);

                $purchase_line = PurchaseLine::join(
                    'transactions as t',
                    'purchase_lines.transaction_id',
                    '=',
                    't.id'
                )
                    ->join(
                        'products as p',
                        'purchase_lines.product_id',
                        '=',
                        'p.id'
                    )
                    ->where('purchase_lines.id', $input['purchase_line_id'])
                    ->where('t.business_id', $business_id)
                    ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                    ->first();

                if (!empty($purchase_line) && !empty($input['exp_date'])) {
                    $purchase_line->exp_date = $this->productUtil->uf_date($input['exp_date']);
                    $purchase_line->save();
                }

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.updated_succesfully')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerGroup(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = Transaction::leftjoin('customer_groups AS CG', 'transactions.customer_group_id', '=', 'CG.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->groupBy('transactions.customer_group_id')
                ->select(DB::raw("SUM(final_total) as total_sell"), 'CG.name');

            $group_id = $request->get('customer_group_id', null);
            if (!empty($group_id)) {
                $query->where('transactions.customer_group_id', $group_id);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }


            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.customer_group')
            ->with(compact('customer_group', 'business_locations'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductPurchaseReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'purchase_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'purchase')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as supplier',
                    't.id as transaction_id',
                    't.ref_no',
                    't.transaction_date as transaction_date',
                    'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                    DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as purchase_qty'),
                    'purchase_lines.quantity_adjusted',
                    'u.short_name as unit',
                    DB::raw('((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted) * purchase_lines.purchase_price_inc_tax) as subtotal')
                )
                ->groupBy('purchase_lines.id');
            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('t.contact_id', $supplier_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('ref_no', function ($row) {
                    return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                })
                ->editColumn('purchase_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency purchase_qty" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_qty . '</span> ' . $row->unit;
                })
                ->editColumn('quantity_adjusted', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float)$row->quantity_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . round($row->subtotal) . '">' . round($row->subtotal) . '</span>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_purchase_price . '</span>';
                })
                ->rawColumns(['ref_no', 'unit_purchase_price', 'subtotal', 'purchase_qty', 'quantity_adjusted'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id);

        return view('report.product_purchase_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('tax_rates', 'p.tax', '=', 'tax_rates.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as customer',
                    'c.contact_id',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                    'transaction_sell_lines.line_discount_type as discount_type',
                    'transaction_sell_lines.line_discount_amount as discount_amount',
                    'transaction_sell_lines.item_tax',
                    'tax_rates.name as tax',
                    'tax_rates.amount as tax_amount',
                    'u.short_name as unit',
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->groupBy('transaction_sell_lines.id');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('invoice_no', function ($row) {
                    return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    $tax = 16;
                    if($row->tax_amount){
                        $tax = $row->tax_amount;
                    }
                    $unit_sale_price = $row->unit_sale_price + $row->unit_sale_price*$tax/100;
                    return '<span class="display_currency '.$row->tax.'" data-currency_symbol = true>' . round($unit_sale_price) . '</span>';
                })
                ->editColumn('sell_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->sell_qty . '</span> ' . $row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    $tax = 16;
                    if($row->tax_amount){
                        $tax = $row->tax_amount;
                    }
                    $subtotal = $row->subtotal + $row->subtotal*$tax/100;
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . round($subtotal) . '">' . round($subtotal) . '</span>';
                })
                ->editColumn('unit_price', function ($row) {
                    $tax = 16;
                    if($row->tax_amount){
                        $tax = $row->tax_amount;
                    }
                    $unit_price = $row->unit_price + $row->unit_price*$tax/100;
                    return '<span class="display_currency" data-currency_symbol = true>' . round($unit_price) . '</span>';
                })
                ->editColumn('discount_amount', '
                    @if($discount_type == "percentage")
                        {{@number_format($discount_amount)}} %
                    @elseif($discount_type == "fixed")
                        {{@number_format($discount_amount)}}
                    @endif
                    ')
                ->editColumn('tax', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' .
                        $row->item_tax .
                        '</span>' . '<br>' . '<span class="tax" data-orig-value="' . (float)$row->item_tax . '" data-unit="' . $row->tax . '"><small>(' . $row->tax . ')</small></span>';
                })
                ->rawColumns(['invoice_no', 'unit_sale_price', 'subtotal', 'sell_qty', 'discount_amount', 'unit_price', 'tax'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        return view('report.product_sell_report')
            ->with(compact('business_locations', 'customers'));
    }

    /**
     * Shows product purchase report with purchase details
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReportWithPurchase(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'transaction_sell_lines_purchase_lines as tspl',
                    'transaction_sell_lines.id',
                    '=',
                    'tspl.sell_line_id'
                )
                ->join(
                    'purchase_lines as pl',
                    'tspl.purchase_line_id',
                    '=',
                    'pl.id'
                )
                ->join(
                    'transactions as purchase',
                    'pl.transaction_id',
                    '=',
                    'purchase.id'
                )
                ->leftjoin('contacts as supplier', 'purchase.contact_id', '=', 'supplier.id')
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as customer',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    'tspl.quantity as purchase_quantity',
                    'u.short_name as unit',
                    'supplier.name as supplier_name',
                    'purchase.ref_no as ref_no',
                    'purchase.type as purchase_type',
                    'pl.lot_number'
                );

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(t.transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('invoice_no', function ($row) {
                    return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('purchase_quantity', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency purchase_quantity" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_quantity . '</span> ' . $row->unit;
                })
                ->editColumn('ref_no', '
                    @if($purchase_type == "opening_stock")
                        <i><small class="help-block">(@lang("lang_v1.opening_stock"))</small></i>
                    @else
                        {{$ref_no}}
                    @endif
                    ')
                ->rawColumns(['invoice_no', 'purchase_quantity', 'ref_no'])
                ->make(true);
        }
    }

    /**
     * Shows product lot report
     *
     * @return \Illuminate\Http\Response
     */
    public function getLotReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Product::where('products.business_id', $business_id)
                ->leftjoin('units', 'products.unit_id', '=', 'units.id')
                ->join('variations as v', 'products.id', '=', 'v.product_id')
                ->join('purchase_lines as pl', 'v.id', '=', 'pl.variation_id')
                ->leftjoin(
                    'transaction_sell_lines_purchase_lines as tspl',
                    'pl.id',
                    '=',
                    'tspl.purchase_line_id'
                )
                ->join('transactions as t', 'pl.transaction_id', '=', 't.id');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = 'WHERE ';

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter = " LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id IN ($locations_imploded) AND ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id)
                    //If filter by location then hide products not available in that location
                    ->ForLocation($location_id);

                $location_filter = "LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id=$location_id AND ";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('products.category_id', $request->input('category_id'));
            }

            if (!empty($request->input('sub_category_id'))) {
                $query->where('products.sub_category_id', $request->input('sub_category_id'));
            }

            if (!empty($request->input('brand_id'))) {
                $query->where('products.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('products.unit_id', $request->input('unit_id'));
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('t.type', 'production_purchase');
            }

            $products = $query->select(
                'products.name as product',
                'v.name as variation_name',
                'sub_sku',
                'pl.lot_number',
                'pl.exp_date as exp_date',
                DB::raw("( COALESCE((SELECT SUM(quantity - quantity_returned) from purchase_lines as pls $location_filter variation_id = v.id AND lot_number = pl.lot_number), 0) - 
                    SUM(COALESCE((tspl.quantity - tspl.qty_returned), 0))) as stock"),
                // DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity, -1* TPL.quantity) ) FROM transactions
                //         LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                //         LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                //         WHERE transactions.status='final' AND transactions.type IN ('sell', 'sell_return') $location_filter
                //         AND (TSL.product_id=products.id OR TPL.product_id=products.id)) as total_sold"),

                DB::raw("COALESCE(SUM(IF(tspl.sell_line_id IS NULL, 0, (tspl.quantity - tspl.qty_returned)) ), 0) as total_sold"),
                DB::raw("COALESCE(SUM(IF(tspl.stock_adjustment_line_id IS NULL, 0, tspl.quantity ) ), 0) as total_adjusted"),
                'products.type',
                'units.short_name as unit'
            )
                ->whereNotNull('pl.lot_number')
                ->groupBy('v.id')
                ->groupBy('pl.lot_number');

            return Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    return '<span data-is_quantity="true" class="display_currency total_stock" data-currency_symbol=false data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" >' . (float)$stock . '</span> ' . $row->unit;
                })
                ->editColumn('product', function ($row) {
                    if ($row->variation_name != 'DUMMY') {
                        return $row->product . ' (' . $row->variation_name . ')';
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('total_sold', function ($row) {
                    if ($row->total_sold) {
                        return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . (float)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (float)$row->total_sold . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    if ($row->total_adjusted) {
                        return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false data-orig-value="' . (float)$row->total_adjusted . '" data-unit="' . $row->unit . '" >' . (float)$row->total_adjusted . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('exp_date', function ($row) {
                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                            return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                        } else {
                            return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                        }
                    } else {
                        return '--';
                    }
                })
                ->removeColumn('unit')
                ->removeColumn('id')
                ->removeColumn('variation_name')
                ->rawColumns(['exp_date', 'stock', 'total_sold', 'total_adjusted'])
                ->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.lot_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations'));
    }

    /**
     * Shows purchase payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchasePaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $supplier_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($supplier_id) ? "AND t.contact_id=$supplier_id" : '';
            $contact_filter2 = !empty($supplier_id) ? "AND transactions.contact_id=$supplier_id" : '';

            $location_id = $request->get('location_id', null);

            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['purchase', 'opening_balance']);
            })
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('purchase', 'opening_balance')  $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('purchase', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })

                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT c.name FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as supplier"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    't.ref_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_no',
                    'transaction_payments.id as DT_RowId'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $payment_types = $this->transactionUtil->payment_types();

            return Datatables::of($query)
                ->editColumn('ref_no', function ($row) {
                    if (!empty($row->ref_no)) {
                        return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) use ($payment_types) {
                    $method = !empty($payment_types[$row->method]) ? $payment_types[$row->method] : '';
                    if ($row->method == 'cheque') {
                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {
                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-currency_symbol = true data-orig-value="' . $row->amount . '">' . $row->amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['ref_no', 'amount', 'method', 'action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);

        return view('report.purchase_payment_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows sell payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function sellPaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $customer_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($customer_id) ? "AND t.contact_id=$customer_id" : '';
            $contact_filter2 = !empty($customer_id) ? "AND transactions.contact_id=$customer_id" : '';

            $location_id = $request->get('location_id', null);
            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['sell', 'opening_balance']);
            })
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftjoin('customer_groups AS CG', 'c.customer_group_id', '=', 'CG.id')
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('sell', 'opening_balance') $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('sell', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })
                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT c.name FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as customer"),
                    'transaction_payments.amount',
                    'transaction_payments.is_return',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    'transaction_payments.transaction_no',
                    't.invoice_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_payments.id as DT_RowId',
                    'CG.name as customer_group'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->get('customer_group_id'))) {
                $query->where('CG.id', $request->get('customer_group_id'));
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            if (!empty($request->get('payment_types'))) {
                $query->where('transaction_payments.method', $request->get('payment_types'));
            }
            $payment_types = $this->transactionUtil->payment_types();
            return Datatables::of($query)
                ->editColumn('invoice_no', function ($row) {
                    if (!empty($row->transaction_id)) {
                        return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) use ($payment_types) {
                    $method = !empty($payment_types[$row->method]) ? $payment_types[$row->method] : '';
                    if ($row->method == 'cheque') {
                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {
                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {
                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }
                    if ($row->is_return == 1) {
                        $method .= '<br><small>(' . __('lang_v1.change_return') . ')</small>';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    $amount = $row->is_return == 1 ? -1 * $row->amount : $row->amount;
                    return '<span class="display_currency paid-amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['invoice_no', 'amount', 'method', 'action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $payment_types = $this->transactionUtil->payment_types();
        $customer_groups = CustomerGroup::forDropdown($business_id, false, true);

        return view('report.sell_payment_report')
            ->with(compact('business_locations', 'customers', 'payment_types', 'customer_groups'));
    }


    /**
     * Shows tables report
     *
     * @return \Illuminate\Http\Response
     */
    public function getTableReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = ResTable::leftjoin('transactions AS T', 'T.res_table_id', '=', 'res_tables.id')
                ->where('T.business_id', $business_id)
                ->where('T.type', 'sell')
                ->where('T.status', 'final')
                ->groupBy('res_tables.id')
                ->select(DB::raw("SUM(final_total) as total_sell"), 'res_tables.name as table');

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('T.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.table_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows service staff report
     *
     * @return \Illuminate\Http\Response
     */
    public function getServiceStaffReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $waiters = $this->transactionUtil->serviceStaffDropdown($business_id);

        return view('report.service_staff_report')
            ->with(compact('business_locations', 'waiters'));
    }

    /**
     * Shows product sell report grouped by date
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellGroupedReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.enable_stock',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                    'u.short_name as unit',
                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->groupBy('v.id')
                ->groupBy('formated_date');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' . $row->unit;
                })
                ->editColumn('current_stock', function ($row) {
                    if ($row->enable_stock) {
                        return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (float)$row->current_stock . '" data-unit="' . $row->unit . '" >' . (float) $row->current_stock . '</span> ' . $row->unit;
                    } else {
                        return '';
                    }
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . round($row->subtotal) . '">' . round($row->subtotal) . '</span>';
                })

                ->rawColumns(['current_stock', 'subtotal', 'total_qty_sold'])
                ->make(true);
        }
    }

    /**
     * Shows product stock details and allows to adjust mismatch
     *
     * @return \Illuminate\Http\Response
     */
    public function productStockDetails()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $stock_details = [];
        $location = null;
        $total_stock_calculated = 0;
        if (!empty(request()->input('location_id'))) {
            $variation_id = request()->get('variation_id', null);
            $location_id = request()->input('location_id');

            $location = BusinessLocation::where('business_id', $business_id)
                ->where('id', $location_id)
                ->first();

            $query = Variation::leftjoin('products as p', 'p.id', '=', 'variations.product_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->where('vld.location_id', $location_id);
            if (!is_null($variation_id)) {
                $query->where('variations.id', $variation_id);
            }

            $stock_details = $query->select(
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_return"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity,0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_transfered"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity,0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase_transfer' AND transactions.location_id=$location_id 
                        AND PL.variation_id=variations.id) as total_purchase_transfered"),
                DB::raw("(SELECT SUM(COALESCE(SAL.quantity, 0)) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' AND transactions.location_id=$location_id 
                        AND SAL.variation_id=variations.id) as total_adjusted"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchased"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchase_return"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='opening_stock' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_opening_stock"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='production_purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_manufactured"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='production_sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_ingredients_used"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sub_sku',
                'p.name as product',
                'p.id as product_id',
                'p.type',
                'p.sku as sku',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name',
                'variations.id as variation_id'
            )
                ->groupBy('variations.id')
                ->get();

            foreach ($stock_details as $index => $row) {
                $total_sold = $row->total_sold ?: 0;
                $total_sell_return = $row->total_sell_return ?: 0;
                $total_sell_transfered = $row->total_sell_transfered ?: 0;

                $total_purchase_transfered = $row->total_purchase_transfered ?: 0;
                $total_adjusted = $row->total_adjusted ?: 0;
                $total_purchased = $row->total_purchased ?: 0;
                $total_purchase_return = $row->total_purchase_return ?: 0;
                $total_opening_stock = $row->total_opening_stock ?: 0;
                $total_manufactured = $row->total_manufactured ?: 0;
                $total_ingredients_used = $row->total_ingredients_used ?: 0;

                $total_stock_calculated = $total_opening_stock + $total_purchased + $total_purchase_transfered + $total_sell_return + $total_manufactured
                    - ($total_sold + $total_sell_transfered + $total_adjusted + $total_purchase_return + $total_ingredients_used);

                $stock_details[$index]->total_stock_calculated = $total_stock_calculated;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.product_stock_details')
            ->with(compact('stock_details', 'business_locations', 'location'));
    }

    /**
     * Adjusts stock availability mismatch if found
     *
     * @return \Illuminate\Http\Response
     */
    public function adjustProductStock()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        if (
            !empty(request()->input('variation_id'))
            && !empty(request()->input('location_id'))
            && request()->has('stock')
        ) {
            $business_id = request()->session()->get('user.business_id');

            $vld = VariationLocationDetails::leftjoin(
                'business_locations as bl',
                'bl.id',
                '=',
                'variation_location_details.location_id'
            )
                ->where('variation_location_details.location_id', request()->input('location_id'))
                ->where('variation_id', request()->input('variation_id'))
                ->where('bl.business_id', $business_id)
                ->select('variation_location_details.*')
                ->first();

            if (!empty($vld)) {
                $vld->qty_available = request()->input('stock');
                $vld->save();
            }
        }

        return redirect()->back()->with(['status' => [
            'success' => 1,
            'msg' => __('lang_v1.updated_succesfully')
        ]]);
    }

    /**
     * Retrieves line orders/sales
     *
     * @return obj
     */
    public function serviceStaffLineOrders()
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
            ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->leftJoin('users as ss', 'ss.id', '=', 'transaction_sell_lines.res_service_staff_id')
            ->leftjoin(
                'business_locations AS bl',
                't.location_id',
                '=',
                'bl.id'
            )
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('transaction_sell_lines.res_service_staff_id');


        if (!empty(request()->service_staff_id)) {
            $query->where('transaction_sell_lines.res_service_staff_id', request()->service_staff_id);
        }

        if (request()->has('location_id')) {
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }
        }

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('t.transaction_date', '>=', $start)
                ->whereDate('t.transaction_date', '<=', $end);
        }

        $query->select(
            'p.name as product_name',
            'p.type as product_type',
            'v.name as variation_name',
            'pv.name as product_variation_name',
            'u.short_name as unit',
            't.id as transaction_id',
            'bl.name as business_location',
            't.transaction_date',
            't.invoice_no',
            'transaction_sell_lines.quantity',
            'transaction_sell_lines.unit_price_before_discount',
            'transaction_sell_lines.line_discount_type',
            'transaction_sell_lines.line_discount_amount',
            'transaction_sell_lines.item_tax',
            'transaction_sell_lines.unit_price_inc_tax',
            DB::raw('CONCAT(COALESCE(ss.first_name, ""), COALESCE(ss.last_name, "")) as service_staff')
        );

        $datatable = Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                $name = $row->product_name;
                if ($row->product_type == 'variable') {
                    $name .= ' - ' . $row->product_variation_name . ' - ' . $row->variation_name;
                }
                return $name;
            })
            ->editColumn(
                'unit_price_inc_tax',
                '<span class="display_currency unit_price_inc_tax" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax}}">{{$unit_price_inc_tax}}</span>'
            )
            ->editColumn(
                'item_tax',
                '<span class="display_currency item_tax" data-currency_symbol="true" data-orig-value="{{$item_tax}}">{{$item_tax}}</span>'
            )
            ->editColumn(
                'quantity',
                '<span class="display_currency quantity" data-unit="{{$unit}}" data-currency_symbol="false" data-orig-value="{{$quantity}}">{{$quantity}}</span> {{$unit}}'
            )
            ->editColumn(
                'unit_price_before_discount',
                '<span class="display_currency unit_price_before_discount" data-currency_symbol="true" data-orig-value="{{$unit_price_before_discount}}">{{$unit_price_before_discount}}</span>'
            )
            ->addColumn(
                'total',
                '<span class="display_currency total" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax * $quantity}}">{{$unit_price_inc_tax * $quantity}}</span>'
            )
            ->editColumn(
                'line_discount_amount',
                function ($row) {
                    $discount = !empty($row->line_discount_amount) ? $row->line_discount_amount : 0;

                    if (!empty($discount) && $row->line_discount_type == 'percentage') {
                        $discount = $row->unit_price_before_discount * ($discount / 100);
                    }

                    return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                }
            )
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')

            ->rawColumns(['line_discount_amount', 'unit_price_before_discount', 'item_tax', 'unit_price_inc_tax', 'item_tax', 'quantity', 'total'])
            ->make(true);

        return $datatable;
    }

    /**
     * Lists profit by product, category, brand, location, invoice and date
     *
     * @return string $by = null
     */
    public function getProfit($by = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine
            ::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftjoin(
                'purchase_lines as PL',
                'TSPL.purchase_line_id',
                '=',
                'PL.id'
            )
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.business_id', $business_id)
            ->where('transaction_sell_lines.children_type', '!=', 'combo');
        //If type combo: find childrens, sale price parent - get PP of childrens
        $query->select(DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", ( 
    SELECT Sum((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)) AS total
        FROM transaction_sell_lines AS tsl
            JOIN transaction_sell_lines_purchase_lines AS tspl2
        ON tsl.id=tspl2.sell_line_id 
        JOIN purchase_lines AS pl2 
        ON tspl2.purchase_line_id = pl2.id 
        WHERE tsl.parent_sell_line_id = transaction_sell_lines.id), IF(P.enable_stock=0,(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,   
        (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) )) AS gross_profit'));

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('sale.transaction_date', '>=', $start)
                ->whereDate('sale.transaction_date', '<=', $end);
        }

        if ($by == 'product') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('product_variations as PV', 'PV.id', '=', 'V.product_variation_id')
                ->addSelect(DB::raw("IF(P.type='variable', CONCAT(P.name, ' - ', PV.name, ' - ', V.name, ' (', V.sub_sku, ')'), CONCAT(P.name, ' (', P.sku, ')')) as product"))
                ->groupBy('V.id');
        }

        if ($by == 'category') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('categories as C', 'C.id', '=', 'P.category_id')
                ->addSelect("C.name as category")
                ->groupBy('C.id');
        }

        if ($by == 'brand') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('brands as B', 'B.id', '=', 'P.brand_id')
                ->addSelect("B.name as brand")
                ->groupBy('B.id');
        }

        if ($by == 'location') {
            $query->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->addSelect("L.name as location")
                ->groupBy('L.id');
        }

        if ($by == 'invoice') {
            $query->addSelect('sale.invoice_no', 'sale.id as transaction_id')
                ->groupBy('sale.invoice_no');
        }

        if ($by == 'date') {
            $query->addSelect("sale.transaction_date")
                ->groupBy(DB::raw('DATE(sale.transaction_date)'));
        }

        if ($by == 'day') {
            $results = $query->addSelect(DB::raw("DAYNAME(sale.transaction_date) as day"))
                ->groupBy(DB::raw('DAYOFWEEK(sale.transaction_date)'))
                ->get();

            $profits = [];
            foreach ($results as $result) {
                $profits[strtolower($result->day)] = $result->gross_profit;
            }
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            return view('report.partials.profit_by_day')->with(compact('profits', 'days'));
        }

        if ($by == 'customer') {
            $query->join('contacts as CU', 'sale.contact_id', '=', 'CU.id')
                ->addSelect("CU.name as customer")
                ->groupBy('sale.contact_id');
        }

        $datatable = Datatables::of($query)
            ->editColumn(
                'gross_profit',
                '<span class="display_currency gross-profit" data-currency_symbol="true" data-orig-value="{{$gross_profit}}">{{$gross_profit}}</span>'
            );

        if ($by == 'category') {
            $datatable->editColumn(
                'category',
                '{{$category ?? __("lang_v1.uncategorized")}}'
            );
        }
        if ($by == 'brand') {
            $datatable->editColumn(
                'brand',
                '{{$brand ?? __("report.others")}}'
            );
        }

        if ($by == 'date') {
            $datatable->editColumn('transaction_date', '{{@format_date($transaction_date)}}');
        }

        $raw_columns = ['gross_profit'];
        if ($by == 'invoice') {
            $datatable->editColumn('invoice_no', function ($row) {
                return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                    . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
            });
            $raw_columns[] = 'invoice_no';
        }
        return $datatable->rawColumns($raw_columns)
            ->make(true);
    }

    /**
     * Shows items report from sell purchase mapping table
     *
     * @return \Illuminate\Http\Response
     */
    public function itemsReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = TransactionSellLinesPurchaseLines::leftJoin('transaction_sell_lines 
                    as SL', 'SL.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
                ->leftJoin('stock_adjustment_lines 
                    as SAL', 'SAL.id', '=', 'transaction_sell_lines_purchase_lines.stock_adjustment_line_id')
                ->leftJoin('transactions as sale', 'SL.transaction_id', '=', 'sale.id')
                ->leftJoin('transactions as stock_adjustment', 'SAL.transaction_id', '=', 'stock_adjustment.id')
                ->join('purchase_lines as PL', 'PL.id', '=', 'transaction_sell_lines_purchase_lines.purchase_line_id')
                ->join('transactions as purchase', 'PL.transaction_id', '=', 'purchase.id')
                ->join('business_locations as bl', 'purchase.location_id', '=', 'bl.id')
                ->join(
                    'variations as v',
                    'PL.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'PL.product_id', '=', 'p.id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('contacts as suppliers', 'purchase.contact_id', '=', 'suppliers.id')
                ->leftJoin('contacts as customers', 'sale.contact_id', '=', 'customers.id')
                ->where('purchase.business_id', $business_id)
                ->select(
                    'v.sub_sku as sku',
                    'p.type as product_type',
                    'p.name as product_name',
                    'v.name as variation_name',
                    'pv.name as product_variation',
                    'u.short_name as unit',
                    'purchase.transaction_date as purchase_date',
                    'purchase.ref_no as purchase_ref_no',
                    'purchase.type as purchase_type',
                    'suppliers.name as supplier',
                    'PL.purchase_price_inc_tax as purchase_price',
                    'sale.transaction_date as sell_date',
                    'stock_adjustment.transaction_date as stock_adjustment_date',
                    'sale.invoice_no as sale_invoice_no',
                    'stock_adjustment.ref_no as stock_adjustment_ref_no',
                    'customers.name as customer',
                    'transaction_sell_lines_purchase_lines.quantity as quantity',
                    'SL.unit_price_inc_tax as selling_price',
                    'SAL.unit_price as stock_adjustment_price',
                    'transaction_sell_lines_purchase_lines.stock_adjustment_line_id',
                    'transaction_sell_lines_purchase_lines.sell_line_id',
                    'transaction_sell_lines_purchase_lines.purchase_line_id',
                    'transaction_sell_lines_purchase_lines.qty_returned',
                    'bl.name as location'
                );

            if (!empty(request()->purchase_start) && !empty(request()->purchase_end)) {
                $start = request()->purchase_start;
                $end =  request()->purchase_end;
                $query->whereDate('purchase.transaction_date', '>=', $start)
                    ->whereDate('purchase.transaction_date', '<=', $end);
            }
            if (!empty(request()->sale_start) && !empty(request()->sale_end)) {
                $start = request()->sale_start;
                $end =  request()->sale_end;
                $query->where(function ($q) use ($start, $end) {
                    $q->where(function ($qr) use ($start, $end) {
                        $qr->whereDate('sale.transaction_date', '>=', $start)
                            ->whereDate('sale.transaction_date', '<=', $end);
                    })->orWhere(function ($qr) use ($start, $end) {
                        $qr->whereDate('stock_adjustment.transaction_date', '>=', $start)
                            ->whereDate('stock_adjustment.transaction_date', '<=', $end);
                    });
                });
            }

            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('suppliers.id', $supplier_id);
            }

            $customer_id = request()->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('customers.id', $customer_id);
            }

            $location_id = request()->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('purchase.location_id', $location_id);
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('purchase.type', 'production_purchase');
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('purchase_date', '{{@format_datetime($purchase_date)}}')
                ->editColumn('purchase_ref_no', function ($row) {
                    $html = $row->purchase_type == 'purchase' ? '<a data-href="' . action('PurchaseController@show', [$row->purchase_line_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->purchase_ref_no . '</a>' : $row->purchase_ref_no;
                    if ($row->purchase_type == 'opening_stock') {
                        $html .= '(' . __('lang_v1.opening_stock') . ')';
                    }
                    return $html;
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="display_currency purchase_price" data-currency_symbol=true data-orig-value="' . $row->purchase_price . '">' . $row->purchase_price . '</span>';
                })
                ->editColumn('sell_date', '@if(!empty($sell_line_id)) {{@format_datetime($sell_date)}} @else {{@format_datetime($stock_adjustment_date)}} @endif')

                ->editColumn('sale_invoice_no', function ($row) {
                    $invoice_no = !empty($row->sell_line_id) ? $row->sale_invoice_no : $row->stock_adjustment_ref_no . '<br><small>(' . __('stock_adjustment.stock_adjustment') . '</small>';

                    return $invoice_no;
                })
                ->editColumn('quantity', function ($row) {
                    $html = '<span data-is_quantity="true" class="display_currency quantity" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity . '</span> ' . $row->unit;
                    if ($row->qty_returned > 0) {
                        $html .= '<small><i>(<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $row->quantity . '</span> ' . $row->unit . ' ' . __('lang_v1.returned') . ')</i></small>';
                    }

                    return $html;
                })
                ->editColumn('selling_price', function ($row) {
                    $selling_price = !empty($row->sell_line_id) ? $row->selling_price : $row->stock_adjustment_price;

                    return '<span class="display_currency row_selling_price" data-currency_symbol=true data-orig-value="' . $selling_price . '">' . $selling_price . '</span>';
                })

                ->addColumn('subtotal', function ($row) {
                    $selling_price = !empty($row->sell_line_id) ? $row->selling_price : $row->stock_adjustment_price;
                    $subtotal = $selling_price * $row->quantity;
                    return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                })

                ->filterColumn('sale_invoice_no', function ($query, $keyword) {
                    $query->where('sale.invoice_no', 'like', ["%{$keyword}%"])
                        ->orWhere('stock_adjustment.ref_no', 'like', ["%{$keyword}%"]);
                })

                ->rawColumns(['subtotal', 'selling_price', 'quantity', 'purchase_price', 'sale_invoice_no', 'purchase_ref_no'])
                ->make(true);
        }

        $suppliers = Contact::suppliersDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.items_report')->with(compact('suppliers', 'customers', 'business_locations'));
    }

    /**
     * Shows purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchaseReport()
    {
        if ((!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create') && !auth()->user()->can('view_own_purchase')) || empty(config('constants.show_report_606'))) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types();
            $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join(
                    'business_locations AS BS',
                    'transactions.location_id',
                    '=',
                    'BS.id'
                )
                ->leftJoin(
                    'transaction_payments AS TP',
                    'transactions.id',
                    '=',
                    'TP.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase')
                ->with(['payment_lines'])
                ->select(
                    'transactions.id',
                    'transactions.ref_no',
                    'contacts.name',
                    'contacts.contact_id',
                    'final_total',
                    'total_before_tax',
                    'discount_amount',
                    'discount_type',
                    'tax_amount',
                    DB::raw('DATE_FORMAT(transaction_date, "%Y/%m") as purchase_year_month'),
                    DB::raw('DATE_FORMAT(transaction_date, "%d") as purchase_day')
                )
                ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->supplier_id)) {
                $purchases->where('contacts.id', request()->supplier_id);
            }
            if (!empty(request()->location_id)) {
                $purchases->where('transactions.location_id', request()->location_id);
            }
            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $purchases->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $purchases->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            if (!empty(request()->status)) {
                $purchases->where('transactions.status', request()->status);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
                $purchases->where('transactions.created_by', request()->session()->get('user.id'));
            }

            return Datatables::of($purchases)
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="display_currency total_before_tax" data-currency_symbol="true" data-orig-value="{{$total_before_tax}}">{{$total_before_tax}}</span>'
                )
                ->editColumn(
                    'tax_amount',
                    '<span class="display_currency tax_amount" data-currency_symbol="true" data-orig-value="{{$tax_amount}}">{{$tax_amount}}</span>'
                )
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                    }
                )
                ->addColumn('payment_year_month', function ($row) {
                    $year_month = '';
                    if (!empty($row->payment_lines->first())) {
                        $year_month = \Carbon::parse($row->payment_lines->first()->paid_on)->format('Y/m');
                    }
                    return $year_month;
                })
                ->addColumn('payment_day', function ($row) {
                    $payment_day = '';
                    if (!empty($row->payment_lines->first())) {
                        $payment_day = \Carbon::parse($row->payment_lines->first()->paid_on)->format('d');
                    }
                    return $payment_day;
                })
                ->addColumn('payment_method', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';
                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]];
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    $html = !empty($payment_method) ? '<span class="payment-method" data-orig-value="' . $payment_method . '" data-status-name="' . $payment_method . '">' . $payment_method . '</span>' : '';

                    return $html;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("purchase.view")) {
                            return  action('PurchaseController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['final_total', 'total_before_tax', 'tax_amount', 'discount_amount', 'payment_method'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $orderStatuses = $this->productUtil->orderStatuses();

        return view('report.purchase_report')
            ->with(compact('business_locations', 'suppliers', 'orderStatuses'));
    }

    /**
     * Shows sale report
     *
     * @return \Illuminate\Http\Response
     */
    public function saleReport()
    {
        if ((!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) || empty(config('constants.show_report_607'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        return view('report.sale_report')
            ->with(compact('business_locations', 'customers'));
    }

    /**
     * Calculates stock values
     *
     * @return array
     */
    public function getStockValue()
    {
        $business_id = request()->session()->get('user.business_id');
        $end_date = \Carbon::now()->format('Y-m-d');
        $location_id = request()->input('location_id');
        $filters = request()->only(['category_id', 'sub_category_id', 'brand_id', 'unit_id']);
        //Get Closing stock
        $closing_stock_by_pp = $this->transactionUtil->getOpeningClosingStock(
            $business_id,
            $end_date,
            $location_id,
            false,
            false,
            $filters
        );
        $closing_stock_by_sp = $this->transactionUtil->getOpeningClosingStock(
            $business_id,
            $end_date,
            $location_id,
            false,
            true,
            $filters
        );
        $potential_profit = $closing_stock_by_sp - $closing_stock_by_pp;
        $profit_margin = empty($closing_stock_by_sp) ? 0 : ($potential_profit / $closing_stock_by_sp) * 100;

        return [
            'closing_stock_by_pp' => $closing_stock_by_pp,
            'closing_stock_by_sp' => $closing_stock_by_sp,
            'potential_profit' => $potential_profit,
            'profit_margin' => $profit_margin
        ];
    }

    public function getProductProfitibity(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $query = Product::Join('transaction_sell_lines', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->Join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftjoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'products.unit_id', '=', 'u.id')
            ->where('products.type', '!=', 'modifier')
            ->where('products.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('products.type', '!=', 'modifier')
            ->where('products.business_id', $business_id);
        $products = $query->select(
            'products.id as products_id',
            'products.name as products_name',
            'c1.name as category_name',
            'c2.name as sub_category_name',
            'products.sku',
            'u.short_name as unit',
            'v.sell_price_inc_tax as sell_price_inc_tax',
            'v.default_sell_price as default_sell_price',
            'v.default_purchase_price as default_purchase_price',
            'tax_rates.amount as tax',
            'transaction_sell_lines.unit_price',
            'transaction_sell_lines.quantity',
            'transaction_sell_lines.unit_price_before_discount',
            DB::raw('IFNULL(round(sum((transaction_sell_lines.unit_price_before_discount + transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity)),0) as gross_amount'),
            DB::raw('IFNULL(sum((transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity),0) as total_tax'),
            DB::raw('IFNULL(sum(transaction_sell_lines.unit_price_before_discount*transaction_sell_lines.quantity),0) as total_sale_without_tax'),
            DB::raw('(transaction_sell_lines.unit_price_inc_tax + transaction_sell_lines.unit_price_inc_tax*tax_rates.amount/100) as base_price'),
            DB::raw('IFNULL(sum((transaction_sell_lines.unit_price_inc_tax + transaction_sell_lines.unit_price_inc_tax*tax_rates.amount/100)*transaction_sell_lines.quantity),0) as without_round_gross_amount'),
            DB::raw('IFNULL(sum(transaction_sell_lines.quantity),0) as sell_qty'),
            DB::raw('IFNULL(sum(v.default_purchase_price*transaction_sell_lines.quantity),0) as total_purchase_price'),
            DB::raw("(SELECT purchase_price FROM `purchase_lines` WHERE products.`business_id`=$business_id AND purchase_lines.`product_id`=products.id GROUP BY products.id) as unit_cost_inc_tax"),
            DB::raw("(SELECT ROUND(AVG(purchase_price),2) AS avg_unit_cost_inc_tax FROM `purchase_lines` WHERE products.`business_id`=$business_id AND purchase_lines.`product_id`=products.id GROUP BY products.id) as avg_unit_cost_inc_tax"),
            DB::raw('(CASE 
                        WHEN sum(round((transaction_sell_lines.unit_price_inc_tax + transaction_sell_lines.unit_price_inc_tax*tax_rates.amount/100)*transaction_sell_lines.quantity)) < (v.sell_price_inc_tax*transaction_sell_lines.quantity) THEN "1" 
                        WHEN sum(round((transaction_sell_lines.unit_price_inc_tax + transaction_sell_lines.unit_price_inc_tax*tax_rates.amount/100)*transaction_sell_lines.quantity)) > (v.sell_price_inc_tax*transaction_sell_lines.quantity) THEN "1" 
                        ELSE "0" 
                        END) AS variance_status')
        );
        $type = request()->get('type', null);
        if (!empty($type)) {
            $products->where('products.type', $type);
        }

        $category_id = request()->get('category_id', null);
        if (!empty($category_id)) {
            $products->where('products.category_id', $category_id);
        }
        $sub_category_id = request()->get('sub_category_id', null);
        if (!empty($sub_category_id)) {
            $products->where('products.sub_category_id', $sub_category_id);
        }
        $brand_id = request()->get('brand_id', null);
        if (!empty($brand_id)) {
            $products->where('products.brand_id', $brand_id);
        }
        $start_date = request()->get('start_date', null);
        if (!empty($start_date)) {
            $start_date = date(request()->get('start_date', null));
            $end_date = date(request()->get('end_date', null));
            if ($start_date == $end_date) {
                $products->whereDate('transactions.transaction_date', $start_date);
            } else {
                $products->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
            }
        }

        $unit_id = request()->get('unit_id', null);
        if (!empty($unit_id)) {
            $products->where('products.unit_id', $unit_id);
        }

        $tax_id = request()->get('tax_id', null);
        if (!empty($tax_id)) {
            $products->where('products.tax', $tax_id);
        }
        $location_id = request()->get('location_id', null);
        if (!empty($location_id)) {
            $products->where('transactions.location_id', $location_id);
        }

        $active_state = request()->get('active_state', null);
        if ($active_state == 'active') {
            $products->Active();
        }
        if ($active_state == 'inactive') {
            $products->Inactive();
        }
        $not_for_selling = request()->get('not_for_selling', null);
        if ($not_for_selling == 'true') {
            $products->ProductNotForSales();
        }

        if (!empty(request()->get('repair_model_id'))) {
            $products->where('products.repair_model_id', request()->get('repair_model_id'));
        }
        $products->groupBy('products.id', 'products.id');
        //$productstwo = $products->groupBy('products.id', 'products.id');
        //$products = $products->get();
        //dd($products);
        //$getproductids = $products->get()->pluck('products_id');
        $gettotal = $products->get()->toArray();
        $totalPriceIncTax = array_sum(array_column($gettotal, 'sell_price_inc_tax'));
        $totalSellQty = array_sum(array_column($gettotal, 'sell_qty'));
        $totalPurchasePrice = array_sum(array_column($gettotal, 'total_purchase_price'));
        $totalGross = array_sum(array_column($gettotal, 'gross_amount'));
        // ->where('products.id', '1887');
        return Datatables::of($products)->addColumn('current_stock', function ($row) {
            return '';
        })->addColumn('current_stock_pp', function ($row) {
            return '';
        })->addColumn('current_stock_sp', function ($row) {
            return '';
        })->addColumn('products_name', function ($row) {
            return '<a href="#" data-products_id="' . $row->products_id . '" data-container=".view_product_detail_model" class="getProductDetail" >' . $row->products_name . '</a>';
        })->addColumn('sell_qty', function ($row) {
            return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false">' . (float) $row->sell_qty . '</span> ' . $row->unit;
        })->addColumn('unit_sale_price', function ($row) {
            return '<span class="display_currency" data-currency_symbol = true>' . $row->sell_price_inc_tax . '</span>';
        })->addColumn('totaltax', function ($row) {
            $totaltax = $row->total_tax;
            return '<span class="display_currency total_tax" data-currency_symbol = true data-orig-value="' . $totaltax . '">' . $totaltax . '</span>';
        })->addColumn('total_sale_without_tax', function ($row) {
            $total_sale_without_tax = $row->total_sale_without_tax;
            return '<span class="display_currency total_sale_without_tax" data-currency_symbol = true data-orig-value="' . $total_sale_without_tax . '">' . $total_sale_without_tax . '</span>';
        })->addColumn('total_sale_gross', function ($row) {
            $total_sale_gross = $row->gross_amount;
            return '<span class="display_currency total_sale_gross" data-currency_symbol = true data-orig-value="' . $total_sale_gross . '">' . $total_sale_gross . '</span>';
        })->addColumn('p_sale_gross', function ($row) use ($totalGross) {
            $p_sale_gross = ($row->gross_amount * 100) / $totalGross;
            $p_sale_gross = number_format($p_sale_gross, 6, ".", ".");
            $class = 'plus';
            if ($p_sale_gross < 0) {
                $class = 'minus';
            }
            return '<span class="' . $class . '">' . $p_sale_gross . '</span>';
        })->addColumn('unit_cost_inc_tax', function ($row) {
            $unit_cost_inc_tax = $row->default_purchase_price;
            if ($row->unit_cost_inc_tax) {
                $unit_cost_inc_tax = $row->unit_cost_inc_tax;
            }
            return '<span class="display_currency" data-currency_symbol = true>' . ($unit_cost_inc_tax) . '</span>';
        })->addColumn('avg_unit_cost_inc_tax', function ($row) {
            $avg_unit_cost_inc_tax = $row->default_purchase_price;
            if ($row->avg_unit_cost_inc_tax) {
                $avg_unit_cost_inc_tax = $row->avg_unit_cost_inc_tax;
            }
            return '<span class="display_currency avg_unit_cost_inc_tax" data-currency_symbol ="true" data-orig-value="' . $avg_unit_cost_inc_tax . '">' . ($avg_unit_cost_inc_tax) . '</span>';
        })->addColumn('total_avg_cost', function ($row) {
            $total_avg_cost = $row->default_purchase_price * $row->sell_qty;
            if ($row->avg_unit_cost_inc_tax) {
                $total_avg_cost = $row->avg_unit_cost_inc_tax * $row->sell_qty;
            }
            return '<span class="display_currency total_avg_cost" data-currency_symbol = true data-orig-value="' . $total_avg_cost . '">' . ($total_avg_cost) . '</span>';
        })->addColumn('gross_profit', function ($row) {
            $total_sale_gross = $row->gross_amount;

            $total_avg_cost = $row->default_purchase_price * $row->sell_qty;
            if ($row->avg_unit_cost_inc_tax) {
                $total_avg_cost = $row->avg_unit_cost_inc_tax * $row->sell_qty;
            }

            $gross_profit = $total_sale_gross - $total_avg_cost;
            return '<span class="display_currency gross_profit" data-currency_symbol = true data-orig-value="' . $gross_profit . '">' . $gross_profit . '</span>';
        })->addColumn('p_gross_profit', function ($row) use ($totalPurchasePrice) {
            $total_avg_cost = $row->default_purchase_price * $row->sell_qty;
            if ($row->avg_unit_cost_inc_tax) {
                $total_avg_cost = $row->avg_unit_cost_inc_tax * $row->sell_qty;
            }
            $total_sale_gross = $row->gross_amount;
            $gross_profit = $total_sale_gross - $total_avg_cost;

            $p_gross_profit =  ($gross_profit * 100) / $totalPurchasePrice;
            $p_gross_profit = number_format($p_gross_profit, 6, ".", ".");
            $class = 'plus';
            if ($p_gross_profit < 0) {
                $class = 'minus';
            }
            return '<span class="' . $class . '">' . $p_gross_profit . '</span>';
        })->addColumn('p_gross_margin', function ($row) {
            $total_sale_gross = $row->gross_amount;
            $total_avg_cost = $row->default_purchase_price * $row->sell_qty;
            if ($row->avg_unit_cost_inc_tax) {
                $total_avg_cost = $row->avg_unit_cost_inc_tax * $row->sell_qty;
            }
            $p_gross_margin = 0;
            if ($total_sale_gross) {
                $p_gross_margin = ($total_sale_gross - $total_avg_cost) / $total_sale_gross;
            }
            $p_gross_margin = number_format($p_gross_margin, 6, ".", ".");
            $class = 'plus';
            if ($p_gross_margin < 0) {
                $class = 'minus';
            }
            return '<span class="' . $class . '">' . $p_gross_margin . '</span>';
        })->setRowAttr([
            'class' => function ($row) {
                $class = '';
                $gross_amount = $row->gross_amount;
                $total_gross_amount = $row->sell_price_inc_tax * $row->sell_qty;
                $total_default_sell_price = $row->default_sell_price * $row->sell_qty;

                if ($gross_amount < $total_gross_amount) {
                    $class = 'bg-yellow-light ' . $gross_amount . ' ' . $total_gross_amount . ' ' . $row->sell_qty . ' ' . $row->sell_price_inc_tax . '';
                } elseif ($gross_amount > $total_gross_amount) {
                    $class = 'bg-yellow-light ' . $gross_amount . ' ' . $total_gross_amount . ' ' . $row->sell_qty . ' ' . $row->sell_price_inc_tax . '';
                }
                return $class;
            }
        ])->rawColumns(['products_name', 'total_sale_gross', 'totaldiscount', 'totaltax', 'sell_qty', 'unit_sale_price', 'total_sale_without_tax', 'p_sale_gross', 'unit_cost_inc_tax', 'avg_unit_cost_inc_tax', 'total_avg_cost', 'gross_profit', 'p_gross_profit', 'p_gross_margin', 'current_stock', 'current_stock_pp', 'current_stock_sp'])->make(true);
    }
    public function getCheckPopupBasePrice($productid)
    {
        return true;
    }
    public function getProductProfitDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $product_id = request()->get('product_id');
        $getProductDetails = Product::where('id', $product_id)->first();
        $query = Product::Join('transaction_sell_lines', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->Join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('tax_rates', 'products.tax', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'products.unit_id', '=', 'u.id')
            ->where('products.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('products.id', $product_id);
        $products = $query->select(
            'products.id as products_id',
            'products.name as product',
            'c1.name as category_name',
            'c2.name as sub_category_name',
            'products.sku',
            'v.sell_price_inc_tax as sell_price_inc_tax',
            'v.default_sell_price as default_sell_price',
            'v.default_purchase_price as default_purchase_price',
            'transaction_sell_lines.unit_price',
            'transaction_sell_lines.quantity',
            'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax',
            'u.short_name as unit',
            'transactions.invoice_no',
            'transactions.transaction_date as transaction_date',
            'tax_rates.amount as tax',
            'transactions.id as transaction_id',
            DB::raw('IFNULL(round(sum((transaction_sell_lines.unit_price_before_discount + transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity)),0) as gross_amount'),
            DB::raw("(SELECT ROUND(AVG(purchase_price),2) AS avg_unit_cost_inc_tax FROM `purchase_lines` WHERE products.`business_id`=$business_id AND purchase_lines.`product_id`=products.id GROUP BY products.id) as avg_unit_cost_inc_tax"),
            DB::raw('IFNULL(sum((transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity),0) as total_tax'),
        );
        $type = request()->get('type', null);
        if (!empty($type)) {
            $products->where('products.type', $type);
        }

        $category_id = request()->get('category_id', null);
        if (!empty($category_id)) {
            $products->where('products.category_id', $category_id);
        }
        $sub_category_id = request()->get('sub_category_id', null);
        if (!empty($sub_category_id)) {
            $products->where('products.sub_category_id', $sub_category_id);
        }
        $brand_id = request()->get('brand_id', null);
        if (!empty($brand_id)) {
            $products->where('products.brand_id', $brand_id);
        }
        $start_date = request()->get('start_date', null);
        if (!empty($start_date)) {
            $start_date = date(request()->get('start_date', null));
            $end_date = date(request()->get('end_date', null));
            if ($start_date == $end_date) {
                $products->whereDate('transactions.transaction_date', $start_date);
            } else {
                $products->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
            }
        }

        $unit_id = request()->get('unit_id', null);
        if (!empty($unit_id)) {
            $products->where('products.unit_id', $unit_id);
        }

        $tax_id = request()->get('tax_id', null);
        if (!empty($tax_id)) {
            $products->where('products.tax', $tax_id);
        }

        $active_state = request()->get('active_state', null);
        if ($active_state == 'active') {
            $products->Active();
        }
        if ($active_state == 'inactive') {
            $products->Inactive();
        }
        $not_for_selling = request()->get('not_for_selling', null);
        if ($not_for_selling == 'true') {
            $products->ProductNotForSales();
        }

        if (!empty(request()->get('repair_model_id'))) {
            $products->where('products.repair_model_id', request()->get('repair_model_id'));
        }
        $products = $products->groupBy('transaction_sell_lines.id')->get();
        $productdetails = view('product.partials.product_profit_details', compact('products', 'getProductDetails'))->render();

        return response()->json(['productdetails' => $productdetails]);
    }

    public function getSuppliersAgeing(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $contacts = Contact::where('contacts.business_id', $business_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->active()
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id',
                    'contacts.type as contact_type',
                    't.id as transaction_id'
                );
            $date = request()->get('date', null);
            if (!empty($date)) {
                $contacts->whereDate('t.transaction_date', $date);
            }

            $location_id = request()->get('location_id', null);
            if (!empty($location_id)) {
                $contacts->where('t.location_id', $location_id);
            }
            $customer_id = request()->get('customer_id', null);
            if (!empty($customer_id)) {
                $contacts->where('t.contact_id', $customer_id);
            }
            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $contacts->where('t.contact_id', $supplier_id);
            }
            $contacts->whereIn('contacts.type', [request()->get('contact_type')]);
            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }
            $contactsPeriod = $contacts;
            $contacts = $contacts->get();

            $contactsArray = $contacts->pluck('id');
            $transactionsIds = $contacts->pluck('transaction_id');

            $due1to30Array = [];
            $due31to60Array = [];
            $due61to90Array = [];
            $due91to120Array = [];
            $due121to150Array = [];
            $due151to180Array = [];
            $due180plusArray = [];
            $totalDueArray = [];
            $currentArray = [];
            foreach ($contactsArray as $ca) {

                $due1to30 = 0;
                $due31to60 = 0;
                $due61to90 = 0;
                $due91to120 = 0;
                $due121to150 = 0;
                $due151to180 = 0;
                $due180 = 0;
                $totalDue = 0;
                $current = 0;
                $getContactDetails = Contact::where('id', $ca)->get()->first();
                $contactType = $getContactDetails->type;
                $days = 30;
                if ($getContactDetails->pay_term_type == 'months') {
                    $getMonth = $getContactDetails->pay_term_number;
                    if ($getMonth) {
                        $currentmonth = date('m');
                        $currentyear = date('Y');
                        $days = 0;
                        for ($month = $getMonth; $month > 0; $month--) {
                            //$days += cal_days_in_month(CAL_GREGORIAN, $currentmonth, $currentyear);
                            $days += date('t', mktime(0, 0, 0, $currentmonth, 1, $currentyear));
                            $currentmonth--;
                        }
                    } else {
                        $days = 30;
                    }
                } elseif ($getContactDetails->pay_term_type == 'days') {
                    $days = $getContactDetails->pay_term_number;
                } else {
                    $days = 30;
                }

                /*Current*/
                $getCurrent = $this->getAgeingQuery($ca);
                $getCurrent = $getCurrent->whereDate('transactions.transaction_date', '>', \carbon\Carbon::now()->subdays($days)->format('Y-m-d'))->get()->first();
                if ($getCurrent) {
                    $getCurrent = $getCurrent->toArray();
                    if (!empty($getCurrent)) {
                        $current = $this->calculateDueAmount($getCurrent, $contactType);
                    }
                }
                $currentArray[] = $current;

                /*1 to 30 days */
                $get1to30Data = $this->getAgeingQuery($ca);
                $toDays30 = $days + 1;
                $fromDays30 = $days + 30;

                $get1to30Data = $get1to30Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays30)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays30)->format('Y-m-d')])->get()->first();


                if ($get1to30Data) {
                    $get1to30Data = $get1to30Data->toArray();
                    if (!empty($get1to30Data)) {
                        $due1to30 = $this->calculateDueAmount($get1to30Data, $contactType);
                    }
                }
                $due1to30Array[] = $due1to30;

                /*31 to 60 days */

                $toDays60 = $days + 31;
                $fromDays60 = $days + 60;
                $get31to60Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get31to60Data->whereDate('transactions.transaction_date', $date);
                // }
                $get31to60Data = $get31to60Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays60)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays60)->format('Y-m-d')])->get()->first();

                if ($get31to60Data) {
                    $get31to60Data = $get31to60Data->toArray();
                    if (!empty($get31to60Data)) {
                        $due31to60 = $this->calculateDueAmount($get31to60Data, $contactType);
                    }
                }
                $due31to60Array[] = $due31to60;

                /*61 to 90 days */
                $toDays90 = $days + 61;
                $fromDays90 = $days + 90;
                $get61to90Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get61to90Data->whereDate('transactions.transaction_date', $date);
                // }
                $get61to90Data = $get61to90Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays90)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays90)->format('Y-m-d')])->get()->first();
                if ($get61to90Data) {
                    $get61to90Data = $get61to90Data->toArray();
                    if (!empty($get61to90Data)) {
                        $due61to90 = $this->calculateDueAmount($get61to90Data, $contactType);
                    }
                }
                $due61to90Array[] = $due61to90;

                /*91 to 120 days */
                $toDays120 = $days + 91;
                $fromDays120 = $days + 120;
                $get91to120Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get91to120Data->whereDate('transactions.transaction_date', $date);
                // }
                $get91to120Data = $get91to120Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays120)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays120)->format('Y-m-d')])->get()->first();
                if ($get91to120Data) {
                    $get91to120Data = $get91to120Data->toArray();
                    if (!empty($get91to120Data)) {
                        $due91to120 = $this->calculateDueAmount($get91to120Data, $contactType);
                    }
                }
                $due91to120Array[] = $due91to120;

                /*121 to 150 days */
                $toDays150 = $days + 121;
                $fromDays150 = $days + 150;
                $get121to150Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get121to150Data->whereDate('transactions.transaction_date', $date);
                // }
                $get121to150Data = $get121to150Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays150)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays150)->format('Y-m-d')])->get()->first();
                if ($get121to150Data) {
                    $get121to150Data = $get121to150Data->toArray();
                    if (!empty($get121to150Data)) {
                        $due121to150 = $this->calculateDueAmount($get121to150Data, $contactType);
                    }
                }
                $due121to150Array[] = $due121to150;

                /*151 to 180 days */
                $toDays180 = $days + 151;
                $fromDays180 = $days + 180;
                $get151to180Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get151to180Data->whereDate('transactions.transaction_date', $date);
                // }
                $get151to180Data = $get151to180Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays180)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d')])->get()->first();
                if ($get151to180Data) {
                    $get151to180Data = $get151to180Data->toArray();
                    if (!empty($get151to180Data)) {
                        $due151to180 = $this->calculateDueAmount($get151to180Data, $contactType);
                    }
                }
                $due151to180Array[] = $due151to180;

                /*180 plus*/
                $toDays180 = $days + 181;
                $get180plusData = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get180plusData->whereDate('transactions.transaction_date', $date);
                // }
                $get180plusData = $get180plusData->where('transactions.transaction_date', '<=', \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d'))->get()->first();
                if ($get180plusData) {
                    $get180plusData = $get180plusData->toArray();
                    if (!empty($get180plusData)) {
                        $due180 = $this->calculateDueAmount($get180plusData, $contactType);
                    }
                }
                $due180plusArray[] = $due180;

                /*Total Due*/
                $getTotalDue = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $getTotalDue->whereDate('transactions.transaction_date', $date);
                // }
                $getTotalDue = $getTotalDue->get()->first();
                if ($getTotalDue) {
                    $getTotalDue = $getTotalDue->toArray();
                    if (!empty($getTotalDue)) {
                        $totalDue = $this->calculateDueAmount($getTotalDue, $contactType);
                    }
                }
                $totalDueArray[] = $totalDue;
            }

            if (request()->get('contact_type') == 'supplier') {
                return view('report.partials.supplier_contact', compact('contacts', 'due1to30Array', 'due31to60Array', 'due61to90Array', 'due91to120Array', 'due121to150Array', 'due151to180Array', 'due180plusArray', 'totalDueArray', 'currentArray'))->render();
            } else {
                return view('report.partials.customer_contact', compact('contacts', 'due1to30Array', 'due31to60Array', 'due61to90Array', 'due91to120Array', 'due121to150Array', 'due151to180Array', 'due180plusArray', 'totalDueArray', 'currentArray'))->render();
            }
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $types = [
            '' => __('lang_v1.all'),
            'customer' => __('report.customer'),
            'supplier' => __('report.supplier')
        ];
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);
        $suppliers = Contact::suppliersDropdown($business_id);
        return view('report.contact')
            ->with(compact('customer_group', 'types', 'business_locations', 'customers', 'suppliers'));
    }

    public function calculateDueAmount($data, $contactType)
    {
        $due = ($data['total_invoice'] - $data['invoice_received'] - $data['total_sell_return'] + $data['sell_return_paid']) - ($data['total_purchase'] - $data['total_purchase_return'] + $data['purchase_return_received'] - $data['purchase_paid']);
        if ($contactType == 'supplier') {
            $due -= $data['opening_balance'] - $data['opening_balance_paid'];
        } else {
            $due += $data['opening_balance'] - $data['opening_balance_paid'];
        }
        return $due;
    }

    public function getAgeingQuery($contactid)
    {
        $periodRow = Transaction::where('transactions.contact_id', $contactid)->select(
            DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as total_purchase"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(transactions.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_paid"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as invoice_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as sell_return_paid"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_return_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as total_sell_return"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as opening_balance_paid"),
        );
        return $periodRow;
    }

    public function getAgeingQueryDetails($contactid, $transactionsIds)
    {
        $periodRow = Transaction::where('transactions.contact_id', $contactid)->leftjoin(
            'business_locations AS BS',
            'transactions.location_id',
            '=',
            'BS.id'
        )->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')->select(
            DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as total_purchase"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(transactions.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_paid"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as invoice_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as sell_return_paid"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_return_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as total_sell_return"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as opening_balance_paid"),
            'transactions.*',
            'transactions.id as transaction_id',
            'BS.name as location_name',
            'contacts.name',
            'transactions.status',
            'transactions.payment_status',
            'transactions.ref_no as ref_no',
        );
        return $periodRow;
    }

    public function getAgeingDetails(Request $request)
    {
        $contact_id = request()->get('contact_id', null);
        $business_id = $request->session()->get('user.business_id');
        $col = request()->get('col', null);
        $date_f = request()->get('date', null);
        $contacts = Contact::where('contacts.business_id', $business_id)
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->active()
            ->groupBy('contacts.id')
            ->where('t.contact_id', $contact_id)
            ->select(
                'contacts.supplier_business_name',
                'contacts.name',
                'contacts.id',
                't.id as transaction_id'
            );
        if (!empty($date_f)) {
            $contacts->whereDate('t.transaction_date', $date_f);
        }
        $transactionsIds = $contacts->pluck('transaction_id');
        $data = [];
        $due = 0;

        $getContactDetails = Contact::where('id', $contact_id)->get()->first();
        $contactType = $getContactDetails->type;
        $days = 30;
        if ($getContactDetails->pay_term_type == 'months') {
            $getMonth = $getContactDetails->pay_term_number;
            if ($getMonth) {
                $currentmonth = date('m');
                $currentyear = date('Y');
                $days = 0;
                for ($month = $getMonth; $month > 0; $month--) {
                    //$days += cal_days_in_month(CAL_GREGORIAN, $currentmonth, $currentyear);
                    $days += date('t', mktime(0, 0, 0, $currentmonth, 1, $currentyear));
                    $currentmonth--;
                }
            } else {
                $days = 30;
            }
        } elseif ($getContactDetails->pay_term_type == 'days') {
            $days = $getContactDetails->pay_term_number;
        } else {
            $days = 30;
        }
        $label = '';
        if ($col == '1') {
            /*Current*/
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $data = $data->groupBy('transactions.id')->whereDate('transactions.transaction_date', '>', \carbon\Carbon::now()->subdays($days)->format('Y-m-d'))->get();
            $label = 'Current';
        } elseif ($col == '2') {
            $label = '1-30 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays30 = $days + 1;
            $fromDays30 = $days + 30;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays30)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays30)->format('Y-m-d')])->get();
        } elseif ($col == '3') {
            $label = '31-60 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays60 = $days + 31;
            $fromDays60 = $days + 60;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays60)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays60)->format('Y-m-d')])->get();
        } elseif ($col == '4') {
            $label = '61-90 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays90 = $days + 61;
            $fromDays90 = $days + 90;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays90)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays90)->format('Y-m-d')])->get();
        } elseif ($col == '5') {
            $label = '91-120 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays120 = $days + 91;
            $fromDays120 = $days + 120;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays120)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays120)->format('Y-m-d')])->get();
        } elseif ($col == '6') {
            $label = '121-150 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays150 = $days + 121;
            $fromDays150 = $days + 150;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays150)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays150)->format('Y-m-d')])->get();
        } elseif ($col == '7') {
            $label = '151-180 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays180 = $days + 151;
            $fromDays180 = $days + 180;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays180)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d')])->get();
        } elseif ($col == '8') {
            $label = '>= 180 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays180 = $days + 181;
            $data = $data->groupBy('transactions.id')->where('transactions.transaction_date', '<=', \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d'))->get();
        } else {
            $label = 'Total Due';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $data = $data->groupBy('transactions.id')->get();
        }

        $details = view('report.partials.ageing_details', compact('data', 'getContactDetails', 'label', 'contactType'))->render();
        return response()->json(['details' => $details]);
    }

    public function getExpanseDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $displaytype = '1';
        if ($request->get('displaytype') != null) {
            $displaytype = $request->get('displaytype');
        }
        $month = $request->get('month');
        $year = $request->get('year');
        $day = $request->get('day');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $display_total = $request->get('display_total');
        $expense_category_id = $request->get('expense_category_id');
        $location_id = $request->get('location_id');
        $expenses = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->join('business_locations AS bl','transactions.location_id','=','bl.id')
            ->leftJoin('tax_rates as tr', 'transactions.tax_id', '=', 'tr.id')
            ->leftJoin('users AS U', 'transactions.expense_for', '=', 'U.id')
            ->leftJoin('users AS usr', 'transactions.created_by', '=', 'usr.id')
            ->leftJoin('transaction_payments AS TP','transactions.id','=','TP.transaction_id'
            )
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'expense')
            ->select('transactions.id','transactions.document','transaction_date','ref_no','ec.name as category','payment_status','additional_notes','final_total','bl.name as location_name',DB::raw("CONCAT(COALESCE(U.surname, ''),' ',COALESCE(U.first_name, ''),' ',COALESCE(U.last_name,'')) as expense_for"),DB::raw("CONCAT(tr.name ,' (', tr.amount ,' )') as tax"),DB::raw('SUM(TP.amount) as amount_paid'),DB::raw("CONCAT(COALESCE(usr.surname, ''),' ',COALESCE(usr.first_name, ''),' ',COALESCE(usr.last_name,'')) as added_by")
            )
            ->groupBy('transactions.id');
        if($display_total){
            $getExpanses = $expenses->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }else{    
            if($displaytype == '1'){
                $getExpanses = $expenses->whereYear('transactions.transaction_date', '=', $year)->whereMonth('transactions.transaction_date', '=', $month);
            }
            if($displaytype == '2'){
                $getExpanses = $expenses->whereDate('transaction_date', $day);
            }
        }
        if ($location_id) {
            $getExpanses = $expenses->where('location_id', $location_id);
        }
        if($expense_category_id){
            if($expense_category_id =='100000'){
                $expense_category_id = null;
            }
            $getExpanses = $expenses->where('transactions.expense_category_id',$expense_category_id);
        }
        $getExpanses = $getExpanses->get();
        
        if($expense_category_id){
            $getCategory = ExpenseCategory::where('id',$expense_category_id)->get()->first();
            $categoryName = $getCategory->name;
        }else{
            $categoryName = 'Other';
        }

        $details = view('report.partials.profit_loss_expanses_details', compact('getExpanses','categoryName'))->render();
        return response()->json(['details' => $details]);
        //dd($getExpanses);
    }

    public function getSalesDetails(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $displaytype = '1';
        if ($request->get('displaytype') != null) {
            $displaytype = $request->get('displaytype');
        }
        $month = $request->get('month');
        $year = $request->get('year');
        $day = $request->get('day');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $display_total = $request->get('display_total');

        $viewtax = null;
        if($request->get('viewtax')){
            $viewtax = $request->get('viewtax');
        }

        $reporttype = $request->get('reporttype');

        $sales_location_id = $request->get('location_id');
        $getLocation = BusinessLocation::where('id',$sales_location_id)->get()->first();
        $locationName =null;
        if($getLocation){
            $locationName = $getLocation->name;
        }
        $location_id = $request->get('location_id');
        $getSales = $this->getProfitLossSalesDetailsData($business_id);
        $getSales = $getSales->where('transactions.payment_status', 'paid')->where('products.business_id', $business_id);
        if($sales_location_id){
            $getSales = $getSales->where('transactions.location_id', $sales_location_id);
        }
        if($display_total){
            $getSales = $getSales->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }else{ 
            if($displaytype == '1'){
                $getSales = $getSales->whereYear('transactions.transaction_date', '=', $year)->whereMonth('transactions.transaction_date', '=', $month);
            }
            if($displaytype == '2'){
                $getSales = $getSales->whereDate('transactions.transaction_date', $day);
            }
        }
        $getSales = $getSales->groupBy('transaction_sell_lines.id')->get();

        $getDueSalesData = $this->getProfitLossSalesDetailsData($business_id);
        $getDueSalesData = $getDueSalesData->where('transactions.business_id', $business_id)->where('payment_status', '!=', 'paid');
        if($sales_location_id){
            $getDueSalesData = $getDueSalesData->where('transactions.location_id', $sales_location_id);
        }
        if($display_total){
            $getDueSalesData = $getDueSalesData->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }else{ 
            if($displaytype == '1'){
                $getDueSalesData = $getDueSalesData->whereYear('transactions.transaction_date', '=', $year)->whereMonth('transactions.transaction_date', '=', $month);
            }
            if($displaytype == '2'){
                $getDueSalesData = $getDueSalesData->whereDate('transactions.transaction_date', $day);
            }
        }
        $getDueSalesData = $getDueSalesData->groupBy('transaction_sell_lines.id')->get();
        
        if ($reporttype == '2') {
            $getSalesArray = $getSales->concat($getDueSalesData);
        }else{
            $getSalesArray = $getSales;
        }

        $details = view('report.partials.profit_loss_sales_details', compact('getSalesArray','locationName','viewtax'))->render();
        return response()->json(['details' => $details]);
        //dd($getExpanses);
    }
    public function getProfitLossSalesDetailsData($business_id)
    {
        $getSalesData = Product::Join('transaction_sell_lines', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->Join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->Join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftjoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'products.unit_id', '=', 'u.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('products.type', '!=', 'modifier')
            ->select(
                DB::raw('IFNULL(round(sum((transaction_sell_lines.unit_price_before_discount + transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity)),0) as gross_amount'),
                //'transactions.final_total as gross_amount',
                DB::raw('IFNULL(sum(transaction_sell_lines.unit_price_before_discount*transaction_sell_lines.quantity),0) as subtotal'),
                DB::raw('IFNULL(sum((transaction_sell_lines.unit_price_before_discount*tax_rates.amount/100)*transaction_sell_lines.quantity),0) as total_tax'),
                DB::raw('IFNULL(sum(transaction_sell_lines.quantity),0) as sell_qty'),
                'v.default_purchase_price as default_purchase_price',
                DB::raw("(SELECT purchase_price FROM `purchase_lines` WHERE products.`business_id`=$business_id AND purchase_lines.`product_id`=products.id GROUP BY products.id) as unit_cost_inc_tax"),
                DB::raw("(SELECT ROUND(AVG(purchase_price),2) AS avg_unit_cost_inc_tax FROM `purchase_lines` WHERE products.`business_id`=$business_id AND purchase_lines.`product_id`=products.id GROUP BY products.id) as avg_unit_cost_inc_tax"),
                'v.sell_price_inc_tax as sell_price_inc_tax',
                'v.default_sell_price as default_sell_price',
                'business_locations.id','transactions.invoice_no','transactions.transaction_date','products.name as product_name','products.sku','u.short_name as unit',
                'transaction_sell_lines.unit_price_before_discount','transaction_sell_lines.line_discount_amount','transactions.id as transaction_id'
            );
        return $getSalesData;
    }
}
