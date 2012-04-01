<?php
/**
 * The template for displaying Bible Tools within the admin interface (ie. on Edit Post screen)
 *
 */

global $bfox;

$refContext = $bfox->refs->currentContext();
$toolContext = $bfox->tools->currentContext();

?>
		<form method="get" id="bible-form" action="<?php echo esc_url( $bfox->tools->urlForToolName() ); ?>" class="bfox-tool-form">
			<input type="text" id="bfox-tool-ref-global" class="field <?php echo $refContext->updaterClass ?>" <?php echo $refContext->dependencySelectorAttribute(); ?> name="ref" placeholder="<?php esc_attr_e( 'Search' ); ?>" value="<?php echo $refContext->ref->get_string(BibleMeta::name_short) ?>" />
			<select class="bfox-tool-name <?php echo $toolContext->updaterClass ?>" id="bfox-tool-name-main" <?php echo $toolContext->dependencySelectorAttribute(); ?> name="tool">
				<?php echo $toolContext->selectOptions(); ?>
			</select>
			<input type="submit" class="submit" value="<?php esc_attr_e( 'Go' ); ?>" />
		</form>
