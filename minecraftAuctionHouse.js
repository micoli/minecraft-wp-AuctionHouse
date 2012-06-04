jQuery(document).ready(function($) {
$.fn.dataTableExt.oApi.fnReloadAjax = function(oSettings,sNewSource, fnCallback, bStandingRedraw) {
		if (typeof sNewSource != 'undefined' && sNewSource != null) {
			oSettings.sAjaxSource = sNewSource;
		}
		this.oApi._fnProcessingDisplay(oSettings, true);
		var that = this;
		var iStart = oSettings._iDisplayStart;
		var aData = [];
		
		this.oApi._fnServerParams(oSettings, aData);
		
		oSettings
		.fnServerData(
				oSettings.sAjaxSource,
				aData,
				function(json) {
					/*
		 * Clear the old information from
		 * the table
		 */
		that.oApi._fnClearTable(oSettings);
	
		/*
		 * Got the data - add it to the
		 * table
		 */
		var aData = (oSettings.sAjaxDataProp !== "") ? that.oApi
				._fnGetObjectDataFn(
						oSettings.sAjaxDataProp)
				(json)
				: json;
	
		for ( var i = 0; i < aData.length; i++) {
			that.oApi._fnAddData(oSettings,
					aData[i]);
		}
	
		oSettings.aiDisplay = oSettings.aiDisplayMaster
				.slice();
		that.fnDraw();
	
		if (typeof bStandingRedraw != 'undefined'
				&& bStandingRedraw === true) {
			oSettings._iDisplayStart = iStart;
			that.fnDraw(false);
		}
	
		that.oApi._fnProcessingDisplay(
				oSettings, false);
	
		/*
		 * Callback user function - for
		 * event handlers etc
		 */
		if (typeof fnCallback == 'function'
					&& fnCallback != null) {
				fnCallback(oSettings);
			}
		}, oSettings);
	}
	$('#auction_house').dataTable({
		"bProcessing": true,
		//"bJQueryUI": true,
		"bAutoWidth": false,
		"bServerSide": true,
		debug:false,
		"aoColumnDefs": [{
			"sName"		:"Item",
			"sWidth"	: "20px",
			"aTargets"	: [ 0 ],
			"bUseRendered": false,
			"sDefaultContent": "",
			"fnRender"	: function ( source, type,val ) {
				return source.aData.img + '&nbsp;' + source.aData.itemName;
			}
		},{
			"sName"		: "Seller",
			"aTargets"	: [ 1 ],
			"sDefaultContent": "",
			"bUseRendered": false,
			"mDataProp"	: "seller"
		},{
			"sName"		: "Buyer",
			"aTargets"	: [ 2 ],
			"sDefaultContent": "",
			"bUseRendered": false,
			"mDataProp"	: "buyer"
		},{
			"sName"		: "SalePrice",
			"aTargets"	: [ 3 ],
			"bUseRendered": false,
			"sDefaultContent": "",
			"mDataProp"	: "remainingSalePrice"
		},{
			"sName"		: "Quantity",
			"aTargets"	: [ 4 ],
			"bUseRendered": false,
			"sDefaultContent": "",
			"fnRender"	: function ( source, type, val ) {
				var str = "";
				if(source.oSettings.oInstance.DataTable.userData.user && source.aData.remainingQuantity){
					str=str+ '<select class="select_quantity" style="width:90px;text-align:right;">';
					for(i=(source.aData.splitable?1:source.aData.remainingQuantity);i<=source.aData.remainingQuantity;i++){
						str=str+'<option value="'+i+'" '+(i==source.aData.remainingQuantity?'selected':'')+'>'+i+' ('+(i*source.aData.stackPrice)+')</option>';
					}
					str=str+"</select>";
				}else{
					str=str+source.aData.remainingQuantity;
				}
				return str;
			}
		},{
			"sName"		: "Buy",
			"aTargets"	: [ 5 ],
			"bUseRendered": false,
			"sDefaultContent": "",
			"fnRender"	: function( source, type, val){
				var str="";
				if(source.oSettings.oInstance.DataTable.userData.user){
					str=str+'<input type="button" class="buy_qty_button" value="buy">';
				}
				return str;
			}
		},{
			"sName"		: "MinAuction",
			"aTargets"	: [ 6 ],
			"bUseRendered": false,
			"sDefaultContent": "",
			"fnRender"	: function ( source, type, val ) {
				var str ='';
				if(source.oSettings.oInstance.DataTable.userData.user){
					str = str + '<form class="auction_form" >';
					str = str + '<input type="text" value="'+(source.aData.remainingBidPrice+1)+'" name="auction_price" id="auction_price_'+source.aData.id+'" class="auction_price validate[custom[integer],min['+source.aData.minPriceAuction+'],max['+source.aData.minPriceSale+']]" style="text-align: right;width:50px">';
					str = str + '<input type="button" class="bid_button" value="bid">';
					str = str + '</form>';
				}
				return str;
			} 
		},{
			"sName"		: "Delai",
			"aTargets"	: [ 7 ],
			"bUseRendered": false,
			"sDefaultContent": "",
			"fnRender"	: function ( source, type, val ) {
				var Milliseconds = new Date().getTime()-new Date(source.aData.expirationDate).getTime();
				Hours = Math.round(Milliseconds / (1000*60*60));
				Minutes = Math.round((Milliseconds % (1000*60*60)) / (1000*60));
				Seconds = Math.round(((Milliseconds % (1000*60*60)) % (1000*60)) / 1000);
				return Hours+':'+(Minutes<10?'0':'')+Minutes+':'+(Seconds<10?'0':'')+Seconds;
			}
		}],
		"fnCalcPartialPrice" : function (nRow,aData){
			var price = aData.stackPrice*$('.select_quantity',nRow).val();
			//$('.each_price',nRow).html(aData.remainingQuantity==0?'-':Math.ceil(aData.remainingSalePrice/aData.remainingQuantity));
			$('.partial_price',nRow).html(price?price:'-');
			if(!this.DataTable.userData.user || this.DataTable.userData.balance<price){
				$('.buy_qty_button',nRow).attr("disabled", "disabled").css('text-decoration','line-through');
				$('.partial_price',nRow ).attr("disabled", "disabled").css('text-decoration','line-through');
			}else{
				$('.buy_qty_button',nRow).removeAttr("disabled").css('text-decoration','none');
				$('.partial_price',nRow ).removeAttr("disabled").css('text-decoration','none');
			}

		},
		"fnErrorPlacement"	: function(error, element){
			var elem	= $(element),
				corners	= ['left center', 'right center'],
				flipIt	= elem.parents('span.right').length > 0;
			if(!error.is(':empty')) {
				elem.filter(':not(.valid)').qtip({
					overwrite	: false,
					content		: error,
					position	: {
						my			: corners[ flipIt ? 0 : 1 ],
						at			: corners[ flipIt ? 1 : 0 ],
						viewport	: $(window)
					},
					show	: {
						event	: false,
						ready	: true
					},
					hide	: false,
					style	: {
						classes		: 'ui-tooltip-red'
					}
				})
				.qtip('option', 'content.text', error);
			}else {
				elem.qtip('destroy'); 
			}
		},
		"fnCreatedRow": function( nRow, aData, iDataIndex ) {
			var that=this;
			that.DataTable.settings[0].oInit.fnCalcPartialPrice.call(that,nRow,aData);
			if(aData.minPriceAuction>that.DataTable.userData.balance){
				$('.bid_button',$(nRow)).attr("disabled", "disabled").css('text-decoration','line-through');
				$('.auction_price',$(nRow)).attr("disabled", "disabled").css('text-decoration','line-through');
			}else{
				$('.auction_form',$(nRow)).validate({
					errorClass	: "errormessage",
					onkeyup		: false,
					errorClass	: 'error',
					validClass	: 'valid',
					rules		: {
						auction_price	: {
							required		: true, 
							number			: true,
							min				: aData.remainingBidPrice+1,
							max				: that.DataTable.userData.balance
						},
					},
					errorPlacement	: that.DataTable.settings[0].oInit.fnErrorPlacement,
					success			: $.noop, // Odd workaround for errorPlacement not firing!
				});
			}
			$('.select_quantity',nRow).change(function(){
				that.DataTable.settings[0].oInit.fnCalcPartialPrice.call(that,nRow,aData);
			});
			$('.buy_qty_button',nRow).click(function(){
				//console.log($("#auction_house_fake_form").validationEngine('validateField','auction_price_'+aData.id));
			});
			$('.bid_button',nRow).click(function(){
				//if($('#auction_house_fake_form').validate().element('#auction_price_'+aData.id )){
				$.ajax({
					'dataType'	: 'json',
					'type'		: 'POST',
					'context'	: that,
					'url'		: (window['wp_ajax_url']==undefined?null:wp_ajax_url)+'?action=auction_house_bid',
					'data'		: {
						auctionId	: aData.id,
						price		: $('.auction_price',$(nRow)).val(),
						quantity	: $('.select_quantity',nRow).val()
					},
					'success'	: function(data,result,arg){
						console.log(data,result,arg);
						this.fnClearTable( 0 );
						this.fnDraw();
					}
				});
				//}
				//console.log($("#auction_house_fake_form").validationEngine('validateField','auction_price_'+aData.id));
			});
		},
		'fnServerData' : function(sSource, aoData, fnCallback ){
			var that=this;
			$.ajax({
				'dataType': 'json',
				'type': 'POST',
				'url': sSource,
				'context'	: that,
				'data': aoData,
				'success': function(data,result,arg){
					that.DataTable.userData={};
					that.DataTable.userData.user    = data.currentUser;
					that.DataTable.userData.balance = data.userBalance;
					$('.currentBalance').html(that.DataTable.userData.balance);
					fnCallback({
						aaData:data.auctionList
					},result,arg)
				}
			});
		},

		"sAjaxSource": (window['wp_ajax_url']==undefined?null:wp_ajax_url)+'?action=auction_house_list'
	});
});