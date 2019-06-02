// thanks to Istvan @ http://zourbuth.com/archives/877/how-to-use-wp-color-picker-in-widgets/#comment-64600
// for a solution that doesn't populate the initial widget with an additional non-functioning colour picker

(function($){

	var parent = $('body');
	if ($('body').hasClass('widgets-php')) {
		parent = $('.widget-liquid-right');
	}

	jQuery(document).ready(function($) {
		parent.find('.color-field').wpColorPicker();
	});

	jQuery(document).on('widget-added', function(e, widget) {
		widget.find('.color-field').wpColorPicker();
	});

	jQuery(document).on('widget-updated', function(e, widget) {
		widget.find('.color-field').wpColorPicker();
	});

	jQuery(document).bind('ajaxComplete', function() {
		parent.find('.color-field').wpColorPicker();
	});

})(jQuery);