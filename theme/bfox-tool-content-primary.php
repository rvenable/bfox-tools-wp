<?php
/**
 * The template for displaying Bible Tools within the admin interface (ie. on Edit Post screen)
 *
 */

global $bfox;

$refContext = $bfox->refs->currentContext();
$toolContext = $bfox->tools->currentContext();
$tool = $toolContext->activeTool();

?>
		<article id="bfox_bible-<?php echo $tool->shortName; ?>" class="bfox_bible">
			<header class="entry-header">
				<h1 class="entry-title"><?php echo $bfox->refs->link($refContext->ref); ?> - <?php echo $tool->longName; ?></h1>
			</header><!-- .entry-header -->

			<nav class='passage-nav'>
				<div class="nav-previous"><?php echo $bfox->refs->link($refContext->ref->prev_chapter_string()); ?></div>
				<div class="nav-next"><?php echo $bfox->refs->link($refContext->ref->next_chapter_string()); ?></div>
			</nav><!-- #nav-above -->

			<div class="entry-content">
				<?php $tool->echoContentForRef($refContext->ref); ?>
			</div><!-- .entry-content -->

			<nav class='passage-nav'>
				<div class="nav-previous"><?php echo $bfox->refs->link($refContext->ref->prev_chapter_string()); ?></div>
				<div class="nav-next"><?php echo $bfox->refs->link($refContext->ref->next_chapter_string()); ?></div>
			</nav><!-- #nav-above -->

		</article><!-- #bfox_bible-<?php echo $tool->shortName; ?> -->
