<?php
/**
 * Adds Goso portfolio meta box to the post editing screen
 */
function Goso_Portfolio_Add_Custom_Metabox() {
	new Goso_Portfolio_Add_Custom_Metabox_Class();
}

if ( is_admin() ) {
	add_action( 'load-post.php', 'Goso_Portfolio_Add_Custom_Metabox' );
	add_action( 'load-post-new.php', 'Goso_Portfolio_Add_Custom_Metabox' );
}

/**
 * The Class.
 */
class Goso_Portfolio_Add_Custom_Metabox_Class {

	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box( $post_type ) {
		$post_types = array( 'portfolio' );     //limit meta box to certain post types
		if ( in_array( $post_type, $post_types ) ) {
			add_meta_box(
				'goso_portfolio_meta'
				, esc_html__( 'Portfolio Settings', 'authow' )
				, array( $this, 'render_meta_box_content' )
				, $post_type
				, 'advanced'
				, 'default'
			);
		}
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {

		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['goso_portfolio_custom_box_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['goso_portfolio_custom_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'goso_portfolio_custom_box' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted,
		//     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$portfolio_max_lists = apply_filters( 'goso_portfolio_list_numer', 5 );
		for ( $i = 1; $i <= $portfolio_max_lists; $i ++ ) {
			$title_setting_name = 'goso_portfolio_label_' . $i;
			$value_setting_name = 'goso_portfolio_value_' . $i;
			if ( isset( $_POST[ $title_setting_name ] ) ) {
				update_post_meta( $post_id, $title_setting_name, sanitize_text_field( $_POST[ $title_setting_name ] ) );
			}
			if ( isset( $_POST[ $value_setting_name ] ) ) {
				update_post_meta( $post_id, $value_setting_name, sanitize_text_field( $_POST[ $value_setting_name ] ) );
			}
		}

		$portfolio_metas_settings = array();

		$portfolio_metas = array(
			'goso_portfolio_hide_featured_img',
			'goso_portfolio_hide_sharebox',
			'goso_portfolio_hide_relared_portfolio',
			'goso_portfolio_hide_nextprev_nav',
		);

		foreach ( $portfolio_metas as $meta_key ) {
			$portfolio_metas_settings[ $meta_key ] = isset( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
		}

		update_post_meta( $post_id, 'portfolio_options_meta', $portfolio_metas_settings );

		$portfolio_other_settings = array(
			'goso_portfolio_content_width',
			'goso_portfolio_content_style'
		);

		foreach ( $portfolio_other_settings as $setting ) {
			if ( isset( $_POST[ $setting ] ) ) {
				update_post_meta( $post_id, $setting, $_POST[ $setting ] );
			}
		}

		if ( isset( $_POST['goso_portfolio_desc'] ) ) {
			update_post_meta( $post_id, 'goso_portfolio_desc', htmlspecialchars( $_POST['goso_portfolio_desc'] ) );
		}
	}


	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'goso_portfolio_custom_box', 'goso_portfolio_custom_box_nonce' );

		// Display the form, using the current value.
		$goso_portfolio_desc = get_post_meta( $post->ID, 'goso_portfolio_desc', true );
		?>
        <div class="goso-portfolio-meta-settings-wrap">

            <div class="goso-table-meta">
                <h3><?php echo esc_attr( 'Portfolio Description', 'goso-portfolio' ); ?></h3>
                <div class="goso-row-editor">
                    <label for="goso_portfolio_desc"
                           class="goso-format-row goso-format-portfolio row-block">Type the description for your
                        project here:</label>
					<?php wp_editor( htmlspecialchars_decode( $goso_portfolio_desc ), 'goso_portfolio_desc', array(
						'media_buttons' => true,
						'textarea_rows' => 5
					) ); ?>
                </div>

                <h3><?php echo esc_attr( 'Portfolio Information', 'goso-portfolio' ); ?></h3>

                <div class="goso-grid-2">
					<?php
					$portfolio_max_lists = apply_filters( 'goso_portfolio_list_numer', 5 );
					for ( $i = 1; $i <= $portfolio_max_lists; $i ++ ) {
						$title_setting_name = 'goso_portfolio_label_' . $i;
						$value_setting_name = 'goso_portfolio_value_' . $i;

						$title_setting_value = get_post_meta( $post->ID, $title_setting_name, true );
						$value_setting_value = get_post_meta( $post->ID, $value_setting_name, true );
						?>

                        <p>
                            <label for="<?php echo esc_attr( $title_setting_name ); ?>"
                                   class="goso-format-row"><?php esc_html_e( 'Portfolio Label ' . $i, 'goso' ); ?></label>
                            <input style="width:100%;" type="text" name="<?php echo esc_attr( $title_setting_name ); ?>"
                                   id="<?php echo esc_attr( $title_setting_name ); ?>"
                                   value="<?php echo esc_attr( $title_setting_value ); ?>">
                        </p>
                        <p>
                            <label for="<?php echo esc_attr( $value_setting_name ); ?>"
                                   class="goso-format-row"><?php esc_html_e( 'Portfolio Value ' . $i, 'goso' ); ?></label>
                            <input style="width:100%;" type="text" name="<?php echo esc_attr( $value_setting_name ); ?>"
                                   id="<?php echo esc_attr( $value_setting_name ); ?>"
                                   value="<?php echo esc_attr( $value_setting_value ); ?>">
                        </p>
					<?php } ?>
                </div>

                <div class="goso-grid-3">

					<?php
					$list_content_width       = array(
						''                           => esc_attr( 'Follow Customize Settings', 'goso-portfolio' ),
						'no-sidebar'                 => esc_attr( 'No Sidebar', 'goso-portfolio' ),
						'no-sidebar-small-container' => esc_attr( 'No Sidebar with Small Container', 'goso-portfolio' ),
						'left-sidebar'               => esc_attr( 'Left Sidebar', 'goso-portfolio' ),
						'right-sidebar'              => esc_attr( 'Right Sidebar', 'goso-portfolio' ),
						'both-sidebar'               => esc_attr( 'Both Sidebar', 'goso-portfolio' ),
						'fullwidth'                  => esc_attr( 'Full Width', 'goso-portfolio' ),
					);
					$list_content_width_value = get_post_meta( $post->ID, 'goso_portfolio_content_width', true );
					?>
                    <p>
                        <label for="goso_portfolio_content_width"
                               class="goso-format-row goso-format-row2"><?php esc_html_e( 'Portfolio Sidebar', 'goso-portfolio' ); ?></label>
                        <select name="goso_portfolio_content_width" id="goso_portfolio_content_width">
							<?php
							foreach ( $list_content_width as $type => $label ) {
								echo '<option value="' . $type . '" ' . selected( $list_content_width_value, $type, false ) . '>' . $label . '</option>';
							}
							?>
                        </select>
                    </p>


					<?php
					$list_content_style       = array(
						''        => esc_attr( 'Follow Customize Settings', 'goso-portfolio' ),
						'style-1' => esc_attr( 'Style 1', 'goso-portfolio' ),
						'style-2' => esc_attr( 'Style 2', 'goso-portfolio' ),
						'style-3' => esc_attr( 'Style 3', 'goso-portfolio' ),
					);
					$list_content_style_value = get_post_meta( $post->ID, 'goso_portfolio_content_style', true );
					?>
                    <p>
                        <label for="goso_portfolio_content_style"
                               class="goso-format-row goso-format-row2"><?php esc_html_e( 'Portfolio Style', 'goso-portfolio' ); ?></label>
                        <select name="goso_portfolio_content_style" id="goso_portfolio_content_style">
							<?php
							foreach ( $list_content_style as $type => $label ) {
								echo '<option value="' . $type . '" ' . selected( $list_content_style_value, $type, false ) . '>' . $label . '</option>';
							}
							?>
                        </select>
                    </p>


					<?php
					$portfolio_list_checkbox = array(
						'goso_portfolio_hide_featured_img'      => esc_html__( 'Featured Image on Portfolio', 'goso-portfolio' ),
						'goso_portfolio_hide_sharebox'          => esc_html__( 'Share Box on Portfolio', 'goso-portfolio' ),
						'goso_portfolio_hide_relared_portfolio' => esc_html__( 'Related Portfolio', 'goso-portfolio' ),
						'goso_portfolio_hide_nextprev_nav'      => esc_html__( 'Portfolio Next/Prev Navigation', 'goso-portfolio' ),
					);

					$portfolio_options_meta = get_post_meta( $post->ID, 'portfolio_options_meta', true );

					foreach ( $portfolio_list_checkbox as $id => $title ) {
						$checkbox_value = isset( $portfolio_options_meta[ $id ] ) ? $portfolio_options_meta[ $id ] : '';
						?>
                        <p class="child-setting">
                            <label for="<?php echo esc_attr( $id ); ?>"
                                   class="goso-format-row goso-format-row2"><?php echo $title; ?></label>
                            <select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>">
                                <option value="" <?php selected( $checkbox_value, '' ) ?>><?php esc_html_e( 'Default', 'goso-portfolio' ) ?></option>
                                <option value="enable" <?php selected( $checkbox_value, 'enable' ) ?>><?php esc_html_e( 'Show', 'goso-portfolio' ) ?></option>
                                <option value="disable" <?php selected( $checkbox_value, 'disable' ) ?>><?php esc_html_e( 'Hide', 'goso-portfolio' ) ?></option>
                            </select>
                        </p>
						<?php
					}
					?>
                </div>
            </div>
        </div>
		<?php
	}
}
