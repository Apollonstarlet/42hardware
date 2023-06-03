<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><b>Documents</b></h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @if($getPurchaseDocument->count())
                    <div class="col-sm-4 col-md-4 col-xs-12">
                        <h4>Purchase Documents</h4>
                        @foreach($getPurchaseDocument as $gpd)
                        <div class="col-sm-12 col-md-12 col-xs-12">
                            <div class="text-center"><img class="img-responsive" src="{{url('uploads/documents/' . $gpd->document)}}" /></div>
                            <div class="text-center mb-2"><a href="{{url('uploads/documents/' . $gpd->document)}}" class="btn btn-success" download=""><i class="fa fa-download"></i> Download</a></div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if($getPurchasePaymentDocument->count())
                    <div class="@if($getPurchaseDocument->count()) col-sm-8 col-md-8 col-xs-12 @else col-sm-12 col-md-12 col-xs-12 @endif">
                        <h4>Payment Documents</h4>
                        @foreach($getPurchasePaymentDocument as $gppd)
                        <div class="@if($getPurchaseDocument->count()) col-sm-6 col-md-6 col-xs-12 @else col-sm-4 col-md-4 col-xs-12 @endif">
                            <div class="text-center mb-2"><img class="img-responsive" src="{{url('uploads/documents/' . $gppd->document)}}" /></div>
                            <div class="text-center"><a href="{{url('uploads/documents/' . $gppd->document)}}" class="btn btn-success" download=""><i class="fa fa-download"></i> Download</a></div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->