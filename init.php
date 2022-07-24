<?php
/*
Plugin Name: Goso Portfolio
Plugin URI: http://gosodesign.net/
Description: Portfolio Plugin for Authow theme.
Version: 3.1
Author: GosoDesign
Author URI: http://themeforest.net/user/gosodesign?ref=gosodesign
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define
 */
define( 'PENCI_PORTFOLIO_DIR', plugin_dir_path( __FILE__ ) );
define( 'PENCI_PORTFOLIO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main class for Vancouver Portfolio
 * Add this plugin to Vancouver theme, you'll portfolio
 * Don't support when you use this plugin to another theme
 *
 * @author GosoDesign ( http://gosodesign.com/ )
 * @since 1.0
 */
if ( ! class_exists( 'Goso_Portfolio' ) ):

	class Goso_Portfolio {

		/**
		 * A reference to an instance of this class.
		 */
		private static $instance;


		/**
		 * Returns an instance of this class.
		 */
		public static function get_instance() {

			if ( null == self::$instance ) {
				self::$instance = new Goso_Portfolio();
			}

			return self::$instance;

		}

		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		private function __construct() {

			// Include main shortcode for portfolio
			include_once( 'inc/metabox.php' );
			include_once( 'inc/portfolio.php' );

			// load plugin text domain for translations
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

			// Register Portfolio Post Type
			add_action( 'init', array( $this, 'register_portfolio_post_type' ) );

			// Register Portfolio Category
			add_action( 'init', array( $this, 'register_portfolio_category' ) );

			// Override Single Portfolio Template
			add_filter( 'single_template', array( $this, 'register_portfolio_single' ) );

			// Override Categories Portfolio Template
			add_filter( 'template_include', array( $this, 'register_portfolio_categories' ) );

			// Custom Posts Per Page on Portfolio Categories
			add_action( 'pre_get_posts', array( $this, 'portfolio_custom_posts_per_page' ) );

			add_action( 'wp_head', array( $this, 'goso_global_js' ), 10 );

			add_action( 'admin_enqueue_scripts', array( $this, 'goso_load_admin_metabox_style' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'goso_load_portfolio_style' ), 90 );
			add_action( 'authow_theme/custom_css', array( $this, 'single_portfolio_custom_style' ) );
			add_action( 'template_redirect', array( $this, 'portfolio_archive_page' ) );
		}

		public function portfolio_archive_page() {
			$id_page = get_theme_mod( 'goso_portfolio_cspage' );
			if ( $id_page && is_post_type_archive( 'portfolio' ) ) {
				wp_redirect( get_permalink( $id_page ), 301 );
				exit();
			}
		}

		/**
		 * Add admin meta box style
		 */
		public function goso_load_admin_metabox_style() {
			$screen = get_current_screen();
			if ( $screen->id == 'portfolio' ) {
				wp_enqueue_style( 'goso_portfolio_meta_box_styles', plugin_dir_url( __FILE__ ) . 'css/admin.css' );
			}
		}

		/**
		 * Add admin meta box style
		 */
		public function goso_load_portfolio_style() {
			wp_enqueue_script( 'goso_portfolio_extra', plugin_dir_url( __FILE__ ) . 'js/goso-portfolio.js', array( 'jquery' ), '1.0', true );
		}

		function goso_global_js() {
			$output = '<script>var portfolioDataJs = portfolioDataJs || [];</script>';
			echo $output;
		}

		/**
		 * Transition ready
		 *
		 * @access public
		 * @return void
		 * @since  1.0
		 */
		public function load_text_domain() {
			load_plugin_textdomain( 'gosodesign', false, PENCI_PORTFOLIO_DIR . '/languages/' );
		}

		/**
		 * Register Portfolio Post Type
		 */
		public function register_portfolio_post_type() {
			$labels = array(
				'name'               => _x( 'Portfolio', 'post type general name', 'gosodesign' ),
				'singular_name'      => _x( 'Portfolio', 'post type singular name', 'gosodesign' ),
				'add_new'            => __( 'Add New', 'gosodesign' ),
				'add_new_item'       => __( 'Add New Project', 'gosodesign' ),
				'edit_item'          => __( 'Edit Project', 'gosodesign' ),
				'new_item'           => __( 'New Project', 'gosodesign' ),
				'all_items'          => __( 'All Projects', 'gosodesign' ),
				'view_item'          => __( 'View Portfolio', 'gosodesign' ),
				'search_items'       => __( 'Search Portfolio', 'gosodesign' ),
				'not_found'          => __( 'No projects found', 'gosodesign' ),
				'not_found_in_trash' => __( 'No projects found in Trash', 'gosodesign' ),
				'parent_item_colon'  => '',
				'menu_name'          => _x( 'Portfolio', 'post type general name', 'gosodesign' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => self::get_slug(),
				'rewrite'            => array( 'slug' => self::get_slug() ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
			);

			if ( ! get_theme_mod( 'goso_portfolio_classic_editor' ) ) {
				$args['show_in_rest'] = true;
			}

			register_post_type( 'portfolio', $args );
		}

		/**
		 * Register Portfolio Categories
		 */
		public function register_portfolio_category() {
			$labels = array(
				'name'              => _x( 'Portfolio Categories', 'taxonomy general name', 'gosodesign' ),
				'singular_name'     => _x( 'Portfolio Category', 'taxonomy singular name', 'gosodesign' ),
				'search_items'      => __( 'Search Portfolio Categories', 'gosodesign' ),
				'all_items'         => __( 'All Portfolio Categories', 'gosodesign' ),
				'parent_item'       => __( 'Parent Portfolio Category', 'gosodesign' ),
				'parent_item_colon' => __( 'Parent Portfolio Category:', 'gosodesign' ),
				'edit_item'         => __( 'Edit Portfolio Category', 'gosodesign' ),
				'update_item'       => __( 'Update Portfolio Category', 'gosodesign' ),
				'add_new_item'      => __( 'Add New Portfolio Category', 'gosodesign' ),
				'new_item_name'     => __( 'New Portfolio Category Name', 'gosodesign' ),
				'menu_name'         => __( 'Portfolio Categories', 'gosodesign' )
			);

			$args = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => self::get_slug_tax() )
			);

			register_taxonomy( 'portfolio-category', array( 'portfolio' ), $args );
		}

		/**
		 * Register single portfolio template
		 */
		public function register_portfolio_single( $single_template ) {
			global $post;
			if ( $post->post_type == 'portfolio' ) {
				$single_template = dirname( __FILE__ ) . '/inc/single-portfolio.php';
			}

			return $single_template;
		}

		/**
		 *    Register categories portfolio template
		 */
		public function register_portfolio_categories( $template ) {
			if ( is_tax( 'portfolio-category' ) ) {
				$template = dirname( __FILE__ ) . '/inc/taxonomy-portfolio-category.php';
			}

			return $template;
		}

		/**
		 * Custom posts per page portfolio categories listing
		 */
		public function portfolio_custom_posts_per_page( $query ) {
			if ( is_tax( 'portfolio-category' ) && $query->is_main_query() ) {
				$numpost = get_theme_mod( 'goso_portfolio_cat_showposts' );
				if ( ! isset ( $numpost ) || empty( $numpost ) ): $numpost = '12'; endif;
				$query->set( 'posts_per_page', $numpost );
			}
		}

		public static function get_image_ratio( $postid, $image_thumb, $padding = false, $echo = true ) {
			$ratio  = '66.6666667';
			$output = '';

			if ( has_post_thumbnail( $postid ) ) {
				$image      = get_the_post_thumbnail( $postid, $image_thumb );
				$img_url    = get_the_post_thumbnail_url( $postid, $image_thumb );
				$image_type = substr( $img_url, - 4 );
				if ( '.gif' == $image_type ) {
					$image   = get_the_post_thumbnail( $postid, 'full' );
					$img_url = get_the_post_thumbnail_url( $postid, 'full' );
				}
			} else {
				$image   = '<img src="' . PENCI_PORTFOLIO_URL . '/images/no-thumbnail.jpg" alt="' . __( "No Thumbnail", "gosodesign" ) . '" />';
				$img_url = PENCI_PORTFOLIO_URL . '/images/no-thumbnail.jpg';
			}

			if ( preg_match_all( '#(width|height)=(\'|")?(?<dimensions>[0-9]+)(\'|")?#i', $image, $image_dis ) && 2 == count( (array) $image_dis['dimensions'] ) ) {
				$ratio = self::get_pre_ratio( $image_dis['dimensions'][0], $image_dis['dimensions'][1] );
			}

			if ( $padding && $ratio ) {
				$ratio = $padding;
			}

			$output = '<span class="goso-image-placeholder goso-lazy" style="padding-bottom:' . $ratio . '%;" data-bgset="' . $img_url . '"></span>';

			if ( ! $echo ) {
				return $output;
			}

			echo $output;
		}

		public static function get_pre_ratio( $width, $height ) {
			return number_format( $height / $width * 100, 8 );
		}

		public static function get_slug() {
			return get_theme_mod( 'goso_pfl_custom_slug' ) ? get_theme_mod( 'goso_pfl_custom_slug' ) : 'portfolio';
		}

		public static function get_slug_tax() {
			return get_theme_mod( 'goso_pfl_custom_catslug' ) ? get_theme_mod( 'goso_pfl_custom_catslug' ) : 'portfolio-category';
		}

		public function single_portfolio_custom_style() {
			$settings_color = array(
				'goso_portfolio_single_title_color'                      => array(
					'color' => 'body.single-portfolio .goso-page-header h1'
				),
				'goso_portfolio_single_text_color'                       => array(
					'color' => 'body.single-portfolio .post-entry p, body.single-portfolio .goso-shortdesc, body.single-portfolio .wpb_text_column p'
				),
				'goso_portfolio_single_meta_label_color'                 => array(
					'color' => 'ul.portfolio-meta-lists span.title'
				),
				'goso_portfolio_single_meta_value_color'                 => array(
					'color' => 'ul.portfolio-meta-lists span.value'
				),
				'goso_portfolio_single_border_color'                     => array(
					'border-color' => '.portfolio-share-box > span,body.single-portfolio .post-pagination,body.single-portfolio .post-related'
				),
				'goso_portfolio_single_relate_title_color'               => array(
					'color' => '.portfolio-releated-area .item-related h3 a',
				),
				'goso_portfolio_single_relate_title_hover_color'         => array(
					'color' => '.portfolio-releated-area .item-related h3 a:hover',
				),
				'goso_portfolio_single_relate_cat_color'                 => array(
					'color' => '.portfolio-releated-area .item-related .portfolio-cat a',
				),
				'goso_portfolio_single_relate_cat_hover_color'           => array(
					'color' => '.portfolio-releated-area .item-related .portfolio-cat a:hover',
				),
				'goso_portfolio_single_text_link_color'                  => array(
					'color' => 'body.single-portfolio .post-entry a, body.single-portfolio .wpb_text_column a'
				),
				'goso_portfolio_single_text_link_hover_color'            => array(
					'color' => 'body.single-portfolio .post-entry a:hover, body.single-portfolio .wpb_text_column a:hover'
				),
				'goso_portfolio_single_carousel_background_color'        => array(
					'background-color' => '.goso-owl-carousel-slider .owl-dot span, .goso-related-carousel .owl-dot span',
				),
				'goso_portfolio_single_carousel_border_color'            => array(
					'border-color' => '.goso-owl-carousel-slider .owl-dot span, .goso-related-carousel .owl-dot span',
				),
				'goso_portfolio_single_carousel_active_background_color' => array(
					'background-color' => '.goso-owl-carousel-slider .owl-dot.active span, .goso-related-carousel .owl-dot.active span',
				),
				'goso_portfolio_single_carousel_active_border_color'     => array(
					'border-color' => '.goso-owl-carousel-slider .owl-dot.active span, .goso-related-carousel .owl-dot.active span',
				),
			);

			$font_size = array(
				'goso_portfolio_single_title_fz'       => array(
					'font-size' => 'body.single-portfolio .goso-page-header h1',
				),
				'goso_portfolio_single_txt_fz'         => array(
					'font-size' => 'body.single-portfolio .post-entry p, body.single-portfolio .goso-shortdesc, body.single-portfolio .wpb_text_column p'
				),
				'goso_portfolio_single_txt_lh_fz'      => array(
					'line-height' => 'body.single-portfolio .post-entry p, body.single-portfolio .goso-shortdesc, body.single-portfolio .wpb_text_column p'
				),
				'goso_portfolio_single_meta_fz'        => array(
					'font-size' => 'body.single-portfolio ul.portfolio-meta-lists span'
				),
				'goso_portfolio_single_nextprev_fz'    => array(
					'font-size' => 'body.single-portfolio .project-pagination a'
				),
				'goso_portfolio_single_related_tt_fz'  => array(
					'font-size' => 'body.single-portfolio .portfolio-releated-area .item-related h3 a'
				),
				'goso_portfolio_single_related_cat_fz' => array(
					'font-size' => 'body.single-portfolio .portfolio-releated-area .item-related .portfolio-cat a'
				),
			);

			if ( is_singular( 'portfolio' ) ) {
				echo $this->css_parse( $settings_color );
				echo $this->css_parse( $font_size );
				echo $this->css_parse( $font_size, true );
			}

		}

		public function css_parse( $css, $mobile = false ) {
			$css_out = $before = $after = '';

			foreach ( $css as $setting => $props ) {
				$setting = $mobile ? str_replace( '_fz', '_mfz', $setting ) : $setting;
				$value   = get_theme_mod( $setting );
				$prefix  = is_numeric( $value ) ? 'px' : '';
				if ( ! empty( $value ) ) {
					foreach ( $props as $prop => $selector ) {
						$css_out .= $selector . '{' . $prop . ':' . $value . $prefix . '}';
					}
				}
			}

			if ( $mobile ) {
				$before = '@media only screen and (max-width:767px){';
				$after  = '}';
			}

			return $before . $css_out . $after;
		}

	}

	add_action( 'plugins_loaded', array( 'Goso_Portfolio', 'get_instance' ) );

endif; /* End check if class exists */

if ( ! function_exists( 'portfolio_meta_content' ) ) {
	function portfolio_meta_content( $social_style = 'style-1', $social_enable = true, $sticky = false, $page_title = false, $echo = true ) {
		$post_id             = get_the_ID();
		$portfolio_max_lists = apply_filters( 'goso_portfolio_list_numer', 5 );

		$short_desc = get_post_meta( $post_id, 'goso_portfolio_desc', true );


		$meta = '';
		$out  = '';

		for ( $i = 1; $i <= $portfolio_max_lists; $i ++ ) {
			$title = get_post_meta( $post_id, 'goso_portfolio_label_' . $i, true );
			$value = get_post_meta( $post_id, 'goso_portfolio_value_' . $i, true );

			if ( $title && $value ) {
				$meta .= '<li>
                                <span class="title">' . esc_attr( $title ) . '</span>
                                <span class="value">' . esc_attr( $value ) . '</span>
                            </li>';
			}

		}

		if ( $meta || $short_desc ) {
			$out .= '<div class="goso-portfolio-meta-wrapper">';

			if ( $sticky ) {
				$out .= '<div class="theiaStickySidebar">';
			}

			if ( $page_title ) {
				$out .= '<div class="goso-page-header"><h1>' . get_the_title() . '</h1></div>';
			}


			if ( $short_desc ) {
				$out .= '<div class="goso-portfolio-col goso-shortdesc post-entry">' . $short_desc . '</div>';
			}

			if ( $meta ) {
				$out .= '<div class="goso-portfolio-col goso-meta-lists"><ul class="portfolio-meta-lists">' . $meta . goso_portfolio_share_html( $social_style, $social_enable ) . '</ul></div>';
			}

			if ( $sticky ) {
				$out .= '</div>';
			}

			$out .= '</div>';
		}

		if ( $echo ) {
			echo $out;
		} else {
			$return = 'has_content';
			if ( empty( $meta ) && empty( $short_desc ) ) {
				$return = 'no_content';
			} elseif ( empty( $meta ) ) {
				$return = 'no_share';
			}

			return $return;
		}
	}
}
if ( ! function_exists( 'goso_portfolio_single_settings' ) ) {
	function goso_portfolio_single_settings( $customize, $meta_key, $default ) {
		$customize_setting = get_theme_mod( $customize, $default );
		$single_setting    = get_post_meta( get_the_ID(), $meta_key, true );

		return ! empty( $single_setting ) ? $single_setting : $customize_setting;
	}
}

if ( ! function_exists( 'goso_portfolio_option2logic' ) ) {
	function goso_portfolio_option2logic( $setting, $res = false ) {
		if ( $res ) {
			return 'enable' == $setting ? false : true;
		} else {
			return 'enable' == $setting;
		}

	}
}
if ( ! function_exists( 'goso_portfolio_share_html' ) ) {
	function goso_portfolio_share_html( $style = 'style-1', $enable = true, $format = 'li' ) {
		ob_start();
		?>
        <<?php echo $format; ?> class="portfolio-share-box tags-share-box <?php echo esc_attr( $style ); ?>">

        <span class="title share-title">
								<?php if ( get_theme_mod( 'goso_trans_share' ) ) {
									echo do_shortcode( get_theme_mod( 'goso_trans_share' ) );
								} else {
									esc_html_e( 'Share', 'authow' );
								} ?></span>
        <span class="value list-posts-share">
            <?php goso_authow_social_share(); ?>
        </span>

        </<?php echo $format; ?>>
		<?php
		$output = ob_get_contents();
		ob_end_clean();

		if ( $enable ) {
			return $output;
		} else {
			return false;
		}
	}
}
if ( ! function_exists( 'goso_relate_projects_carousel' ) ) {
	function goso_relate_projects_carousel( $enable ) {
		if ( ! $enable ) {
			return false;
		}
		$columns              = get_theme_mod( 'goso_single_portfolio_related_col', 3 );
		$layout               = get_theme_mod( 'goso_single_portfolio_related_layout', 'carousel' );
		$relate_project_query = get_posts( array(
			'post_type'      => 'portfolio',
			'post__not_in'   => array( get_the_ID() ),
			'posts_per_page' => get_theme_mod( 'goso_single_portfolio_related_num', 3 ),
		) );
		$parent_class         = 'goso-related-carousel goso-related-grid-display';
		$parent_atts          = array();
		$lazy_class           = 'goso-lazy';
		if ( 'carousel' == $layout ) {
			$parent_atts[] = 'data-lazy="true"';
			$parent_atts[] = 'data-item="' . esc_attr( $columns ) . '"';
			$parent_atts[] = 'data-desktop="' . esc_attr( $columns ) . '"';
			$parent_atts[] = 'data-tablet="2"';
			$parent_atts[] = 'data-tabsmall="2"';
			$parent_atts[] = 'data-auto="false"';
			$parent_atts[] = 'data-speed="300"';
			$parent_atts[] = 'data-dots="true"';
			$parent_atts[] = 'data-nav="false"';
			$parent_class  = 'goso-owl-carousel goso-owl-carousel-slider goso-related-carousel';
			$lazy_class    = 'owl-lazy';
		}
		$parent_atts = implode( ' ', $parent_atts );
		if ( $relate_project_query ) {
			?>
            <div class="post-related portfolio-releated-area goso-related-projects">
                <div class="post-title-box"><h4
                            class="post-box-title"><?php echo get_theme_mod( 'goso_portfolio_related_text', esc_attr( 'Related Projects', 'authow' ) ); ?></h4>
                </div>
                <div class="<?php echo esc_attr( $parent_class ); ?>" <?php echo $parent_atts; ?>>
					<?php foreach ( $relate_project_query as $project ):
						$project_link = get_permalink( $project->ID );
						$project_title = get_the_title( $project->ID );
						$get_terms = wp_get_post_terms( $project->ID, 'portfolio-category' );
						?>
                        <div class="item-related">
							<?php if ( ! get_theme_mod( 'goso_disable_lazyload_single', false ) ) { ?>
                                <a class="related-thumb goso-image-holder <?php echo esc_attr( $lazy_class ); ?>"
                                   data-bgset="<?php echo goso_get_featured_image_size( $project->ID, goso_featured_images_size() ); ?>"
                                   href="<?php esc_url( $project_link ); ?>"
                                   title="<?php echo wp_strip_all_tags( $project_title ); ?>"></a>
							<?php } else { ?>
                                <a class="related-thumb goso-image-holder"
                                   style="background-image: url('<?php echo goso_get_featured_image_size( $project->ID, goso_featured_images_size() ); ?>');"
                                   href="<?php esc_url( $project_link ); ?>"
                                   title="<?php echo wp_strip_all_tags( $project_title ); ?>"></a>
							<?php } ?>
                            <h3>
                                <a href="<?php echo esc_url( get_permalink( $project->ID ) ); ?>"><?php echo esc_attr( get_the_title( $project->ID ) ); ?></a>
                            </h3>
							<?php
							if ( ! empty( $get_terms ) ):
								?>
                                <span class="portfolio-cat">
                                   <?php foreach ( $get_terms as $term ): ?>
                                       <a href="<?php echo esc_url( get_term_link( $term->term_id ) ); ?>"><?php echo esc_attr( $term->name ); ?></a>
                                   <?php endforeach; ?>
                                </span>
							<?php endif; ?>
                        </div>
					<?php endforeach; ?>
                </div>
            </div>
			<?php
		}
	}
}
