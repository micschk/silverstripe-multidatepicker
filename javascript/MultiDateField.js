(function($) {

	$.fn.extend({
		ssMultiDatepicker: function(opts) {
			return $(this).each(function() {
				if($(this).data('multidatepicker')) return; // already applied

				$(this).siblings("button").addClass("ui-icon ui-icon-calendar");
				
				var holder = $(this).parents('.field.date:first'), 
					config = $.extend(opts || {}, $(this).data(), $(this).data('jqueryuiconfig'), {});
				if(!config.showcalendar) return;
	
				if(config.locale && $.datepicker.regional[config.locale]) {
					config = $.extend(config, $.datepicker.regional[config.locale], {});
				}

				if(config.min) config.minDate = $.datepicker.parseDate('yy-mm-dd', config.min);
				if(config.max) config.maxDate = $.datepicker.parseDate('yy-mm-dd', config.max);
	
				// Initialize and open a datepicker 
				// live() doesn't have "onmatch", and jQuery.entwine is a bit too heavyweight for this, so we need to do this onclick.
				config.dateFormat = config.jquerydateformat;
				$(this).multiDatesPicker(config);
				
				// set datepicker to keep track
				$(this).data('multidatepicker','multidatepicker');
			});
		}
	});

	$(document).on("click", ".field.multidate input.text,.fieldholder-small input.text.multidate", function() {
		$(this).ssMultiDatepicker();

		if($(this).data('multidatepicker')) {
			$(this).datepicker('show'); // use subclassed datepicker-show method
		}
	});
}(jQuery));
