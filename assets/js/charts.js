/**
 * Author: leelangley
 * Date Created: 29/04/2013 14:59
 */

;(function($, window, document){
	$(window).on('ready', function(){
		var chartForm = $('#chartForm');

		/**
		 * Enable/disable the state drop-down,
		 * depending on the selected country
		 */
		$('#personBirthCountry')
				.on('change', function(){
					var val = $(this).val(),
						$stateSelect = $('#personBirthState');

					if(val.toUpperCase() == 'US'){
						$stateSelect.prop('disabled', false);
					}else{
						$stateSelect.prop('disabled', true);
					}
				})
				.triggerHandler('change');

		/**
		 * Adds datepicker to the relevant fields
		 */
		chartForm.find('input[type=date]').datepicker({
			dateFormat:'dd/mm/yy'
		});
	});
})(jQuery, window, document);