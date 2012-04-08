/*global jQuery, BfoxAjax */

jQuery(document).ready(function () {
	'use strict';

	/*
	 * Set up DOM elements
	 */
	
	// select.bfox-tool-name should update the Bible tool on value change 
	jQuery('select.bfox-tool-context-updater').live('change', function () {
		return BfoxAjax.refreshSelectorForKeyValue(jQuery(this).attr('data-selector'), 'tool', jQuery(this).val());
	});
});
