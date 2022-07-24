<?php
/**
 * Template to displaying single portfolio
 * This template be registered in this plugin
 *
 * @since 1.0
 */

get_header();
$block_style  = get_theme_mod( 'goso_blockquote_style', 'style-1' );
$single_style = goso_portfolio_single_settings( 'goso_single_portfolio_style', 'goso_portfolio_content_style', 'style-1' );

$single_share_style     = get_theme_mod( 'goso_single_portfolio_social_share_style', 'style-1' );
$GLOBALS['share_style'] = $single_share_style;
$sidebar_enable         = get_theme_mod( 'goso_portfolio_single_enable_sidebar' );
$both_sidebar           = get_theme_mod( 'goso_portfolio_single_enable_2sidebar' );

$sidebar_enable_inv = get_post_meta( get_the_ID(), 'goso_portfolio_content_width', true );

if ( in_array( $sidebar_enable_inv, array( 'left-sidebar', 'right-sidebar', 'both-sidebar' ) ) ) {
	$sidebar_enable = true;
}

if ( 'both-sidebar' == $sidebar_enable_inv ) {
	$both_sidebar = true;
}

$left_sidebar     = get_theme_mod( 'goso_portfolio_single_enable_left_sidebar' );
$sidebar_position = 'right-sidebar';

if ( $left_sidebar || 'left-sidebar' == $sidebar_enable_inv ) {
	$sidebar_position = 'left-sidebar';
}

if ( $both_sidebar ) {
	$sidebar_position = 'two-sidebar';
}

if ( 'style-3' == $single_style && ( 'left-sidebar' == $sidebar_position || 'right-sidebar' == $sidebar_position ) ) {
	$GLOBALS['right-sidebar'] = true;
}

if ( 'style-3' == $single_style && 'two-sidebar' == $sidebar_position ) {
	$GLOBALS['left-sidebar'] = true;
}


$hide_featured_img = get_theme_mod( 'goso_portfolio_hide_featured_image_single', false );
$hide_sharebox     = get_theme_mod( 'goso_portfolio_share_box', false );
$hide_nextprev_nav = get_theme_mod( 'goso_portfolio_next_prev_project', false );
$related_project   = get_theme_mod( 'goso_portfolio_related_project', false );

$portfolio_meta    = get_post_meta( get_the_ID(), 'portfolio_options_meta', true );
$hide_featured_img = isset( $portfolio_meta['goso_portfolio_hide_featured_img'] ) && ! empty( $portfolio_meta['goso_portfolio_hide_featured_img'] ) ? goso_portfolio_option2logic( $portfolio_meta['goso_portfolio_hide_featured_img'], true ) : $hide_featured_img;
$hide_sharebox     = isset( $portfolio_meta['goso_portfolio_hide_sharebox'] ) && ! empty( $portfolio_meta['goso_portfolio_hide_sharebox'] ) ? goso_portfolio_option2logic( $portfolio_meta['goso_portfolio_hide_sharebox'], true ) : $hide_sharebox;
$related_project   = isset( $portfolio_meta['goso_portfolio_hide_relared_portfolio'] ) && ! empty( $portfolio_meta['goso_portfolio_hide_relared_portfolio'] ) ? goso_portfolio_option2logic( $portfolio_meta['goso_portfolio_hide_relared_portfolio'] ) : $related_project;
$hide_nextprev_nav = isset( $portfolio_meta['goso_portfolio_hide_nextprev_nav'] ) && ! empty( $portfolio_meta['goso_portfolio_hide_nextprev_nav'] ) ? goso_portfolio_option2logic( $portfolio_meta['goso_portfolio_hide_nextprev_nav'] ) : $hide_nextprev_nav;
$sticky            = false;
if ( 'style-3' == $single_style && ! $sidebar_enable ) {
	$sticky = true;
}
$GLOBALS['share_box'] = $hide_sharebox;
$content_class        = portfolio_meta_content( $single_share_style, ! $hide_sharebox, false, false, false );
?>

<?php if ( ! get_theme_mod( 'goso_disable_breadcrumb' ) ):
	$yoast_breadcrumb = $rm_breadcrumb = '';
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		$yoast_breadcrumb = yoast_breadcrumb( '<div class="container portfolio-single-width-' . $sidebar_enable_inv . ' goso-breadcrumb single-breadcrumb ' . $sidebar_position . '">', '</div>', false );
	}

	if ( function_exists( 'rank_math_get_breadcrumbs' ) ) {
		$rm_breadcrumb = rank_math_get_breadcrumbs( [
			'wrap_before' => '<div class="container portfolio-single-width-' . $sidebar_enable_inv . ' goso-breadcrumb single-breadcrumb ' . $sidebar_position . '"><nav aria-label="breadcrumbs" class="rank-math-breadcrumb">',
			'wrap_after'  => '</nav></div>',
		] );
	}

	if ( $rm_breadcrumb ) {
		echo $rm_breadcrumb;
	} elseif ( $yoast_breadcrumb ) {
		echo $yoast_breadcrumb;
	} else {
		?>

        <div class="container portfolio-single-width-<?php echo esc_attr( $sidebar_enable_inv ); ?> goso-breadcrumb single-breadcrumb <?php echo esc_attr( $sidebar_position ); ?>">
		<span><a class="crumb" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php
			if ( function_exists( 'goso_get_setting' ) ) {
				echo goso_get_setting( 'goso_trans_home' );
			} else {
				esc_html_e( 'Home', 'gosodesign' );
			}
			?>
		</a></span><?php echo( function_exists( 'goso_icon_by_ver' ) ? goso_icon_by_ver( 'fas fa-angle-right' ) : '<i class="fa fa-angle-right"></i>' ); ?>

			<?php
			$id_page = get_theme_mod( 'goso_portfolio_cspage' );

			if ( $id_page && 'publish' == get_post_status( $id_page ) ) {
				echo '<span><a class="crumb" href=' . get_permalink( $id_page ) . '">' . get_the_title( $id_page ) . '</a></span>';
				echo( function_exists( 'goso_icon_by_ver' ) ? goso_icon_by_ver( 'fas fa-angle-right' ) : '<i class="fa fa-angle-right"></i>' );
			}

			$goso_cats         = wp_get_post_terms( get_the_ID(), 'portfolio-category' );
			$wpseo_primary_term = function_exists( 'goso_get_wpseo_primary_term' ) ? goso_get_wpseo_primary_term( 'portfolio-category' ) : '';


			if ( get_theme_mod( 'enable_pri_cat_yoast_seo' ) && $wpseo_primary_term ) {
				echo $wpseo_primary_term;
			} else if ( ! empty( $goso_cats ) ) { ?>
                <span>
				<?php
				echo '<a href="' . get_term_link( $goso_cats[0], 'portfolio-category' ) . '">' . $goso_cats[0]->name . '</a>';
				?>
                </span><?php echo( function_exists( 'goso_icon_by_ver' ) ? goso_icon_by_ver( 'fas fa-angle-right' ) : '<i class="fa fa-angle-right"></i>' ); ?>
			<?php } ?>
            <span><?php the_title(); ?></span>
        </div>
	<?php } endif; ?>

<div class="container <?php echo esc_attr( $content_class ); ?> portfolio-single-width-<?php echo esc_attr( $sidebar_enable_inv ); ?> <?php if ( $sidebar_enable ) : ?>goso_sidebar <?php echo esc_attr( $sidebar_position ); ?><?php endif; ?>">
    <div id="main">
		<?php /* The loop */
		while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php if ( ! $sticky || 'no_content' == $content_class ): ?>
                    <div class="goso-page-header">
                        <h1><?php the_title(); ?></h1>
                    </div>
				<?php endif; ?>

                <div class="post-entry <?php echo 'blockquote-' . $block_style . ' portfolio-' . $single_style; ?>">

					<?php
					if ( $sticky ) {
						?>
						<?php
						portfolio_meta_content( $single_share_style, ! $hide_sharebox, true, true );
					}
					?>


                    <div class="portfolio-page-content <?php if ( $sticky ) : ?>portfolio-sticky-content<?php endif; ?>">
						<?php if ( $sticky ) : ?>
                        <div class="theiaStickySidebar">
							<?php endif; ?>
							<?php /* Thumbnail */
							if ( has_post_thumbnail() && ! $hide_featured_img ) {
								echo '<div class="single-portfolio-thumbnail">';
								the_post_thumbnail( 'goso-full-thumb' );
								echo '</div>';
							}
							?>

							<?php
							if ( 'style-2' == $single_style ) {
								portfolio_meta_content( $single_share_style, ! $hide_sharebox, false, false );
							}
							?>

                            <div class="portfolio-detail">
								<?php the_content(); ?>
                            </div>
							<?php if ( $sticky ) : ?>
                        </div>
					<?php endif; ?>
                    </div>
                </div>


				<?php
				if ( 'style-1' == $single_style || ( 'style-3' == $single_style && wp_is_mobile() && $sidebar_enable ) ) {
					portfolio_meta_content( $single_share_style, ! $hide_sharebox, false, false );
				}
				?>

				<?php
				$portfolio_border_class = ! $related_project ? 'has-border' : 'no-border';
				if ( 'has_content' != portfolio_meta_content( $single_share_style, ! $hide_sharebox, false, false, false ) ) {
					echo '<div class="portfolio-bottom-share ' . $portfolio_border_class . '">';
					echo goso_portfolio_share_html( $single_share_style, ! $hide_sharebox, 'div' );
					echo '</div>';
				} ?>

				<?php if ( $hide_nextprev_nav ) : ?>
                    <div class="post-pagination project-pagination">
						<?php
						$next_text = 'Next Project';
						if ( get_theme_mod( 'goso_portfolio_next_text' ) ): $next_text = do_shortcode( get_theme_mod( 'goso_portfolio_next_text' ) ); endif;
						$prev_text = 'Previous Project';
						if ( get_theme_mod( 'goso_portfolio_prev_text' ) ): $prev_text = do_shortcode( get_theme_mod( 'goso_portfolio_prev_text' ) ); endif;
						?>
                        <div class="prev-post">
							<?php previous_post_link( '%link', $prev_text, $in_same_term = false, $excluded_terms = '', $taxonomy = 'portfolio-category' ); ?>
                        </div>
                        <div class="next-post">
							<?php next_post_link( '%link', $next_text, $in_same_term = false, $excluded_terms = '', $taxonomy = 'portfolio-category' ); ?>
                        </div>
                    </div>
				<?php endif; ?>

				<?php
				goso_relate_projects_carousel( $related_project );
				?>

				<?php if ( get_theme_mod( 'goso_portfolio_enable_comment' ) ) : ?>
					<?php comments_template( '', true ); ?>
				<?php endif; ?>

            </article>
		<?php endwhile; ?>
    </div>

	<?php if ( $sidebar_enable ) : ?>
		<?php get_sidebar(); ?>
		<?php if ( $both_sidebar ) : get_sidebar( 'left' ); endif; ?>
	<?php endif; ?>

</div>

<?php get_footer(); ?>
