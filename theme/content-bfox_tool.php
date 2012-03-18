<?php
/**
 * The template for displaying Bible Tools within the admin interface (ie. on Edit Post screen)
 *
 */

global $bfox;

// TODO: this shouldn't be the main context, but the current context
$context = $bfox->tools->mainContext();
$tool = $context->activeTool();

?>
		<article id="bfox_bible-<?php echo $tool->shortName; ?>" class="bfox_bible">
			<header class="entry-header">
				<h1 class="entry-title"><?php echo $bfox->linkForRefStr($context->ref->get_string()); ?> - <?php echo $tool->longName; ?></h1>
			</header><!-- .entry-header -->

			<nav class='passage-nav'>
				<div class="nav-previous"><?php echo $bfox->linkForRefStr($context->ref->prev_chapter_string()); ?></div>
				<div class="nav-next"><?php echo $bfox->linkForRefStr($context->ref->next_chapter_string()); ?></div>
			</nav><!-- #nav-above -->

			<div class="entry-content">
				<?php $tool->echoContentForRef($context->ref); ?>
			</div><!-- .entry-content -->

			<nav class='passage-nav'>
				<div class="nav-previous"><?php echo $bfox->linkForRefStr($context->ref->prev_chapter_string()); ?></div>
				<div class="nav-next"><?php echo $bfox->linkForRefStr($context->ref->next_chapter_string()); ?></div>
			</nav><!-- #nav-above -->

		</article><!-- #bfox_bible-<?php echo $tool->shortName; ?> -->
