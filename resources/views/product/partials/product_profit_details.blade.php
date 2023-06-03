<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title col-sm-10 col-md-10"><b>@lang('product.product_name'):</b> {{$getProductDetails->name}}</h4>
            <div class="col-sm-2 col-md-2 invoice-col">
      				<div class="thumbnail">
      					<img src="{{$getProductDetails->image_url}}" alt="Product image">
      				</div>
      			</div>
        </div>
        <?php
        $totalquantity = 0;
        $totalgross = 0;
        $totaltax = 0;
        ?>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered " id="product_profit_detail_report">
                            <thead>
                                <tr style="background-color: #706db1;color:#fff;">
                                    <th>Product Name</th>
                                    <th>Date</th>
                                    <th>Invoice</th>
                                    <th>SKU</th>
                                    <th>Category Name</th>
                                    <th>Sub Category Name</th>
                                    <th>Sell Price Inc Tax</th>
                                    <th>Quantity</th>
                                    <th>Tax</th>
                                    <th>Sell Price Without Tax</th>
                                    <th>Avg. Unit Cost without  Tax</th>
                                    <th>Total Gross Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                <?php $tax = round($product->tax);  
                                $base_price = round($product->unit_price_inc_tax + $product->unit_price_inc_tax * $tax/100);
                                $avg_unit_cost_inc_tax = $product->default_purchase_price;
                                if ($product->avg_unit_cost_inc_tax) {
                                    $avg_unit_cost_inc_tax = $product->avg_unit_cost_inc_tax;
                                }
                                
                                ?>
                                <tr class="
                                    @if($base_price < $product->sell_price_inc_tax and $base_price < $product->default_sell_price) pbg-red
                                    @elseif($base_price < $product->sell_price_inc_tax) pbg-blue
                                    @elseif($base_price > $product->sell_price_inc_tax) pbg-green 
                                    @endif
                                    ">
                                    <td>{{$product->product}}</td>
                                    <td>{{@format_date($product->transaction_date)}}</td>
                                    <td><a data-href="{{action('SellController@show', [$product->transaction_id])}}" href="#" data-container=".view_modal" class="btn-modal">{{$product->invoice_no}}</a></td>
                                    <td>{{$product->sku}}</td>
                                    <td>{{$product->category_name}}</td>
                                    <td>{{$product->sub_category_name}}</td>
                                    <td><span class="display_currency" data-currency_symbol='true' data-orig-value="{{$base_price}}">{{$base_price}}</span></td>
                                    <td><span class="display_currency popup_total_quantity" data-orig-value="{{$product->quantity}}">{{$product->quantity}} {{$product->unit}}</span></td>
                                    <td class="popup_total_tax display_currency" data-currency_symbol='true' data-orig-value="{{$product->total_tax}}">{{$product->total_tax}}</td>
                                    <td><span class="display_currency" data-currency_symbol='true' data-orig-value="{{$product->unit_price}}">{{$product->unit_price}}</span></td>
                                    <td><span class="display_currency" data-currency_symbol='true' data-orig-value="{{$avg_unit_cost_inc_tax}}">{{$avg_unit_cost_inc_tax}}</span></td>
                                    <td><span class="display_currency popup_total_gross" data-currency_symbol='true' data-orig-value="{{$product->gross_amount}}">{{$product->gross_amount}}</span></td>
                                </tr>
                                @php
                                $totalquantity +=$product->quantity;
                                $totalgross +=round($product->quantity*$base_price);
                                $totaltax +=$product->quantity * $product->unit_price_inc_tax * $tax/100;
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray font-17 text-center">
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th><span class="display_currency" id="popup_footer_total_quantity"></span></th>
                                    <th><span class="display_currency" data-currency_symbol='true' id="popup_footer_total_tax"></span></th>
                                    <th></th>
                                    <th></th>
                                    <th><span class="display_currency" data-currency_symbol='true' id="popup_footer_total_gross"></span></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div>
                        <span><span class="heighlightround" style="background-color: #47f10d;"></span>green > baseprice</span><br>
                        <span><span class="heighlightround" style="background-color: #f1948a;"></span>Red < Base Price and Purchase Cost</span><br>
                        <span><span class="heighlightround" style="background-color: #5dade2;"></span>blue < base price</span>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->