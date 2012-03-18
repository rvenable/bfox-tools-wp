<?php
/**
 * The template for displaying Bible Tool Archive pages.
 *
 * Usually used to create a Bible reader with access to all of the Bible Tools.
 *
 */

global $bfox;

/* For BuddyPress, we actually reuse the activity directory theme files */
if (defined('BP_VERSION') && bp_is_active('activity')) {
	if (locate_template(array('activity/index.php'))) {
		$bfox->tools->loadTemplate('activity/index-bfox_tool');
		exit;
	}
}

get_header(); ?>

<section id="primary">
	<div id="content" role="main">

	<?php $bfox->pushRefLinker($bfox->tools->linkerForSelector('#bfox-tool-ref-global')); // Bible links update #bfox-tool-ref-global ?>
	<?php $context = $bfox->tools->mainContext(); ?>

	<?php if ( $context->hasTools() ) : ?>

		<header class="page-header">
			<h1 class="page-title">
				<?php _e('Bible Tools'); ?>
			</h1>
		</header>

		<form method="get" id="bible-form" action="<?php echo esc_url( $bfox->tools->urlForToolName() ); ?>" class="bfox-tool-form">
			<input type="text" id="bfox-tool-ref-global" class="field bfox-tool-ref" name="ref" placeholder="<?php esc_attr_e( 'Search' ); ?>" value="<?php echo $context->ref->get_string(BibleMeta::name_short) ?>" />
			<select class="bfox-tool-name" id="bfox-tool-name-main" name="tool"><?php echo $context->selectOptions(); ?></select>
			<input type="submit" class="submit" value="<?php esc_attr_e( 'Go' ); ?>" />
		</form>

		<div class="depends-bfox-tool-ref-global depends-bfox-tool-name-main" data-url="<?php echo $context->ajaxUrl(); ?>">
		<?php $bfox->tools->loadTemplate('content-bfox_tool'); ?>
		</div>

	<?php else : ?>

		<article id="post-0" class="post no-results not-found">
			<header class="entry-header">
				<h1 class="entry-title"><?php _e( 'Bible Tool Not Found' ); ?></h1>
			</header><!-- .entry-header -->

			<div class="entry-content">
				<p><?php _e( 'Apologies, but the Bible Tool was not found.' ); ?></p>
			</div><!-- .entry-content -->
		</article><!-- #post-0 -->

	<?php endif; ?>

	<?php $bfox->popLinker(); // #bfox-tool-ref-global ?>

	</div><!-- #content -->
</section><!-- #primary -->


<?php get_sidebar(); ?>
<?php get_footer(); ?>