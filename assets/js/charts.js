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
		chartForm.find('select.country')
				.on('change', function(){
					var $countrySelect = $(this),																// the select element
						type = $countrySelect.prop('name').match(/^(.*?)Country/)[1] || '',						// the select type (birth/current etc)
						val = $countrySelect.val(),																// the selected country code
						$stateSelect = $countrySelect															// the state select field
													.parents('fieldset:first')
													.find('select.state[name^="' + type + 'State"]'),
						selectOpts = $stateSelect.data('countryOptions') ||										// the original select options
										$stateSelect.data('countryOptions', $stateSelect.children('optgroup'))
													.data('countryOptions'),
						options = val ? selectOpts.filter('[label="' + val + '"]').children('option') : null;	// the options for the chosen country

					// remove any existing states
					$stateSelect.children(':not(:first)').remove();

					if(options && options.length){
						// states found, for the selected country
						// enable the select field
						$stateSelect.prop('disabled', false);
						// append the country's states
						options.clone().appendTo($stateSelect);
					}else{
						// no states found, for the selected country - disable the select field
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