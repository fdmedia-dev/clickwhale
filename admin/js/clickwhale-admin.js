(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	// Link Live Preview

	function padTo2Digits(num) {
		return num.toString().padStart(2, '0');
	}
	
	function formatDate(date) {
	return (
		[
		date.getFullYear(),
		padTo2Digits(date.getMonth() + 1),
		padTo2Digits(date.getDate()),
		].join('-') +
		' ' +
		[
		padTo2Digits(date.getHours()),
		padTo2Digits(date.getMinutes()),
		padTo2Digits(date.getSeconds()),
		].join(':')
	);
	}

	$(document)
		.on('keyup change', '#slug', function(){
			var slug = $(this).val();

			slug = slug.replace(/\s+/g, '-').toLowerCase();
			slug = slug.indexOf('/') == 0 ? slug.substring(1) : slug;
			slug = slug.replace(/\\/g, "/");
			slug = slug.replace(/\/\//g, "/");
			slug = slug.replace(/\/\/\//g, "/");
			slug = slug.replace(/\/$/, '');

			$('#slug__text').find('span').html(slug);
		})
		.on('click', '.slug-input--btn', function(e){
			e.preventDefault();
			
			var $temp = $('<input>'),
				textToCopy = $(this).parent().find('input').val();
				textToCopy = clickwhale_admin.siteurl + '/' + textToCopy;

			$('body').append($temp);
			$temp.val(textToCopy).select();
			document.execCommand("copy");
			$temp.remove();

		})
		.on('submit', '#form_edit_link', function(){
			if($('#created_at').val() === ''){
				$('#created_at').val(formatDate(new Date()));
			}
			$('#updated_at').val(formatDate(new Date()));
			//console.log(formatDate(new Date()));
		});


})( jQuery );
