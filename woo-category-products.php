<?php
/**
 * Plugin Name: Woo Category Products
 * Description: Display WooCommerce products from a selected product category with a responsive, Tailwind-friendly layout.
 * Version: 1.1.0
 * Author: Foysal Hasan
 * Author URI: https://wa.me/8801521521499
 * Text Domain: woo-category-products
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Woo_Category_Products {
	const VERSION = '1.1.0';
	const OPTION  = 'woo_category_products_settings';
	const DEFAULT_CATEGORY_COLOR = '#091b29';

	/** @var array<string, string> */
	private $defaults = array(
		'category_text' => '#ffffff',
		'product_bg' => '#ffffff',
		'product_text' => '#202329',
		'accent' => '#28b487',
		'muted' => '#737b87',
	);

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'product_cat_add_form_fields', array( $this, 'render_category_color_add_field' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'render_category_color_edit_field' ) );
		add_action( 'created_product_cat', array( $this, 'save_category_color' ) );
		add_action( 'edited_product_cat', array( $this, 'save_category_color' ) );
		add_shortcode( 'woo_category_products', array( $this, 'render_shortcode' ) );
	}

	public function register_frontend_assets() {
		wp_register_style(
			'woo-category-products-jakarta',
			'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
			array(),
			self::VERSION
		);
	}

	public function enqueue_admin_assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_settings_page = 'settings_page_woo-category-products' === $hook;
		$is_product_category_page = $screen && 'product_cat' === $screen->taxonomy;
		if ( ! $is_settings_page && ! $is_product_category_page ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', "jQuery(function($){ $('.wcp-color-field').wpColorPicker(); });" );
	}

	public function render_category_color_add_field() {
		?>
		<div class="form-field term-wcp-category-color-wrap">
			<label for="wcp-category-color"><?php esc_html_e( 'Woo Category Products header color', 'woo-category-products' ); ?></label>
			<input class="wcp-color-field" id="wcp-category-color" name="wcp_category_color" type="text" value="<?php echo esc_attr( self::DEFAULT_CATEGORY_COLOR ); ?>" data-default-color="<?php echo esc_attr( self::DEFAULT_CATEGORY_COLOR ); ?>">
			<p class="description"><?php esc_html_e( 'Choose the color for this category’s header bar in the Woo Category Products shortcode. Default: #091b29.', 'woo-category-products' ); ?></p>
		</div>
		<?php
	}

	public function render_category_color_edit_field( $term ) {
		$color = sanitize_hex_color( get_term_meta( $term->term_id, '_wcp_category_color', true ) );
		if ( ! $color ) {
			$color = self::DEFAULT_CATEGORY_COLOR;
		}
		?>
		<tr class="form-field term-wcp-category-color-wrap">
			<th scope="row"><label for="wcp-category-color"><?php esc_html_e( 'Woo Category Products header color', 'woo-category-products' ); ?></label></th>
			<td>
				<input class="wcp-color-field" id="wcp-category-color" name="wcp_category_color" type="text" value="<?php echo esc_attr( $color ); ?>" data-default-color="<?php echo esc_attr( self::DEFAULT_CATEGORY_COLOR ); ?>">
				<p class="description"><?php esc_html_e( 'Choose the color for this category’s header bar in the Woo Category Products shortcode. Default: #091b29.', 'woo-category-products' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public function save_category_color( $term_id ) {
		if ( ! isset( $_POST['wcp_category_color'] ) || ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'edit_term', $term_id ) ) ) {
			return;
		}

		$color = sanitize_hex_color( wp_unslash( $_POST['wcp_category_color'] ) );
		if ( $color ) {
			update_term_meta( $term_id, '_wcp_category_color', $color );
		} else {
			delete_term_meta( $term_id, '_wcp_category_color' );
		}
	}

	public function add_settings_page() {
		add_options_page(
			esc_html__( 'Woo Category Products', 'woo-category-products' ),
			esc_html__( 'Woo Category Products', 'woo-category-products' ),
			'manage_options',
			'woo-category-products',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'woo_category_products_group',
			self::OPTION,
			array(
				'type' => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default' => $this->defaults,
			)
		);
	}

	public function sanitize_settings( $input ) {
		$clean = array();
		foreach ( $this->defaults as $key => $default ) {
			$color       = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$clean[$key] = $color ? $color : $default;
		}
		return $clean;
	}

	private function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $this->defaults );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = $this->get_settings();
		$fields = array(
			'category_text' => __( 'Category header text', 'woo-category-products' ),
			'product_bg'    => __( 'Product card background', 'woo-category-products' ),
			'product_text'  => __( 'Product card text', 'woo-category-products' ),
			'accent'        => __( 'Buy button / accent', 'woo-category-products' ),
			'muted'         => __( 'Secondary text', 'woo-category-products' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Woo Category Products', 'woo-category-products' ); ?></h1>
			<p><?php esc_html_e( 'Set the category header text and product row colors here. Set each category’s header background color under Products → Categories. Layout and typography use your site’s Tailwind CSS.', 'woo-category-products' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( 'woo_category_products_group' ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( $fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="wcp-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td><input class="wcp-color-field" id="wcp-<?php echo esc_attr( $key ); ?>" type="text" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" data-default-color="<?php echo esc_attr( $this->defaults[ $key ] ); ?>"></td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr>
			<p><strong><?php esc_html_e( 'Shortcode:', 'woo-category-products' ); ?></strong> <code>[woo_category_products category="your-category-slug"]</code></p>
			<hr>
			<p><?php esc_html_e( 'Plugin by Foysal Hasan.', 'woo-category-products' ); ?> <?php esc_html_e( 'WhatsApp:', 'woo-category-products' ); ?> <a href="https://wa.me/8801521521499" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Chat with Foysal Hasan on WhatsApp at +8801521521499', 'woo-category-products' ); ?>">+8801521521499</a></p>
		</div>
		<?php
	}

	/** Render [woo_category_products category="slug"]. */
	public function render_shortcode( $atts ) {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
			return '<p class="!my-4 !rounded-xl !bg-red-50 !p-4 !text-sm !text-red-700">' . esc_html__( 'Woo Category Products requires WooCommerce to be active.', 'woo-category-products' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'category' => '',
				'slug' => '',
			),
			$atts,
			'woo_category_products'
		);
		$slug = sanitize_title( $atts['category'] ? $atts['category'] : $atts['slug'] );
		if ( ! $slug ) {
			return '<p class="!my-4 !rounded-xl !bg-amber-50 !p-4 !text-sm !text-amber-800">' . esc_html__( 'Add a product category slug, for example: [woo_category_products category="gmail-accounts"].', 'woo-category-products' ) . '</p>';
		}

		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return '<p class="!my-4 !rounded-xl !bg-amber-50 !p-4 !text-sm !text-amber-800">' . esc_html__( 'The requested product category was not found.', 'woo-category-products' ) . '</p>';
		}

		$query = new WP_Query(
			array(
				'post_type' => 'product',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'no_found_rows' => true,
				'ignore_sticky_posts' => true,
				'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
					'field' => 'term_id',
					'terms' => (int) $term->term_id,
				),
				),
			)
		);

		wp_enqueue_style( 'woo-category-products-jakarta' );
		$settings = $this->get_settings();
		$category_color = sanitize_hex_color( get_term_meta( $term->term_id, '_wcp_category_color', true ) );
		if ( ! $category_color ) {
			$category_color = self::DEFAULT_CATEGORY_COLOR;
		}
		$instance_id = wp_unique_id( 'wcp-' );
		$style = sprintf(
			'--wcp-category-bg:%1$s;--wcp-category-text:%2$s;--wcp-product-bg:%3$s;--wcp-product-text:%4$s;--wcp-accent:%5$s;--wcp-muted:%6$s;font-family:"Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif;',
			esc_attr( $category_color ),
			esc_attr( $settings['category_text'] ),
			esc_attr( $settings['product_bg'] ),
			esc_attr( $settings['product_text'] ),
			esc_attr( $settings['accent'] ),
			esc_attr( $settings['muted'] )
		);

		ob_start();
		?>
		<section id="<?php echo esc_attr( $instance_id ); ?>" class="woo-category-products !my-6 !w-full !space-y-2 sm:!my-8" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Products in %s', 'woo-category-products' ), $term->name ) ); ?>">
			<div class="!grid !grid-cols-1 !items-center !gap-2 !rounded-xl !px-4 !py-4 !shadow-sm sm:!grid-cols-[minmax(0,1fr)_9rem_10rem_6.5rem] sm:!gap-4 sm:!px-5" style="background-color:var(--wcp-category-bg);color:var(--wcp-category-text)">
				<div class="!min-w-0">
					<span class="!text-lg !font-extrabold !leading-tight !text-[var(--wcp-category-text)] sm:!text-xl"><?php echo esc_html( $term->name ); ?></span>
					<?php if ( trim( wp_strip_all_tags( $term->description ) ) ) : ?>
						<p class="!mb-0 !mt-1 !text-xs !leading-relaxed !opacity-75 sm:!text-sm"><?php echo esc_html( wp_strip_all_tags( $term->description ) ); ?></p>
					<?php endif; ?>
				</div>
				<div class="!hidden !text-sm !font-bold !tracking-wide sm:!block"><?php esc_html_e( 'In Stock', 'woo-category-products' ); ?></div>
				<div class="!hidden !text-sm !font-bold !tracking-wide sm:!block"><?php esc_html_e( 'Price', 'woo-category-products' ); ?></div>
				<div class="!hidden sm:!block" aria-hidden="true"></div>
			</div>

			<?php if ( $query->have_posts() ) : ?>
				<div class="!space-y-2">
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						$product = wc_get_product( get_the_ID() );
						if ( ! $product || ! $product->is_visible() ) {
							continue;
						}
						$product_id = $product->get_id();
						$price = $product->is_type( 'variable' ) ? $product->get_variation_price( 'min', true ) : $product->get_price();
						$price_html = '' === $price ? __( 'Contact for price', 'woo-category-products' ) : wc_price( (float) $price );
						$buy_url = add_query_arg( 'qc_cart_product_id', $product_id, get_permalink( $product_id ) );
						// Stable per-product pseudo-random count, so it looks random but does not change on every page load.
						$sold = 100 + ( abs( crc32( 'wcp-sold-' . $product_id ) ) % 9900 );
						?>
						<article class="!grid !grid-cols-[minmax(0,1fr)_auto] !items-center !gap-x-3 !gap-y-3 !rounded-xl !p-3 !shadow-sm !ring-1 !ring-black/5 sm:!grid-cols-[minmax(0,1fr)_9rem_10rem_6.5rem] sm:!gap-4 sm:!px-5 sm:!py-4" style="background-color:var(--wcp-product-bg);color:var(--wcp-product-text)">
							<div class="!flex !min-w-0 !items-center !gap-3 sm:!gap-4">
								<div class="!flex !h-11 !w-11 !shrink-0 !items-center !justify-center !overflow-hidden !rounded-xl !bg-slate-100 !text-slate-500 sm:!h-12 sm:!w-12">
									<?php if ( $product->get_image_id() ) : ?>
										<?php echo wp_kses_post( $product->get_image( 'thumbnail', array( 'class' => '!h-full !w-full !object-cover' ) ) ); ?>
									<?php else : ?>
										<svg class="!h-6 !w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m4.5 7.8 7.5 4.4 7.5-4.4M12 12.3V20" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
									<?php endif; ?>
								</div>
								<div class="!min-w-0 !text-[13px] !font-semibold !leading-6 !text-[var(--wcp-product-text)]">
									<?php echo esc_html( $product->get_name() ); ?>
								</div>
							</div>

							<div class="!col-start-1 !row-start-2 !pl-14 !text-sm sm:!col-auto sm:!row-auto sm:!pl-0">
								<div class="!font-semibold !leading-5"><?php esc_html_e( 'Unlimited', 'woo-category-products' ); ?></div>
								<div class="!mt-0.5 !text-xs !leading-5 !text-[var(--wcp-muted)]"><?php echo esc_html( sprintf( __( 'Sold %s pieces', 'woo-category-products' ), number_format_i18n( $sold ) ) ); ?></div>
							</div>

							<div class="!col-start-2 !row-start-2 !text-right sm:!col-auto sm:!row-auto sm:!text-left">
								<div class="!text-xs !leading-5 !text-[var(--wcp-muted)]"><?php esc_html_e( 'Price per item', 'woo-category-products' ); ?></div>
								<div class="!text-sm !font-bold !leading-5"><?php esc_html_e( 'from', 'woo-category-products' ); ?> <?php echo wp_kses_post( $price_html ); ?></div>
							</div>

							<div class="!col-span-2 !flex !justify-end sm:!col-auto">
								<a href="<?php echo esc_url( $buy_url ); ?>" class="!inline-flex !min-h-10 !items-center !justify-center !gap-2 !rounded-lg !px-4 !text-sm !font-bold !text-white !no-underline !shadow-sm !transition hover:!brightness-95 focus:!outline-none focus:!ring-2 focus:!ring-offset-2" style="background-color:var(--wcp-accent)" aria-label="<?php echo esc_attr( sprintf( __( 'Buy %s', 'woo-category-products' ), $product->get_name() ) ); ?>">
									<svg class="!h-4 !w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.3a2 2 0 0 0 1.9-1.4L21 9H7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.3" fill="currentColor"/><circle cx="18" cy="20" r="1.3" fill="currentColor"/></svg>
									<?php esc_html_e( 'Buy', 'woo-category-products' ); ?>
								</a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<div class="!rounded-xl !bg-white !px-5 !py-8 !text-center !text-sm !text-slate-500 !shadow-sm"><?php esc_html_e( 'No products were found in this category.', 'woo-category-products' ); ?></div>
			<?php endif; ?>
		</section>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}

new Woo_Category_Products();
