jQuery(document).ready(function($){

	$(document).on('keyup', '.addon-quantity', function( keyup_event ){
				
		calculate_total();

	});

	function calculate_addon_total( addon_row ) {

		var addon_quantity = $(addon_row).find('.addon-quantity').val();
		var addon_price = $(addon_row).find('.addon-price').data('price');
		var addon_total = addon_quantity * addon_price;

		$(addon_row).find('.addon-price').text( '$' + addon_total.toFixed(2) );

		return addon_total;

	}

	function calculate_total() {

		var total = 0;

		$('.addon-quantity').each(function(){

			var addon_row = $(this).closest('tr');
			var addon_total = calculate_addon_total( addon_row );

			total += addon_total;

		});

		$('#appointment_total_cost').val( total );
		$('#total_price_display').text( '$' + total.toFixed(2) );

	}

	calculate_total();

});