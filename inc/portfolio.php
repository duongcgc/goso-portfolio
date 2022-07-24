<?php
/**
 * Add portfolio shortcode
 *
 */

if ( ! class_exists( 'Goso_Authow_Portfolio_Shortcode' ) ):
	class Goso_Authow_Portfolio_Shortcode {
		public function __construct() {
			add_shortcode( 'portfolio', array( $this, 'portfolio_shortcode' ) );
		}

		function portfolio_shortcode( $atts, $content = null ) {
			$image_type = 'landscape';
			$pagination = $pag_pos = $numbermore = $item_style = '';
			$loop       = $cat = $elementor_query = '';

			extract( shortcode_atts( array(
				'style'           => 'masonry',
				'image_type'      => 'landscape',
				'item_style'      => 'text_overlay',
				'number'          => '-1',
				'lightbox'        => 'false',
				'column'          => '3',
				'cat'             => '',
				'filter'          => 'true',
				'all_text'        => __( 'All', 'gosodesign' ),
				'pagination'      => 'number',
				'numbermore'      => 6,
				'loop'            => '',
				'elementor_query' => ''
			), $atts ) );

			/* Set default value when properties is not valid */
			$image_thumb = 'goso-masonry-thumb';
			if ( $style == 'grid' ): $image_thumb = 'goso-thumb'; endif;
			if ( ! is_numeric( $number ) ): $number = '-1'; endif;
			if ( ! in_array( $column, array( '2', '3' ) ) ): $column = '3'; endif;
			if ( $filter != 'false' ): $filter = 'true'; endif;
			if ( empty( $all_text ) ): $all_text = __( 'All', 'gosodesign' ); endif;

			$image_thumb_pre = '';
			$padding         = false;
			if ( $style == 'grid' ) {
				$padding = '66.6666667';
				if ( 'square' == $image_type ) {
					$image_thumb_pre = 'goso-thumb-square';
					$padding         = '100';
				} elseif ( 'vertical' == $image_type ) {
					$image_thumb_pre = 'goso-thumb-vertical';
					$padding         = '135.4';
				}
			}

			if ( $image_thumb_pre ) {
				$image_thumb = $image_thumb_pre;
			}

			/* Display Portfolio */
			global $wp_query, $post;
			if ( get_query_var( 'paged' ) ) {
				$paged = get_query_var( 'paged' );
			} elseif ( get_query_var( 'page' ) ) {
				$paged = get_query_var( 'page' );
			} else {
				$paged = 1;
			}

			$query = array(
				'post_type'      => 'portfolio',
				'posts_per_page' => $number,
				'paged'          => $paged
			);

			$data_query = array( 'post_type' => 'portfolio' );

			if ( ( ! $loop || 'post_type:portfolio' == $loop ) && $cat ) {

				$cat_array = array();
				if ( ! empty ( $cat ) ) {

					$cat                = str_replace( ' ', '', $cat );
					$cat_array          = explode( ',', $cat );
					$query['tax_query'] = array(
						array(
							'taxonomy' => 'portfolio-category',
							'field'    => 'slug',
							'terms'    => $cat_array
						),
					);

					$_cat_array_id   = '';
					$_cat_array_id_i = 0;
					foreach ( $cat_array as $cat_array_item ) {
						$cat_array_term = get_term_by( 'slug', $cat_array_item, 'portfolio-category' );
						if ( isset( $cat_array_term->term_id ) && $cat_array_term->term_id ) {

							if ( $_cat_array_id_i > 0 ) {
								$_cat_array_id .= ',';
							}

							$_cat_array_id .= $cat_array_term->term_id;

							$_cat_array_id_i ++;
						}
					}

					if ( $_cat_array_id ) {
						$data_query['tax_query'] = $_cat_array_id;
					}
				}
			}

			if ( $elementor_query ) {

				$data_query = $elementor_query;
				if ( isset( $elementor_query['filter_bar_ids'] ) ) {
					$data_query['tax_query'] = $elementor_query['filter_bar_ids'];
					unset( $elementor_query['filter_bar_ids'] );
				}

				$query = $elementor_query;
				if ( $paged > 1 && isset( $query['posts_per_page'] ) ) {

					$query_offset_el = isset( $query['offset'] ) ? intval( $query['offset'] ) : 0;
					$page_offset     = $query_offset_el + ( ( $paged - 1 ) * intval( $query['posts_per_page'] ) );
					$query['offset'] = $page_offset;
				}

			} elseif ( $loop ) {
				$data_query    = GosoAuthowLoopSettings::parseData( $loop );
				$query_builder = new GosoAuthowLoopQueryBuilder( $data_query );
				$loop_query    = $query_builder->build_args();

				$query = wp_parse_args( $loop_query, $query );

				if ( $paged > 1 && isset( $query['offset'] ) && isset( $query['posts_per_page'] ) ) {
					$page_offset = intval( $query['offset'] ) + ( ( $paged - 1 ) * intval( $query['posts_per_page'] ) );

					$query['offset'] = $page_offset;
				}
			}

			$portfolio_query = new WP_Query( $query );
			if ( ! $portfolio_query->have_posts() ) {
				return;
			}

			$portfolio_tax = $this->get_taxs_by_post_type( $data_query );

			// Get filter by post
			//$portfolio_terms = $this->get_terms_by_query2( $query, $portfolio_tax );

			// Get filter with category user choose
			$portfolio_terms = $this->get_terms_by_query( $data_query );
			$cat_select      = $this->get_only_cat_select( $data_query );

			$unique_id = 'goso-portfolio' . '--' . rand( 1000, 100000 );
			ob_start();
			/* Portfolio Filter */
			?>
            <div id="<?php echo esc_attr( $unique_id ); ?>" class="wrapper-goso-portfolio">
				<?php if ( $filter == 'true' ):
					?>
					<?php if ( ! empty( $portfolio_terms ) ): ?>
                    <div class="goso-portfolio-filter">
                        <ul>
                            <li class="all active">
                                <a data-term="*" data-filter="*" href="#" class="alltext"><?php echo $all_text; ?></a>
                            </li>
							<?php
							$term_arrayss = array();
							foreach ( $portfolio_terms as $term ) {

								$term_id   = isset( $term->term_id ) ? $term->term_id : '';
								$term_slug = isset( $term->slug ) ? esc_attr( $term->slug ) : '';
								$term_name = isset( $term->name ) ? esc_attr( $term->name ) : '';
								$taxonomy  = isset( $term->taxonomy ) ? esc_attr( $term->taxonomy ) : '';

								if ( $cat_select && ! in_array( $term_id, $cat_select ) ) {
									continue;
								}

								$term_arrayss[ $term_name ] = array( $term_slug, $taxonomy );
							}

							if ( ! empty( $term_arrayss ) ) {
								foreach ( $term_arrayss as $keyx => $valx ) {
									printf( '<li><a data-filter=".goso-%s" data-term="%s" data-tax="%s" href="#">%s</a></li>',
										$valx[0],
										$valx[0],
										$valx[1],
										$keyx
									);
								}
							}
							?>
                        </ul>
                    </div><!-- .portfolio-filter -->
                    <div class="clearfix"></div>
				<?php endif; ?>
				<?php endif; ?>
                <div id="<?php echo $unique_id; ?>"
                     class="goso-portfolio goso-portfolio-wrap column-<?php echo $column; ?> goso-portfolio-<?php echo $item_style; ?>">
                    <div class="inner-portfolio-posts">
						<?php while ( $portfolio_query->have_posts() ): $portfolio_query->the_post(); ?>
							<?php
							$item_classes   = array( 'portfolio-item' );
							$item_cats      = get_the_terms( get_the_ID(), $portfolio_tax );
							$tfl_item_terms = array();
							if ( $item_cats ) {
								foreach ( $item_cats as $item_cat ) {
									$item_classes[]   = 'goso-' . $item_cat->slug;
									$tfl_item_terms[] = $item_cat->slug;
								}
							}
							$item_classes = implode( ' ', $item_classes );
							?>
                            <article class="<?php echo $item_classes; ?>" id="portfolio-<?php the_ID(); ?>"
                                     data-pflID="<?php the_ID(); ?>"
                                     data-terms="<?php echo implode( ' ', $tfl_item_terms ); ?>">
                                <div class="inner-item-portfolio">
                                    <div class="info-portfolio">
                                        <div class="goso-portfolio-thumbnail">
											<?php if ( $lightbox != 'true' || ! function_exists( 'goso_get_featured_image_size' ) ) { ?>
                                            <a href="<?php the_permalink(); ?>">
												<?php } else { ?>
                                                <a class="goso-portfolio-open-lightbox lightbox-port-thumb"
                                                   title="<?php esc_attr( the_title() ); ?>"
                                                   data-rel="goso-gallery-image-content"
                                                   href="<?php echo goso_get_featured_image_size( get_the_ID(), 'goso-single-full' ); ?>">
													<?php } ?>
													<?php
													Goso_Portfolio::get_image_ratio( get_the_ID(), $image_thumb, $padding );
													?>
                                                </a>
                                        </div>
                                        <div class="portfolio-desc">
                                            <a href="<?php the_permalink(); ?>">
                                                <h3 class="portfolio-title"><?php the_title(); ?></h3>
												<?php
												/* Get list term of this portfolio */
												if ( 'product' != get_post_type() ) {
													$get_terms = wp_get_post_terms( get_the_ID(), $portfolio_tax, array( "fields" => "names" ) );
													$get_terms = implode( ', ', $get_terms );
												} else {
													$GLOBALS['post'] = get_post( get_the_ID() );
													setup_postdata( $GLOBALS['post'] );
													global $product;

													if ( '' === $product->get_price() ) {
														$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
													} elseif ( $product->is_on_sale() ) {
														$price = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
													} else {
														$price = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
													}

													$get_terms = apply_filters( 'woocommerce_get_price_html', $price, $product );
												}
												if ( ! empty( $get_terms ) ):
													?>
                                                    <span class="portfolio-cat"><?php echo $get_terms; ?></span>
												<?php endif; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
						<?php endwhile;
						wp_reset_postdata(); ?>
                    </div>
                </div>

                <div class="goso-pagenavi-shortcode">
					<?php
					if ( in_array( $pagination, array( 'load_more', 'infinite' ) ) ) {

						$button_class = 'goso-ajax-more goso-plf-loadmore';
						$button_class .= 'load_more' == $pagination ? ' goso-plf-more-click' : ' goso-infinite-scroll';

						$data_loadmore = 'data-mes_no_more="' . ( function_exists( 'goso_get_setting' ) ? goso_get_setting( 'goso_trans_no_more_posts' ) : '' ) . '"';
						$data_loadmore .= 'data-mes="' . ( function_exists( 'goso_get_setting' ) ? goso_get_setting( 'goso_trans_load_more_posts' ) : '' ) . '"';

						?>
                        <div class="goso-pagination <?php echo $button_class; ?>">

                            <a class="goso-ajax-more-button" <?php echo $data_loadmore; ?>>
                                <span class="ajax-more-text"><?php echo( function_exists( 'goso_get_setting' ) ? goso_get_setting( 'goso_trans_load_more_posts' ) : '' ); ?></span>
                                <span class="goso-pfl-ajaxdot"></span>
                            </a>
                        </div>

						<?php
					} else if ( function_exists( 'goso_pagination_numbers' ) && ( 'none' != $pagination ) ) {
						echo goso_pagination_numbers( $portfolio_query );
					}
					?>
                </div>
				<?php
				$category_post_count = array();
				$category_query      = array_merge( $query, array(
					'paged'              => 0,
					'posts_per_page'     => - 1,
					'portfolio_category' => '',
					'fields'             => 'ids'
				) );


				$all_items_count = count( get_posts( $category_query ) );

				$category_tax_query = isset( $category_query['tax_query'] ) ? $category_query['tax_query'] : array();

				foreach ( $portfolio_terms as $term ) {

					$category_query['tax_query']        = array_merge( $category_tax_query, array(
						'relation' => 'AND',
						array(
							'taxonomy'         => $portfolio_tax,
							'field'            => 'term_id',
							'terms'            => $term->term_id,
							'include_children' => false
						)
					) );
					$get_posts_category_query2          = get_posts( $category_query );
					$category_post_count[ $term->slug ] = count( (array) $get_posts_category_query2 );
				}

				$portfolio_data_js = array(
					'instanceId'   => $unique_id,
					'atts'         => $atts,
					'query'        => $query,
					'layout'       => $style,
					'imagetype'    => $image_type,
					'count'        => $all_items_count,
					'countByTerms' => $category_post_count,
					'currentTerm'  => '*',
					'currentTax'   => $portfolio_tax,
					'lightbox'     => $lightbox,
				);
				?>
                <script type="text/javascript">
                    portfolioDataJs.push( <?php echo json_encode( $portfolio_data_js ); ?> );
                </script>
            </div>

			<?php
			$return = ob_get_clean();
			$return .= $this->get_custom_css( $unique_id, $atts );

			return $return;
		}

		public function get_custom_css( $block_id, $atts ) {
			$atts = wp_parse_args( $atts, array(
				'pbgoverlay_color' => '',
				'ptitle_color'     => '',
				'ptitle_hcolor'    => '',
				'ptitle_fsize'     => '',
				'use_ptitle_typo'  => '',
				'ptitle_typo'      => '',
				'pcat_color'       => '',
				'pcat_hcolor'      => '',
				'pcat_fsize'       => '',
				'use_pcat_typo'    => '',
				'pcat_typo'        => '',

				'pfilter_color'    => '',
				'pfilter_hcolor'   => '',
				'pfilter_fsize'    => '',
				'use_pfilter_typo' => '',
				'pfilter_typo'     => '',
			) );

			$css_custom = '';
			$block_id   = '#' . $block_id;

			if ( $atts['pfilter_color'] ) {
				$css_custom .= $block_id . ' .goso-portfolio-filter ul li a,';
				$css_custom .= $block_id . ' .post-entry .goso-portfolio-filter ul li a{ color:' . esc_attr( $atts['pfilter_color'] ) . ' }';
			}
			if ( $atts['pfilter_hcolor'] ) {
				$css_custom .= $block_id . ' .goso-portfolio-filter ul li a:hover,';
				$css_custom .= $block_id . ' .post-entry .goso-portfolio-filter ul li a:hover{ color:' . esc_attr( $atts['pfilter_hcolor'] ) . ' }';
			}

			if ( class_exists( 'Goso_Vc_Helper' ) ) {
				$css_custom .= Goso_Vc_Helper::vc_google_fonts_parse_attributes( array(
					'font_size'  => $atts['pfilter_fsize'],
					'font_style' => $atts['use_pfilter_typo'] ? $atts['pfilter_typo'] : '',
					'template'   => $block_id . ' .goso-portfolio-filter ul li a,' . $block_id . ' .post-entry .goso-portfolio-filter ul li a{ %s }',
				) );
			}

			if ( $atts['pbgoverlay_color'] ) {
				$css_custom .= $block_id . ' .goso-portfolio-thumbnail a:after{ background-color:' . esc_attr( $atts['pbgoverlay_color'] ) . ' }';
			}
			if ( $atts['ptitle_color'] ) {
				$css_custom .= $block_id . ' .inner-item-portfolio .portfolio-desc h3{ color:' . esc_attr( $atts['ptitle_color'] ) . ' }';
			}
			if ( $atts['ptitle_hcolor'] ) {
				$css_custom .= $block_id . ' .inner-item-portfolio .portfolio-desc h3:hover{ color:' . esc_attr( $atts['ptitle_hcolor'] ) . ' }';
			}

			if ( class_exists( 'Goso_Vc_Helper' ) ) {
				$css_custom .= Goso_Vc_Helper::vc_google_fonts_parse_attributes( array(
					'font_size'  => $atts['ptitle_fsize'],
					'font_style' => $atts['use_ptitle_typo'] ? $atts['ptitle_typo'] : '',
					'template'   => $block_id . ' .inner-item-portfolio .portfolio-desc h3{ %s }',
				) );
			}

			if ( $atts['pcat_color'] ) {
				$css_custom .= $block_id . ' .inner-item-portfolio .portfolio-desc span{ color:' . esc_attr( $atts['pcat_color'] ) . ' }';
			}
			if ( $atts['pcat_hcolor'] ) {
				$css_custom .= $block_id . ' .inner-item-portfolio .portfolio-desc span:hover{ color:' . esc_attr( $atts['pcat_hcolor'] ) . ' }';
			}

			if ( class_exists( 'Goso_Vc_Helper' ) ) {
				$css_custom .= Goso_Vc_Helper::vc_google_fonts_parse_attributes( array(
					'font_size'  => $atts['pcat_fsize'],
					'font_style' => $atts['use_pcat_typo'] ? $atts['pcat_typo'] : '',
					'template'   => $block_id . ' .inner-item-portfolio .portfolio-desc span{ %s }',
				) );
			}

			if ( $css_custom ) {
				return '<style>' . $css_custom . '</style>';
			}

			return '';
		}

		public function get_only_cat_select( $data_query ) {
			$post_type = $this->get_only_post_type( $data_query );
			$cats      = array();
			if ( 'post' == $post_type ) {
				$cats = $this->get_vc_category( $data_query );
			} elseif ( $post_type ) {
				$cats = $this->get_vc_tax_query( $data_query );
			}

			$cats = $this->remove_exclude_param( $cats );

			return $cats;
		}

		public function get_terms_by_query( $data_query ) {
			$object_terms = array();

			$tax = '';

			$post_type     = $this->get_only_post_type( $data_query );
			$id_categories = '';

			if ( 'post' == $post_type ) {
				$tax = 'category';

				if ( isset( $data_query['categories'] ) && $data_query['categories'] ) {
					$id_categories = $data_query['categories'];
				} elseif ( isset( $data_query['tax_query'] ) && $data_query['tax_query'] ) {
					$id_categories = $data_query['tax_query'];
				} else {
					$object_terms = get_categories( array( 'hide_empty' => true ) );
				}
			} elseif ( 'portfolio' == $post_type ) {
				$tax = 'portfolio-category';

				if ( isset( $data_query['tax_query'] ) && $data_query['tax_query'] ) {
					$id_categories = $data_query['tax_query'];
				} else {
					$object_terms = get_terms( $tax, array( 'hide_empty' => true ) );
				}
			} elseif ( 'product' == $post_type ) {
				$tax = 'product_cat';

				if ( isset( $data_query['tax_query'] ) && $data_query['tax_query'] ) {
					$id_categories = $data_query['tax_query'];
				} else {
					$object_terms = get_terms( $tax, array( 'hide_empty' => true ) );
				}
			} elseif ( $post_type ) {
				$taxonomy_objects = get_object_taxonomies( $post_type );

				if ( isset( $taxonomy_objects[0] ) ) {
					$tax = $taxonomy_objects[0];
				}

				if ( isset( $data_query['tax_query'] ) && $data_query['tax_query'] ) {
					$id_categories = $data_query['tax_query'];
				}
			}


			if ( $id_categories && $tax ) {
				$id_categories = $this->stringToArray( $id_categories );
				foreach ( (array) $id_categories as $id_category ) {
					$object_terms[] = get_term( $id_category, $tax );
				}
			}

			return $object_terms;
		}

		/**
		 * @param $args
		 * @param string $tax
		 *
		 * @return array|void|WP_Error
		 */
		public function get_terms_by_query2( $args, $tax = 'portfolio-category' ) {
			if ( ! $tax ) {
				return array();
			}


			// Remove param paged
			if ( isset( $args['paged'] ) ) {
				unset( $args['paged'] );
			}
			$args['fields']         = 'ids';
			$args['posts_per_page'] = 50;

			$posts = new WP_Query( $args );

			if ( ! $posts->have_posts() ) {
				return;
			}

			$object_terms = wp_get_object_terms( $posts->posts, $tax );

			if ( is_wp_error( $object_terms ) ) {
				return array();
			}

			$term_ids = array();
			if ( $object_terms ) {
				foreach ( (array) $object_terms as $term ) {
					$term_ids[] = $term->term_id;
				}

				foreach ( (array) $object_terms as $term ) {
					if ( ! in_array( $term->parent, $term_ids ) ) {
						$term->parent = 0;
					}
				}
			}

			return $object_terms;
		}

		public function get_taxs_by_post_type( $data ) {
			$tax = '';

			$post_type = $this->get_only_post_type( $data );

			if ( 'post' == $post_type ) {
				$tax = 'category';
			} elseif ( 'portfolio' == $post_type ) {
				$tax = 'portfolio-category';
			} elseif ( 'product' == $post_type ) {
				$tax = 'product_cat';
			} elseif ( $post_type ) {
				$taxonomy_objects = get_object_taxonomies( $post_type );

				if ( isset( $taxonomy_objects[0] ) ) {
					$tax = $taxonomy_objects[0];
				}
			}

			return $tax;
		}

		public function get_only_post_type( $data ) {
			$post_type       = $this->get_vc_post_type( $data );
			$count_post_type = count( (array) $post_type );


			if ( $count_post_type > 1 ) {
				return '';
			}

			$post_type = reset( $post_type );

			return $post_type;
		}

		public function get_vc_post_type( $data ) {
			$post_type = isset( $data['post_type'] ) ? $data['post_type'] : '';

			if ( ! $post_type ) {
				return '';
			}

			$post_type = $this->stringToArray( $post_type );
			$post_type = array_filter( $post_type );

			return $post_type;
		}

		public function get_vc_category( $data ) {

			$cats = isset( $data['categories'] ) ? $data['categories'] : '';

			if ( ! $cats ) {
				return array();
			}

			$cats = $this->stringToArray( $cats );
			$cats = array_filter( $cats );

			return $cats;
		}

		public function get_vc_tax_query( $data ) {

			$tax_query = isset( $data['tax_query'] ) ? $data['tax_query'] : '';

			if ( ! $tax_query ) {
				return array();
			}

			$tax_query = $this->stringToArray( $tax_query );
			$tax_query = array_filter( $tax_query );

			return $tax_query;
		}

		public function remove_exclude_param( $data ) {
			if ( ! $data ) {
				return $data;
			}

			foreach ( (array) $data as $key => $value ) {
				$value = (int) $value;

				if ( $value < 0 ) {
					unset( $data[ $key ] );
				}
			}

			return $data;
		}

		public function get_first_item_array( $array ) {
			return isset( $array[0] ) ? $array[0] : '';
		}

		public function stringToArray( $value ) {
			$valid_values = array();

			if ( is_string( $value ) ) {
				$list = preg_split( '/\,[\s]*/', $value );
				if ( $list ) {
					foreach ( (array) $list as $v ) {
						if ( strlen( $v ) > 0 ) {
							$valid_values[] = $v;
						}
					}
				}
			} else {
				$valid_values = $value;
			}

			return $valid_values;
		}
	}

	new Goso_Authow_Portfolio_Shortcode;
endif;

if ( ! function_exists( 'goso_portfolio_shortcode' ) ) {
	function goso_portfolio_shortcode( $atts, $content = null ) {
		return '';
	}
}

if ( ! function_exists( 'goso_pfl_more_post_ajax_func' ) ):

	add_action( 'wp_ajax_nopriv_goso_pfl_more_post_ajax', 'goso_pfl_more_post_ajax_func' );
	add_action( 'wp_ajax_goso_pfl_more_post_ajax', 'goso_pfl_more_post_ajax_func' );

	function goso_pfl_more_post_ajax_func() {
		if ( is_user_logged_in() ) {
			$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
			if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
				die ( 'Nope!' );
			}
		}

		$datafilter    = isset( $_POST['datafilter'] ) ? $_POST['datafilter'] : '';
		$query         = isset( $datafilter['query'] ) ? $datafilter['query'] : '';
		$shown_ids     = isset( $datafilter['pflShowIds'] ) ? $datafilter['pflShowIds'] : '';
		$currentTerm   = isset( $datafilter['currentTerm'] ) ? $datafilter['currentTerm'] : '';
		$portfolio_tax = isset( $datafilter['currentTax'] ) ? $datafilter['currentTax'] : 'portfolio-category';
		$atts          = isset( $datafilter['atts'] ) ? $datafilter['atts'] : '';
		$numbermore    = isset( $atts['numbermore'] ) ? $atts['numbermore'] : '6';
		$style         = ( isset( $datafilter['layout'] ) ) ? $datafilter['layout'] : 'grid';
		$lightbox      = ( isset( $datafilter['lightbox'] ) ) ? $datafilter['lightbox'] : 'false';
		$image_type    = ( isset( $datafilter['imagetype'] ) ) ? $datafilter['imagetype'] : '';


		$pre_query = array_merge( $query, array(
			'ignore_sticky_posts' => true,
			'post__not_in'        => $shown_ids,
			'paged'               => 0,
			'post_status'         => 'publish',
			'posts_per_page'      => $numbermore,
		) );

		if ( $currentTerm && '*' != $currentTerm ) {
			$pre_query['tax_query'] = array(
				array(
					'taxonomy' => $portfolio_tax,
					'field'    => 'slug',
					'terms'    => $currentTerm
				)
			);
		}

		$portfolio_query = new WP_Query( $pre_query );

		if ( ! $portfolio_query->have_posts() ) {
			wp_send_json_success( array( 'items' => '' ) );
		}

		$image_thumb = 'goso-masonry-thumb';
		$padding     = false;
		if ( $style == 'grid' ): $image_thumb = 'goso-thumb';
			$padding                          = '66.6666667'; endif;

		$image_thumb_pre = '';
		if ( $style == 'grid' && 'square' == $image_type ) {
			$image_thumb_pre = 'goso-thumb-square';
			$padding         = '100';
		} elseif ( $style == 'grid' && 'vertical' == $image_type ) {
			$image_thumb_pre = 'goso-thumb-vertical';
			$padding         = '135.4';
		}

		if ( $image_thumb_pre ) {
			$image_thumb = $image_thumb_pre;
		}
		ob_start();
		while ( $portfolio_query->have_posts() ): $portfolio_query->the_post(); ?>
			<?php
			$item_classes   = array( 'portfolio-item' );
			$item_cats      = get_the_terms( get_the_ID(), $portfolio_tax );
			$tfl_item_terms = array();
			if ( $item_cats ) {
				foreach ( $item_cats as $item_cat ) {
					$item_classes[]   = 'goso-' . $item_cat->slug;
					$tfl_item_terms[] = $item_cat->slug;
				}

			}
			$item_classes   = implode( ' ', $item_classes );
			$tfl_item_terms = implode( ' ', $tfl_item_terms );
			?>
            <article class="<?php echo $item_classes; ?>" id="portfolio-<?php the_ID(); ?>"
                     data-pflID="<?php the_ID(); ?>" data-terms="<?php echo $tfl_item_terms; ?>">
                <div class="inner-item-portfolio">
                    <div class="info-portfolio">
                        <div class="goso-portfolio-thumbnail">
							<?php if ( $lightbox != 'true' || ! function_exists( 'goso_get_featured_image_size' ) ) { ?>
                            <a href="<?php the_permalink(); ?>">
								<?php } else { ?>
                                <a class="goso-portfolio-open-lightbox lightbox-port-thumb"
                                   title="<?php esc_attr( the_title() ); ?>" data-rel="goso-gallery-image-content"
                                   href="<?php echo goso_get_featured_image_size( get_the_ID(), 'goso-single-full' ); ?>">
									<?php } ?>
									<?php
									Goso_Portfolio::get_image_ratio( get_the_ID(), $image_thumb, $padding );
									?>
                                </a>
                        </div>
                        <div class="portfolio-desc">
                            <a href="<?php the_permalink(); ?>">
                                <h3 class="portfolio-title"><?php the_title(); ?></h3>
								<?php
								if ( 'product' != get_post_type() ) {
									$get_terms = wp_get_post_terms( get_the_ID(), $portfolio_tax, array( "fields" => "names" ) );
									$get_terms = implode( ', ', $get_terms );
								} else {
									$GLOBALS['post'] = get_post( get_the_ID() );
									setup_postdata( $GLOBALS['post'] );
									global $product;

									if ( '' === $product->get_price() ) {
										$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
									} elseif ( $product->is_on_sale() ) {
										$price = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
									} else {
										$price = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
									}

									$get_terms = apply_filters( 'woocommerce_get_price_html', $price, $product );
								}
								if ( ! empty( $get_terms ) ):
									?>
                                    <span class="portfolio-cat"><?php echo $get_terms; ?></span>
								<?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
		<?php endwhile;
		wp_reset_postdata();
		$content_items = ob_get_clean();
		wp_send_json_success( array( 'items' => $content_items ) );
	}
endif;
?>
