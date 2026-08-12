/**
 * Persist admin notice dismissal.
 * @link https://github.com/afragen/wp-dismiss-notice
 */
(function ($) {
	$(function () {
		$('div[data-dismissible] button.notice-dismiss').on('click', function (event) {
			event.preventDefault();
			var $this = $(this);
			var attr_value = $this.closest('div[data-dismissible]').attr('data-dismissible').split('-');
			var dismissible_length = attr_value.pop();
			var option_name = attr_value.join('-');
			$.post(window.wp_dismiss_notice.ajaxurl, {
				'action': 'wp_dismiss_notice',
				'option_name': option_name,
				'dismissible_length': dismissible_length,
				'nonce': window.wp_dismiss_notice.nonce
			});
			$this.closest('div[data-dismissible]').hide('slow');
		});
	});
}(jQuery));
