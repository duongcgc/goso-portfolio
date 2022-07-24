<?php
/**
 * Project navigation in single portfolio
 * Create next and prev button to next and prev for projects
 *
 * @package Wordpress
 * @since 1.0
 */
?>
<div class="post-pagination project-pagination">
	<div class="prev-post">
		<?php previous_posts_link(); ?>
	</div>
	<div class="next-post">
		<?php next_posts_link(); ?>
	</div>
</div>