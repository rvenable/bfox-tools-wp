<?php

class BfoxWPBibleToolIframeApi extends BfoxBibleToolIframeApi {
	function echoContentForUrl($url) {
?>
	<div class="bfox-tool-iframe">
		<?php parent::echoContentForUrl($url); ?>
	</div>
<?php
	}
}

?>