/*global jQuery, BfoxAjax */

jQuery(document).ready(function () {
	'use strict';

	/*
	 * Add BfoxAjax functions
	 */
	
	BfoxAjax.appendUrlWithParamString = function (url, paramString) {
		return url + ((url.indexOf('?') === -1) ? '?' : '&') + paramString;
	};

	BfoxAjax.toolValueChanged = function (id, key, value) {
		var parameters, url, updatingElement, obj;

		obj = { 'id': id };
		obj[key] = value;
		parameters = jQuery.param(obj);

		jQuery('.depends-' + id).each(function () {
			updatingElement = this;
			url = jQuery(updatingElement).attr('data-url');
			url = BfoxAjax.appendUrlWithParamString(url, parameters);

			jQuery.get(url, function (response) {
				jQuery(updatingElement).attr('data-url', response.dataUrl);
				jQuery(updatingElement).html(response.html);
			});
		});

		return false;
	};
	
	BfoxAjax.refValueChanged = function (element) {
		return BfoxAjax.toolValueChanged(jQuery(element).attr('id'), 'ref', jQuery(element).val());
	};

	BfoxAjax.toolNameValueChanged = function (element) {
		return BfoxAjax.toolValueChanged(jQuery(element).attr('id'), 'tool', jQuery(element).val());
	};

	/*
	 * Set up DOM elements
	 */
	
	// input.bfox-tool-ref should update Bible references on keyboard enter
	jQuery('input.bfox-tool-ref').keypress(function (e) {
		if (e.which == 13) {
			BfoxAjax.refValueChanged(this);
			e.preventDefault();
			return false;
		}
	});

	// .bfox-tool-ref-refresh should refresh once after the page loads
	jQuery('.bfox-tool-ref-refresh').each(function () {
		BfoxAjax.refValueChanged(this);
	});
	
	// select.bfox-tool-name should update the Bible tool on value change 
	jQuery('select.bfox-tool-name').change(function () {
		BfoxAjax.toolNameValueChanged(this);
	});
	
	// a.bfox-ref-update links should update the ref value for a specified selector
	jQuery('a.bfox-ref-update').live('click', function () {
		var selector, ref;
		selector = jQuery(this).attr('data-selector');
		ref = jQuery(this).attr('data-ref');
		jQuery(selector).val(ref).each(function () {
			BfoxAjax.refValueChanged(this);
		});
		
		return false;
	});
});
