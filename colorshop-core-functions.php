<?php
/**
 * ColorShop Core Functions
 *
 * Functions available on both the front-end and admin.
 *
 * @author 		ColorVila
 * @category 	Core
 * @package 	ColorShop/Functions
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Hooks used in admin and frontend
 */
add_filter( 'colorshop_coupon_code', 'sanitize_text_field' );
add_filter( 'colorshop_coupon_code', 'strtolower' ); // Coupons case-insensitive by default
add_filter( 'colorshop_stock_amount', 'intval' ); // Stock amounts are integers by default

/**
 * Main function for returning products, uses the CS_Product_Factory class.
 *
 * @access public
 * @param mixed $the_product Post object or post ID of the product.
 * @param array $args (default: array()) Contains all arguments to be used to get this product.
 * @return void
 */
function get_product( $the_product = false, $args = array() ) {
	global $colorshop;
	return $colorshop->product_factory->get_product( $the_product, $args );
}

/**
 * Function that returns an array containing the IDs of the products that are on sale.
 *
 * @since 2.0
 * @access public
 * @return array
 */
function colorshop_get_product_ids_on_sale() {
	// Load from cache
	$product_ids_on_sale = get_transient( 'cs_products_onsale' );

	// Valid cache found
	if ( false !== $product_ids_on_sale )
		return $product_ids_on_sale;

	$on_sale = get_posts( array(
		'post_type'      => array( 'product', 'product_variation' ),
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'        => '_sale_price',
				'value'      => 0,
				'compare'    => '>=',
				'type'       => 'DECIMAL',
			),
			array(
				'key'        => '_sale_price',
				'value'      => '',
				'compare'    => '!=',
				'type'       => '',
			)
		),
		'fields'         => 'id=>parent',
	) );

	$product_ids = array_keys( $on_sale );
	$parent_ids  = array_values( $on_sale );

	// Check for scheduled sales which have not started
	foreach ( $product_ids as $key => $id ) {
		if ( get_post_meta( $id, '_sale_price_dates_from', true ) > current_time( 'timestamp' ) ) {
			unset( $product_ids[ $key ] );
		}
	}

	$product_ids_on_sale = array_unique( array_merge( $product_ids, $parent_ids ) );

	set_transient( 'cs_products_onsale', $product_ids_on_sale );

	return $product_ids_on_sale;
}

/**
 * colorshop_sanitize_taxonomy_name function.
 *
 * @access public
 * @param mixed $taxonomy
 * @return void
 */
function colorshop_sanitize_taxonomy_name( $taxonomy ) {
	return str_replace( array( ' ', '_' ), '-', strtolower( $taxonomy ) );
}

/**
 * colorshop_get_attachment_image_attributes function.
 *
 * @access public
 * @param mixed $attr
 * @return void
 */
function colorshop_get_attachment_image_attributes( $attr ) {
	if ( strstr( $attr['src'], 'colorshop_uploads/' ) )
		$attr['src'] = colorshop_placeholder_img_src();

	return $attr;
}

add_filter( 'wp_get_attachment_image_attributes', 'colorshop_get_attachment_image_attributes' );


/**
 * colorshop_prepare_attachment_for_js function.
 *
 * @access public
 * @param mixed $response
 * @return void
 */
function colorshop_prepare_attachment_for_js( $response ) {

	if ( isset( $response['url'] ) && strstr( $response['url'], 'colorshop_uploads/' ) ) {
		$response['full']['url'] = colorshop_placeholder_img_src();
		if ( isset( $response['sizes'] ) ) {
			foreach( $response['sizes'] as $size => $value ) {
				$response['sizes'][ $size ]['url'] = colorshop_placeholder_img_src();
			}
		}
	}

	return $response;
}

add_filter( 'wp_prepare_attachment_for_js', 'colorshop_prepare_attachment_for_js' );

/**
 * colorshop_get_dimension function.
 *
 * Normalise dimensions, unify to cm then convert to wanted unit value
 *
 * Usage: colorshop_get_dimension(55, 'in');
 *
 * @access public
 * @param mixed $dim
 * @param mixed $to_unit 'in', 'm', 'cm', 'm'
 * @return float
 */
function colorshop_get_dimension( $dim, $to_unit ) {

	$from_unit 	= strtolower( get_option( 'colorshop_dimension_unit' ) );
	$to_unit	= strtolower( $to_unit );

	// Unify all units to cm first
	if ( $from_unit !== $to_unit ) {

		switch ( $from_unit ) {
			case 'in':
				$dim *= 2.54;
			break;
			case 'm':
				$dim *= 100;
			break;
			case 'mm':
				$dim *= 0.1;
			break;
			case 'yd':
				$dim *= 0.010936133;
			break;
		}

		// Output desired unit
		switch ( $to_unit ) {
			case 'in':
				$dim *= 0.3937;
			break;
			case 'm':
				$dim *= 0.01;
			break;
			case 'mm':
				$dim *= 10;
			break;
			case 'yd':
				$dim *= 91.44;
			break;
		}
	}
	return ( $dim < 0 ) ? 0 : $dim;
}


/**
 * colorshop_get_weight function.
 *
 * Normalise weights, unify to cm then convert to wanted unit value
 *
 * Usage: colorshop_get_weight(55, 'kg');
 *
 * @access public
 * @param mixed $weight
 * @param mixed $to_unit 'g', 'kg', 'lbs'
 * @return float
 */
function colorshop_get_weight( $weight, $to_unit ) {

	$from_unit 	= strtolower( get_option('colorshop_weight_unit') );
	$to_unit	= strtolower( $to_unit );

	//Unify all units to kg first
	if ( $from_unit !== $to_unit ) {

		switch ( $from_unit ) {
			case 'g':
				$weight *= 0.001;
			break;
			case 'lbs':
				$weight *= 0.4536;
			break;
			case 'oz':
				$weight *= 0.0283;
			break;
		}

		// Output desired unit
		switch ( $to_unit ) {
			case 'g':
				$weight *= 1000;
			break;
			case 'lbs':
				$weight *= 2.2046;
			break;
			case 'oz':
				$weight *= 35.274;
			break;
		}
	}
	return ( $weight < 0 ) ? 0 : $weight;
}


/**
 * Get product name with extra details such as SKU price and attributes. Used within admin.
 *
 * @access public
 * @param mixed $product
 * @return void
 */
function colorshop_get_formatted_product_name( $product ) {
	if ( ! $product || ! is_object( $product ) )
		return;

	if ( $product->get_sku() )
		$identifier = $product->get_sku();
	elseif ( $product->is_type( 'variation' ) )
		$identifier = '#' . $product->variation_id;
	else
		$identifier = '#' . $product->id;

	if ( $product->is_type( 'variation' ) ) {
		$attributes = $product->get_variation_attributes();
		$extra_data = ' &ndash; ' . implode( ', ', $attributes ) . ' &ndash; ' . colorshop_price( $product->get_price() );
	} else {
		$extra_data = '';
	}

	return sprintf( __( '%s &ndash; %s%s', 'colorshop' ), $identifier, $product->get_title(), $extra_data );
}


/**
 * Get the placeholder image URL for products etc
 *
 * @access public
 * @return string
 */
function colorshop_placeholder_img_src() {
	global $colorshop;

	return apply_filters('colorshop_placeholder_img_src', $colorshop->plugin_url() . '/assets/images/placeholder.png' );
}


/**
 * Get the placeholder image
 *
 * @access public
 * @return string
 */
function colorshop_placeholder_img( $size = 'shop_thumbnail' ) {
	global $colorshop;

	$dimensions = $colorshop->get_image_size( $size );

	return apply_filters('colorshop_placeholder_img', '<img src="' . colorshop_placeholder_img_src() . '" alt="Placeholder" width="' . $dimensions['width'] . '" height="' . $dimensions['height'] . '" />' );
}


/**
 * colorshop_lostpassword_url function.
 *
 * @access public
 * @param mixed $url
 * @return void
 */
function colorshop_lostpassword_url( $url ) {
    $id = colorshop_get_page_id( 'lost_password' );
    if ( $id != -1 )
    	 $url = get_permalink( $id );

    return $url;
}

add_filter( 'lostpassword_url',  'colorshop_lostpassword_url' );


/**
 * Send HTML emails from ColorShop
 *
 * @access public
 * @param mixed $to
 * @param mixed $subject
 * @param mixed $message
 * @param string $headers (default: "Content-Type: text/html\r\n")
 * @param string $attachments (default: "")
 * @return void
 */
function colorshop_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	global $colorshop;

	$mailer = $colorshop->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

if ( ! function_exists( 'colorshop_get_page_id' ) ) {

	/**
	 * ColorShop page IDs
	 *
	 * retrieve page ids - used for myaccount, edit_address, change_password, shop, cart, checkout, pay, view_order, thanks, terms
	 *
	 * returns -1 if no page is found
	 *
	 * @access public
	 * @param string $page
	 * @return int
	 */
	function colorshop_get_page_id( $page ) {
		$page = apply_filters('colorshop_get_' . $page . '_page_id', get_option('colorshop_' . $page . '_page_id'));
		return ( $page ) ? $page : -1;
	}
}

if ( ! function_exists( 'colorshop_empty_cart' ) ) {

	/**
	 * ColorShop clear cart
	 *
	 * Clears the cart session when called
	 *
	 * @access public
	 * @return void
	 */
	function colorshop_empty_cart() {
		global $colorshop;

		if ( ! isset( $colorshop->cart ) || $colorshop->cart == '' )
			$colorshop->cart = new CS_Cart();

		$colorshop->cart->empty_cart( false );
	}
}

if ( ! function_exists( 'colorshop_disable_admin_bar' ) ) {

	/**
	 * ColorShop disable admin bar
	 *
	 * @access public
	 * @param bool $show_admin_bar
	 * @return bool
	 */
	function colorshop_disable_admin_bar( $show_admin_bar ) {
		if ( get_option('colorshop_lock_down_admin')=='yes' && ! ( current_user_can('edit_posts') || current_user_can('manage_colorshop') ) ) {
			$show_admin_bar = false;
		}

		return $show_admin_bar;
	}
}


/**
 * Load the cart upon login
 *
 * @access public
 * @param mixed $user_login
 * @param mixed $user
 * @return void
 */
function colorshop_load_persistent_cart( $user_login, $user ) {
	global $colorshop;

	$saved_cart = get_user_meta( $user->ID, '_colorshop_persistent_cart', true );

	if ( $saved_cart )
		if ( empty( $colorshop->session->cart ) || ! is_array( $colorshop->session->cart ) || sizeof( $colorshop->session->cart ) == 0 )
			$colorshop->session->cart = $saved_cart['cart'];
}

/**
 * is_colorshop - Returns true if on a page which uses ColorShop templates (cart and checkout are standard pages with shortcodes and thus are not included)
 *
 * @access public
 * @return bool
 */
function is_colorshop() {
	return ( is_shop() || is_product_category() || is_product_tag() || is_product() ) ? true : false;
}

if ( ! function_exists( 'is_shop' ) ) {

	/**
	 * is_shop - Returns true when viewing the product type archive (shop).
	 *
	 * @access public
	 * @return bool
	 */
	function is_shop() {
		return ( is_post_type_archive( 'product' ) || is_page( colorshop_get_page_id( 'shop' ) ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_product_category' ) ) {

	/**
	 * is_product_category - Returns true when viewing a product category.
	 *
	 * @access public
	 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_product_category( $term = '' ) {
		return is_tax( 'product_cat', $term );
	}
}

if ( ! function_exists( 'is_product_tag' ) ) {

	/**
	 * is_product_tag - Returns true when viewing a product tag.
	 *
	 * @access public
	 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_product_tag( $term = '' ) {
		return is_tax( 'product_tag', $term );
	}
}

if ( ! function_exists( 'is_product' ) ) {

	/**
	 * is_product - Returns true when viewing a single product.
	 *
	 * @access public
	 * @return bool
	 */
	function is_product() {
		return is_singular( array( 'product' ) );
	}
}

if ( ! function_exists( 'is_cart' ) ) {

	/**
	 * is_cart - Returns true when viewing the cart page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_cart() {
		return is_page( colorshop_get_page_id( 'cart' ) );
	}
}

if ( ! function_exists( 'is_checkout' ) ) {

	/**
	 * is_checkout - Returns true when viewing the checkout page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_checkout() {
		return ( is_page( colorshop_get_page_id( 'checkout' ) ) || is_page( colorshop_get_page_id( 'pay' ) ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_edit_address' ) ) {

	/**
	 * is_edit_address - Returns true when viewing the edit address page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_edit_address() {
		return ( is_page( colorshop_get_page_id( 'edit_address' ) ) ) ? true : false;
	}
}

if ( ! function_exists( 'is_account_page' ) ) {

	/**
	 * is_account_page - Returns true when viewing an account page.
	 *
	 * @access public
	 * @return bool
	 */
	function is_account_page() {
		return is_page( colorshop_get_page_id( 'myaccount' ) ) || is_page( colorshop_get_page_id( 'edit_address' ) ) || is_page( colorshop_get_page_id( 'view_order' ) ) || is_page( colorshop_get_page_id( 'change_password' ) ) || is_page( colorshop_get_page_id( 'lost_password' ) ) || apply_filters( 'colorshop_is_account_page', false ) ? true : false;
	}
}

if ( ! function_exists( 'is_order_received_page' ) ) {

    /**
    * is_order_received_page - Returns true when viewing the order received page.
    *
    * @access public
    * @return bool
    */
    function is_order_received_page() {
        return ( is_page( colorshop_get_page_id( 'thanks' ) ) ) ? true : false;
    }
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @access public
	 * @return bool
	 */
	function is_ajax() {
		if ( defined('DOING_AJAX') )
			return true;

		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) ? true : false;
	}
}

if ( ! function_exists( 'is_filtered' ) ) {

	/**
	 * is_filtered - Returns true when filtering products using layered nav or price sliders.
	 *
	 * @access public
	 * @return bool
	 */
	function is_filtered() {
		global $_chosen_attributes;

		return ( sizeof( $_chosen_attributes ) > 0 || ( isset( $_GET['max_price'] ) && isset( $_GET['min_price'] ) ) ) ? true : false;
	}
}


/**
 * Get template part (for templates like the shop-loop).
 *
 * @access public
 * @param mixed $slug
 * @param string $name (default: '')
 * @return void
 */
function colorshop_get_template_part( $slug, $name = '' ) {
	global $colorshop;
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/colorshop/slug-name.php
	if ( $name )
		$template = locate_template( array ( "{$slug}-{$name}.php", "{$colorshop->template_url}{$slug}-{$name}.php" ) );
	
	// Get default slug-name.php
	if ( !$template && $name && file_exists( $colorshop->plugin_path() . "/templates/{$slug}-{$name}.php" ) )
		$template = $colorshop->plugin_path() . "/templates/{$slug}-{$name}.php";

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/colorshop/slug.php
	if ( !$template )
		$template = locate_template( array ( "{$slug}.php", "{$colorshop->template_url}{$slug}.php" ) );

	if ( $template )
		load_template( $template, false );
}


/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @access public
 * @param mixed $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return void
 */
function colorshop_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	global $colorshop;

	if ( $args && is_array($args) )
		extract( $args );

	$located = colorshop_locate_template( $template_name, $template_path, $default_path );

	do_action( 'colorshop_before_template_part', $template_name, $template_path, $located );

	include( $located );

	do_action( 'colorshop_after_template_part', $template_name, $template_path, $located );
}


/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @access public
 * @param mixed $template_name
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return string
 */
function colorshop_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	global $colorshop;

	if ( ! $template_path ) $template_path = $colorshop->template_url;
	if ( ! $default_path ) $default_path = $colorshop->plugin_path() . '/templates/';

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( ! $template )
		$template = $default_path . $template_name;

	// Return what we found
	return apply_filters('colorshop_locate_template', $template, $template_name, $template_path);
}


/**
 * Get Base Currency Code.
 *
 * @access public
 * @return string
 */
function get_colorshop_currency() {
	return apply_filters( 'colorshop_currency', get_option('colorshop_currency') );
}


/**
 * Get full list of currency codes.
 *
 * @access public
 * @return void
 */
function get_colorshop_currencies() {
	return array_unique(
		apply_filters( 'colorshop_currencies',
			array(
				'AUD' => __( 'Australian Dollars', 'colorshop' ),
				'BRL' => __( 'Brazilian Real', 'colorshop' ),
				'CAD' => __( 'Canadian Dollars', 'colorshop' ),
				'RMB' => __( 'Chinese Yuan', 'colorshop' ),
				'CZK' => __( 'Czech Koruna', 'colorshop' ),
				'DKK' => __( 'Danish Krone', 'colorshop' ),
				'EUR' => __( 'Euros', 'colorshop' ),
				'HKD' => __( 'Hong Kong Dollar', 'colorshop' ),
				'HUF' => __( 'Hungarian Forint', 'colorshop' ),
				'IDR' => __( 'Indonesia Rupiah', 'colorshop' ),
				'INR' => __( 'Indian Rupee', 'colorshop' ),
				'ILS' => __( 'Israeli Shekel', 'colorshop' ),
				'JPY' => __( 'Japanese Yen', 'colorshop' ),
				'KRW' => __( 'South Korean Won', 'colorshop' ),
				'MYR' => __( 'Malaysian Ringgits', 'colorshop' ),
				'MXN' => __( 'Mexican Peso', 'colorshop' ),
				'NOK' => __( 'Norwegian Krone', 'colorshop' ),
				'NZD' => __( 'New Zealand Dollar', 'colorshop' ),
				'PHP' => __( 'Philippine Pesos', 'colorshop' ),
				'PLN' => __( 'Polish Zloty', 'colorshop' ),
				'GBP' => __( 'Pounds Sterling', 'colorshop' ),
				'RON' => __( 'Romanian Leu', 'colorshop' ),
				'SGD' => __( 'Singapore Dollar', 'colorshop' ),
				'ZAR' => __( 'South African rand', 'colorshop' ),
				'SEK' => __( 'Swedish Krona', 'colorshop' ),
				'CHF' => __( 'Swiss Franc', 'colorshop' ),
				'TWD' => __( 'Taiwan New Dollars', 'colorshop' ),
				'THB' => __( 'Thai Baht', 'colorshop' ),
				'TRY' => __( 'Turkish Lira', 'colorshop' ),
				'USD' => __( 'US Dollars', 'colorshop' ),
			)
		)
	);
}

/**
 * Get Currency symbol.
 *
 * @access public
 * @param string $currency (default: '')
 * @return string
 */
function get_colorshop_currency_symbol( $currency = '' ) {
	if ( ! $currency )
		$currency = get_colorshop_currency();

	switch ( $currency ) {
		case 'BRL' :
			$currency_symbol = '&#82;&#36;';
			break;
		case 'AUD' :
		case 'CAD' :
		case 'MXN' :
		case 'NZD' :
		case 'HKD' :
		case 'SGD' :
		case 'USD' :
			$currency_symbol = '&#36;';
			break;
		case 'EUR' :
			$currency_symbol = '&euro;';
			break;
		case 'CNY' :
		case 'RMB' :
		case 'JPY' :
			$currency_symbol = '&yen;';
			break;
		case 'KRW' : $currency_symbol = '&#8361;'; break;
		case 'TRY' : $currency_symbol = '&#84;&#76;'; break;
		case 'NOK' : $currency_symbol = '&#107;&#114;'; break;
		case 'ZAR' : $currency_symbol = '&#82;'; break;
		case 'CZK' : $currency_symbol = '&#75;&#269;'; break;
		case 'MYR' : $currency_symbol = '&#82;&#77;'; break;
		case 'DKK' : $currency_symbol = '&#107;&#114;'; break;
		case 'HUF' : $currency_symbol = '&#70;&#116;'; break;
		case 'IDR' : $currency_symbol = 'Rp'; break;
		case 'INR' : $currency_symbol = '&#8377;'; break;
		case 'ILS' : $currency_symbol = '&#8362;'; break;
		case 'PHP' : $currency_symbol = '&#8369;'; break;
		case 'PLN' : $currency_symbol = '&#122;&#322;'; break;
		case 'SEK' : $currency_symbol = '&#107;&#114;'; break;
		case 'CHF' : $currency_symbol = '&#67;&#72;&#70;'; break;
		case 'TWD' : $currency_symbol = '&#78;&#84;&#36;'; break;
		case 'THB' : $currency_symbol = '&#3647;'; break;
		case 'GBP' : $currency_symbol = '&pound;'; break;
		case 'RON' : $currency_symbol = 'lei'; break;
		default    : $currency_symbol = ''; break;
	}

	return apply_filters( 'colorshop_currency_symbol', $currency_symbol, $currency );
}


/**
 * Format the price with a currency symbol.
 *
 * @access public
 * @param float $price
 * @param array $args (default: array())
 * @return string
 */
function colorshop_price( $price, $args = array() ) {
	global $colorshop;

	extract( shortcode_atts( array(
		'ex_tax_label' 	=> '0'
	), $args ) );

	$return          = '';
	$num_decimals    = (int) get_option( 'colorshop_price_num_decimals' );
	$currency_pos    = get_option( 'colorshop_currency_pos' );
	$currency_symbol = get_colorshop_currency_symbol();
	$decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'colorshop_price_decimal_sep' ) ), ENT_QUOTES );
	$thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'colorshop_price_thousand_sep' ) ), ENT_QUOTES );

	$price           = apply_filters( 'raw_colorshop_price', (double) $price );
	$price           = number_format( $price, $num_decimals, $decimal_sep, $thousands_sep );

	if ( get_option( 'colorshop_price_trim_zeros' ) == 'yes' && $num_decimals > 0 )
		$price = colorshop_trim_zeros( $price );

	$return = '<span class="amount">' . sprintf( get_colorshop_price_format(), $currency_symbol, $price ) . '</span>';

	if ( $ex_tax_label && get_option( 'colorshop_calc_taxes' ) == 'yes' )
		$return .= ' <small>' . $colorshop->countries->ex_tax_or_vat() . '</small>';

	return $return;
}

function get_colorshop_price_format() {
	$currency_pos = get_option( 'colorshop_currency_pos' );

	switch ( $currency_pos ) {
		case 'left' :
			$format = '%1$s%2$s';
		break;
		case 'right' :
			$format = '%2$s%1$s';
		break;
		case 'left_space' :
			$format = '%1$s&nbsp;%2$s';
		break;
		case 'right_space' :
			$format = '%2$s&nbsp;%1$s';
		break;
	}

	return apply_filters( 'colorshop_price_format', $format, $currency_pos );
}


/**
 * Trim trailing zeros off prices.
 *
 * @access public
 * @param mixed $price
 * @return string
 */
function colorshop_trim_zeros( $price ) {
	return preg_replace( '/' . preg_quote( get_option( 'colorshop_price_decimal_sep' ), '/' ) . '0++$/', '', $price );
}


/**
 * Formal decimal numbers - format to 4 dp and remove trailing zeros.
 *
 * @access public
 * @param mixed $number
 * @return string
 */
function colorshop_format_decimal( $number, $dp = '' ) {
	if ( $dp == '' )
		$dp = intval( get_option( 'colorshop_price_num_decimals' ) );

	$number = number_format( (float) $number, (int) $dp, '.', '' );

	if ( strstr( $number, '.' ) )
		$number = rtrim( rtrim( $number, '0' ), '.' );

	return $number;
}


/**
 * Formal total costs - format to the number of decimal places for the base currency.
 *
 * @access public
 * @param mixed $number
 * @return float
 */
function colorshop_format_total( $number ) {
	return number_format( (float) $number, (int) get_option( 'colorshop_price_num_decimals' ), '.', '' );
}


/**
 * Clean variables
 *
 * @access public
 * @param string $var
 * @return string
 */
function colorshop_clean( $var ) {
	//return sanitize_text_field( $var );
	return $var;
}


/**
 * Merge two arrays
 *
 * @access public
 * @param array $a1
 * @param array $a2
 * @return array
 */
function colorshop_array_overlay( $a1, $a2 ) {
    foreach( $a1 as $k => $v ) {
        if ( ! array_key_exists( $k, $a2 ) )
        	continue;
        if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
            $a1[ $k ] = colorshop_array_overlay( $v, $a2[ $k ] );
        } else {
            $a1[ $k ] = $a2[ $k ];
        }
    }
    return $a1;
}


/**
 * Get top term
 * http://wordpress.stackexchange.com/questions/24794/get-the-the-top-level-parent-of-a-custom-taxonomy-term
 *
 * @access public
 * @param int $term_id
 * @param string $taxonomy
 * @return int
 */
function colorshop_get_term_top_most_parent( $term_id, $taxonomy ) {
    // start from the current term
    $parent  = get_term_by( 'id', $term_id, $taxonomy );
    // climb up the hierarchy until we reach a term with parent = '0'
    while ( $parent->parent != '0' ) {
        $term_id = $parent->parent;
        $parent  = get_term_by( 'id', $term_id, $taxonomy);
    }
    return $parent;
}


/**
 * Variation Formatting
 *
 * Gets a formatted version of variation data or item meta
 *
 * @access public
 * @param string $variation (default: '')
 * @param bool $flat (default: false)
 * @return string
 */
function colorshop_get_formatted_variation( $variation = '', $flat = false ) {
	global $colorshop;

	if ( is_array( $variation ) ) {

		if ( ! $flat )
			$return = '<dl class="variation">';
		else
			$return = '';

		$variation_list = array();

		foreach ( $variation as $name => $value ) {

			if ( ! $value )
				continue;

			// If this is a term slug, get the term's nice name
            if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $name ) ) ) ) {
            	$term = get_term_by( 'slug', $value, esc_attr( str_replace( 'attribute_', '', $name ) ) );
            	if ( ! is_wp_error( $term ) && $term->name )
            		$value = $term->name;
            }

			if ( $flat )
				$variation_list[] = $colorshop->attribute_label(str_replace('attribute_', '', $name)).': '.$value;
			else
				$variation_list[] = '<dt>'.$colorshop->attribute_label(str_replace('attribute_', '', $name)).':</dt><dd>'.$value.'</dd>';
		}

		if ( $flat )
			$return .= implode( ', ', $variation_list );
		else
			$return .= implode( '', $variation_list );

		if ( ! $flat )
			$return .= '</dl>';

		return $return;

	}
}

if ( ! function_exists( 'colorshop_rgb_from_hex' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @return string
	 */
	function colorshop_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb['R'] = hexdec( $color{0}.$color{1} );
		$rgb['G'] = hexdec( $color{2}.$color{3} );
		$rgb['B'] = hexdec( $color{4}.$color{5} );
		return $rgb;
	}
}

if ( ! function_exists( 'colorshop_hex_darker' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function colorshop_hex_darker( $color, $factor = 30 ) {
		$base = colorshop_rgb_from_hex( $color );
		$color = '#';

		foreach ($base as $k => $v) :
	        $amount = $v / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v - $amount;

	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
		endforeach;

		return $color;
	}
}

if ( ! function_exists( 'colorshop_hex_lighter' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function colorshop_hex_lighter( $color, $factor = 30 ) {
		$base = colorshop_rgb_from_hex( $color );
		$color = '#';

	    foreach ($base as $k => $v) :
	        $amount = 255 - $v;
	        $amount = $amount / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v + $amount;

	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
	   	endforeach;

	   	return $color;
	}
}

if ( ! function_exists( 'colorshop_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark colour on a background colour
	 *
	 * @access public
	 * @param mixed $color
	 * @param string $dark (default: '#000000')
	 * @param string $light (default: '#FFFFFF')
	 * @return string
	 */
	function colorshop_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
	    //return ( hexdec( $color ) > 0xffffff / 2 ) ? $dark : $light;
	    $hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );
		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155 ? $dark : $light;
	}
}

if ( ! function_exists( 'colorshop_format_hex' ) ) {

	/**
	 * Format string as hex
	 *
	 * @access public
	 * @param string $hex
	 * @return string
	 */
	function colorshop_format_hex( $hex ) {

	    $hex = trim( str_replace( '#', '', $hex ) );

	    if ( strlen( $hex ) == 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	    }

	    if ( $hex ) return '#' . $hex;
	}
}


/**
 * Exclude order comments from queries and RSS
 *
 * This code should exclude shop_order comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and
 * shop managers can view orders anyway.
 *
 * The frontend view order pages get around this filter by using remove_filter('comments_clauses', 'colorshop_exclude_order_comments');
 *
 * @access public
 * @param array $clauses
 * @return array
 */
function colorshop_exclude_order_comments( $clauses ) {
	global $wpdb, $typenow, $pagenow;

	if ( is_admin() && ( $typenow == 'shop_order' || $pagenow == 'edit-comments.php' ) && current_user_can( 'manage_colorshop' ) )
		return $clauses; // Don't hide when viewing orders in admin

	if ( ! $clauses['join'] )
		$clauses['join'] = '';

	if ( ! strstr( $clauses['join'], "JOIN $wpdb->posts" ) )
		$clauses['join'] .= " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";

	if ( $clauses['where'] )
		$clauses['where'] .= ' AND ';

	$clauses['where'] .= " $wpdb->posts.post_type NOT IN ('shop_order') ";

	return $clauses;
}

add_filter( 'comments_clauses', 'colorshop_exclude_order_comments', 10, 1);


/**
 * Exclude order comments from queries and RSS
 *
 * @access public
 * @param string $join
 * @return string
 */
function colorshop_exclude_order_comments_from_feed_join( $join ) {
	global $wpdb;

    if ( ! $join )
    	$join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";

    return $join;
}

add_action( 'comment_feed_join', 'colorshop_exclude_order_comments_from_feed_join' );


/**
 * Exclude order comments from queries and RSS
 *
 * @access public
 * @param string $where
 * @return string
 */
function colorshop_exclude_order_comments_from_feed_where( $where ) {
	global $wpdb;

    if ( $where )
    	$where .= ' AND ';

	$where .= " $wpdb->posts.post_type NOT IN ('shop_order') ";

    return $where;
}

add_action( 'comment_feed_where', 'colorshop_exclude_order_comments_from_feed_where' );


/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 *
 * @access public
 * @param int $order_id
 * @return void
 */
function colorshop_downloadable_product_permissions( $order_id ) {
	global $wpdb;

	if (get_post_meta( $order_id, __( 'Download Permissions Granted', 'colorshop' ), true)==1) return; // Only do this once

	$order = new CS_Order( $order_id );

	if (sizeof($order->get_items())>0) foreach ($order->get_items() as $item) :

		if ($item['product_id']>0) :
			$_product = $order->get_product_from_item( $item );

			if ( $_product->exists() && $_product->is_downloadable() ) :

				$product_id = ($item['variation_id']>0) ? $item['variation_id'] : $item['product_id'];

				$file_download_paths = apply_filters( 'colorshop_file_download_paths', get_post_meta( $product_id, '_file_paths', true ), $product_id, $order_id, $item );
				if ( ! empty( $file_download_paths ) ) {
					foreach ( $file_download_paths as $download_id => $file_path ) {
						colorshop_downloadable_file_permission( $download_id, $product_id, $order );
					}
				}

			endif;

		endif;

	endforeach;

	update_post_meta( $order_id,  __( 'Download Permissions Granted', 'colorshop' ), 1);
}

add_action('colorshop_order_status_completed', 'colorshop_downloadable_product_permissions');

/**
 * Grant downloadable product access to the file identified by $download_id
 *
 * @access public
 * @param string $download_id file identifier
 * @param int $product_id product identifier
 * @param CS_Order $order the order
 */
function colorshop_downloadable_file_permission( $download_id, $product_id, $order ) {
	global $wpdb;

	$user_email = $order->billing_email;

	$limit = trim( get_post_meta( $product_id, '_download_limit', true ) );
	$expiry = trim( get_post_meta( $product_id, '_download_expiry', true ) );

    $limit = empty( $limit ) ? '' : (int) $limit;

    // Default value is NULL in the table schema
	$expiry = empty( $expiry ) ? null : (int) $expiry;

	if ( $expiry ) $expiry = date_i18n( "Y-m-d", strtotime( 'NOW + ' . $expiry . ' DAY' ) );

    $data = array(
    	'download_id'			=> $download_id,
		'product_id' 			=> $product_id,
		'user_id' 				=> $order->user_id,
		'user_email' 			=> $user_email,
		'order_id' 				=> $order->id,
		'order_key' 			=> $order->order_key,
		'downloads_remaining' 	=> $limit,
		'access_granted'		=> current_time( 'mysql' ),
		'download_count'		=> 0
    );

    $format = array(
    	'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%d'
    );

    if ( ! is_null( $expiry ) ) {
        $data['access_expires'] = $expiry;
        $format[] = '%s';
    }

	// Downloadable product - give access to the customer
    $wpdb->insert( $wpdb->prefix . 'colorshop_downloadable_product_permissions',
        $data,
        $format
    );
}

if ( get_option('colorshop_downloads_grant_access_after_payment') == 'yes' )
	add_action( 'colorshop_order_status_processing', 'colorshop_downloadable_product_permissions' );


/**
 * Order Status completed - This is a paying customer
 *
 * @access public
 * @param int $order_id
 * @return void
 */
function colorshop_paying_customer( $order_id ) {

	$order = new CS_Order( $order_id );

	if ( $order->user_id > 0 ) {
		update_user_meta( $order->user_id, 'paying_customer', 1 );

		$old_count = absint( get_user_meta( $order->user_id, '_order_count', true ) );
		update_user_meta( $order->user_id, '_order_count', $old_count + 1 );
	}

}

add_action( 'colorshop_order_status_completed', 'colorshop_paying_customer' );


/**
 * Filter to allow product_cat in the permalinks for products.
 *
 * @access public
 * @param string $permalink The existing permalink URL.
 * @param object $post
 * @return string
 */
function colorshop_product_post_type_link( $permalink, $post ) {
    // Abort if post is not a product
    if ( $post->post_type !== 'product' )
    	return $permalink;

    // Abort early if the placeholder rewrite tag isn't in the generated URL
    if ( false === strpos( $permalink, '%' ) )
    	return $permalink;

    // Get the custom taxonomy terms in use by this post
    $terms = get_the_terms( $post->ID, 'product_cat' );

    if ( empty( $terms ) ) {
    	// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
        $product_cat = _x( 'uncategorized', 'slug', 'colorshop' );
    } else {
    	// Replace the placeholder rewrite tag with the first term's slug
        $first_term = array_shift( $terms );
        $product_cat = $first_term->slug;
    }

    $find = array(
    	'%year%',
    	'%monthnum%',
    	'%day%',
    	'%hour%',
    	'%minute%',
    	'%second%',
    	'%post_id%',
    	'%category%',
    	'%product_cat%'
    );

    $replace = array(
    	date_i18n( 'Y', strtotime( $post->post_date ) ),
    	date_i18n( 'm', strtotime( $post->post_date ) ),
    	date_i18n( 'd', strtotime( $post->post_date ) ),
    	date_i18n( 'H', strtotime( $post->post_date ) ),
    	date_i18n( 'i', strtotime( $post->post_date ) ),
    	date_i18n( 's', strtotime( $post->post_date ) ),
    	$post->ID,
    	$product_cat,
    	$product_cat
    );

    $replace = array_map( 'sanitize_title', $replace );

    $permalink = str_replace( $find, $replace, $permalink );

    return $permalink;
}

add_filter( 'post_type_link', 'colorshop_product_post_type_link', 10, 2 );



/**
 * Add term ordering to get_terms
 *
 * It enables the support a 'menu_order' parameter to get_terms for the product_cat taxonomy.
 * By default it is 'ASC'. It accepts 'DESC' too
 *
 * To disable it, set it ot false (or 0)
 *
 * @access public
 * @param array $clauses
 * @param array $taxonomies
 * @param array $args
 * @return array
 */
function colorshop_terms_clauses( $clauses, $taxonomies, $args ) {
	global $wpdb, $colorshop;

	// No sorting when menu_order is false
	if ( isset($args['menu_order']) && $args['menu_order'] == false ) return $clauses;

	// No sorting when orderby is non default
	if ( isset($args['orderby']) && $args['orderby'] != 'name' ) return $clauses;

	// No sorting in admin when sorting by a column
	if ( is_admin() && isset($_GET['orderby']) ) return $clauses;

	// wordpress should give us the taxonomies asked when calling the get_terms function. Only apply to categories and pa_ attributes
	$found = false;
	foreach ( (array) $taxonomies as $taxonomy ) :
		if ( strstr($taxonomy, 'pa_') || in_array( $taxonomy, apply_filters( 'colorshop_sortable_taxonomies', array( 'product_cat' ) ) ) ) :
			$found = true;
			break;
		endif;
	endforeach;
	if (!$found) return $clauses;

	// Meta name
	if ( ! empty( $taxonomies[0] ) && strstr($taxonomies[0], 'pa_') ) {
		$meta_name =  'order_' . esc_attr($taxonomies[0]);
	} else {
		$meta_name = 'order';
	}

	// query fields
	if ( strpos('COUNT(*)', $clauses['fields']) === false ) $clauses['fields']  .= ', tm.* ';

	//query join
	$clauses['join'] .= " LEFT JOIN {$wpdb->colorshop_termmeta} AS tm ON (t.term_id = tm.colorshop_term_id AND tm.meta_key = '". $meta_name ."') ";

	// default to ASC
	if ( ! isset($args['menu_order']) || ! in_array( strtoupper($args['menu_order']), array('ASC', 'DESC')) ) $args['menu_order'] = 'ASC';

	$order = "ORDER BY CAST(tm.meta_value AS SIGNED) " . $args['menu_order'];

	if ( $clauses['orderby'] ):
		$clauses['orderby'] = str_replace('ORDER BY', $order . ',', $clauses['orderby'] );
	else:
		$clauses['orderby'] = $order;
	endif;

	return $clauses;
}

add_filter( 'terms_clauses', 'colorshop_terms_clauses', 10, 3);


/**
 * colorshop_get_product_terms function.
 *
 * Gets product terms in the order they are defined in the backend.
 *
 * @access public
 * @param mixed $object_id
 * @param mixed $taxonomy
 * @param mixed $fields ids, names, slugs, all
 * @return array
 */
function colorshop_get_product_terms( $object_id, $taxonomy, $fields = 'all' ) {

	if ( ! taxonomy_exists( $taxonomy ) )
		return array();

	$terms 			= array();
	$object_terms 	= get_the_terms( $object_id, $taxonomy );
	$all_terms 		= array_flip( get_terms( $taxonomy, array( 'menu_order' => 'ASC', 'fields' => 'ids' ) ) );

	switch ( $fields ) {
		case 'names' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term->name;
			break;
		case 'ids' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term->term_id;
			break;
		case 'slugs' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term->slug;
			break;
		case 'all' :
			foreach ( $object_terms as $term )
				$terms[ $all_terms[ $term->term_id ] ] = $term;
			break;
	}

	ksort( $terms );

	return $terms;
}


/**
 * ColorShop Dropdown categories
 *
 * Stuck with this until a fix for http://core.trac.wordpress.org/ticket/13258
 * We use a custom walker, just like WordPress does
 *
 * @access public
 * @param int $show_counts (default: 1)
 * @param int $hierarchical (default: 1)
 * @param int $show_uncategorized (default: 1)
 * @return string
 */
function colorshop_product_dropdown_categories( $show_counts = 1, $hierarchical = 1, $show_uncategorized = 1, $orderby = '' ) {
	global $wp_query, $colorshop;

	include_once( $colorshop->plugin_path() . '/classes/walkers/class-product-cat-dropdown-walker.php' );

	$r = array();
	$r['pad_counts'] 	= 1;
	$r['hierarchical'] 	= $hierarchical;
	$r['hide_empty'] 	= 1;
	$r['show_count'] 	= $show_counts;
	$r['selected'] 		= ( isset( $wp_query->query['product_cat'] ) ) ? $wp_query->query['product_cat'] : '';

	$r['menu_order'] = false;

	if ( $orderby == 'order' )
		$r['menu_order'] = 'asc';
	elseif ( $orderby )
		$r['orderby'] = $orderby;

	$terms = get_terms( 'product_cat', $r );

	if (!$terms) return;

	$output  = "<select name='product_cat' id='dropdown_product_cat'>";
	$output .= '<option value="" ' .  selected( isset( $_GET['product_cat'] ) ? $_GET['product_cat'] : '', '', false ) . '>'.__( 'Select a category', 'colorshop' ).'</option>';
	$output .= colorshop_walk_category_dropdown_tree( $terms, 0, $r );

	if ( $show_uncategorized )
		$output .= '<option value="0" ' . selected( isset( $_GET['product_cat'] ) ? $_GET['product_cat'] : '', '0', false ) . '>' . __( 'Uncategorized', 'colorshop' ) . '</option>';

	$output .="</select>";

	echo $output;
}


/**
 * Walk the Product Categories.
 *
 * @access public
 * @return void
 */
function colorshop_walk_category_dropdown_tree() {
	$args = func_get_args();

	// the user's options are the third parameter
	if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )
		$walker = new CS_Product_Cat_Dropdown_Walker;
	else
		$walker = $args[2]['walker'];

	return call_user_func_array(array( &$walker, 'walk' ), $args );
}


/**
 * ColorShop Term/Order item Meta API - set table name
 *
 * @access public
 * @return void
 */
function colorshop_taxonomy_metadata_wpdbfix() {
	global $wpdb;
	$termmeta_name = 'colorshop_termmeta';
	$itemmeta_name = 'colorshop_order_itemmeta';

	$wpdb->colorshop_termmeta = $wpdb->prefix . $termmeta_name;
	$wpdb->order_itemmeta = $wpdb->prefix . $itemmeta_name;

	$wpdb->tables[] = 'colorshop_termmeta';
	$wpdb->tables[] = 'order_itemmeta';
}

add_action( 'init', 'colorshop_taxonomy_metadata_wpdbfix', 0 );
add_action( 'switch_blog', 'colorshop_taxonomy_metadata_wpdbfix', 0 );


/**
 * ColorShop Term Meta API - Update term meta
 *
 * @access public
 * @param mixed $term_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param string $prev_value (default: '')
 * @return bool
 */
function update_colorshop_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'colorshop_term', $term_id, $meta_key, $meta_value, $prev_value );
}


/**
 * ColorShop Term Meta API - Add term meta
 *
 * @access public
 * @param mixed $term_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param bool $unique (default: false)
 * @return bool
 */
function add_colorshop_term_meta( $term_id, $meta_key, $meta_value, $unique = false ){
	return add_metadata( 'colorshop_term', $term_id, $meta_key, $meta_value, $unique );
}


/**
 * ColorShop Term Meta API - Delete term meta
 *
 * @access public
 * @param mixed $term_id
 * @param mixed $meta_key
 * @param string $meta_value (default: '')
 * @param bool $delete_all (default: false)
 * @return bool
 */
function delete_colorshop_term_meta( $term_id, $meta_key, $meta_value = '', $delete_all = false ) {
	return delete_metadata( 'colorshop_term', $term_id, $meta_key, $meta_value, $delete_all );
}


/**
 * ColorShop Term Meta API - Get term meta
 *
 * @access public
 * @param mixed $term_id
 * @param mixed $key
 * @param bool $single (default: true)
 * @return mixed
 */
function get_colorshop_term_meta( $term_id, $key, $single = true ) {
	return get_metadata( 'colorshop_term', $term_id, $key, $single );
}


/**
 * Move a term before the a	given element of its hierarchy level
 *
 * @access public
 * @param int $the_term
 * @param int $next_id the id of the next sibling element in save hierarchy level
 * @param string $taxonomy
 * @param int $index (default: 0)
 * @param mixed $terms (default: null)
 * @return int
 */
function colorshop_order_terms( $the_term, $next_id, $taxonomy, $index = 0, $terms = null ) {

	if( ! $terms ) $terms = get_terms($taxonomy, 'menu_order=ASC&hide_empty=0&parent=0' );
	if( empty( $terms ) ) return $index;

	$id	= $the_term->term_id;

	$term_in_level = false; // flag: is our term to order in this level of terms

	foreach ($terms as $term) {

		if( $term->term_id == $id ) { // our term to order, we skip
			$term_in_level = true;
			continue; // our term to order, we skip
		}
		// the nextid of our term to order, lets move our term here
		if(null !== $next_id && $term->term_id == $next_id) {
			$index++;
			$index = colorshop_set_term_order($id, $index, $taxonomy, true);
		}

		// set order
		$index++;
		$index = colorshop_set_term_order($term->term_id, $index, $taxonomy);

		// if that term has children we walk through them
		$children = get_terms($taxonomy, "parent={$term->term_id}&menu_order=ASC&hide_empty=0");
		if( !empty($children) ) {
			$index = colorshop_order_terms( $the_term, $next_id, $taxonomy, $index, $children );
		}
	}

	// no nextid meaning our term is in last position
	if( $term_in_level && null === $next_id )
		$index = colorshop_set_term_order($id, $index+1, $taxonomy, true);

	return $index;
}


/**
 * Set the sort order of a term
 *
 * @access public
 * @param int $term_id
 * @param int $index
 * @param string $taxonomy
 * @param bool $recursive (default: false)
 * @return int
 */
function colorshop_set_term_order( $term_id, $index, $taxonomy, $recursive = false ) {
	global $wpdb;

	$term_id 	= (int) $term_id;
	$index 		= (int) $index;

	// Meta name
	if (strstr($taxonomy, 'pa_')) :
		$meta_name =  'order_' . esc_attr($taxonomy);
	else :
		$meta_name = 'order';
	endif;

	update_colorshop_term_meta( $term_id, $meta_name, $index );

	if( ! $recursive ) return $index;

	$children = get_terms($taxonomy, "parent=$term_id&menu_order=ASC&hide_empty=0");

	foreach ( $children as $term ) {
		$index ++;
		$index = colorshop_set_term_order($term->term_id, $index, $taxonomy, true);
	}

	clean_term_cache( $term_id, $taxonomy );

	return $index;
}


/**
 * let_to_num function.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @access public
 * @param $size
 * @return int
 */
function colorshop_let_to_num( $size ) {
    $l 		= substr( $size, -1 );
    $ret 	= substr( $size, 0, -1 );
    switch( strtoupper( $l ) ) {
	    case 'P':
	        $ret *= 1024;
	    case 'T':
	        $ret *= 1024;
	    case 'G':
	        $ret *= 1024;
	    case 'M':
	        $ret *= 1024;
	    case 'K':
	        $ret *= 1024;
    }
    return $ret;
}


/**
 * colorshop_customer_bought_product
 *
 * Checks if a user (by email) has bought an item
 *
 * @access public
 * @param string $customer_email
 * @param int $user_id
 * @param int $product_id
 * @return bool
 */
function colorshop_customer_bought_product( $customer_email, $user_id, $product_id ) {
	global $wpdb;

	$emails = array();

	if ( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		$emails[] = $user->user_email;
	}

	if ( is_email( $customer_email ) )
		$emails[] = $customer_email;

	if ( sizeof( $emails ) == 0 )
		return false;

	return $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT( order_items.order_item_id )
		FROM {$wpdb->prefix}colorshop_order_items as order_items
		LEFT JOIN {$wpdb->prefix}colorshop_order_itemmeta AS itemmeta ON order_items.order_item_id = itemmeta.order_item_id
		LEFT JOIN {$wpdb->postmeta} AS postmeta ON order_items.order_id = postmeta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON postmeta.post_id = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		WHERE 	term.slug IN ('" . implode( "','", apply_filters( 'colorshop_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		(
					(
						itemmeta.meta_key = '_variation_id'
						AND itemmeta.meta_value = %s
					) OR (
						itemmeta.meta_key = '_product_id'
						AND itemmeta.meta_value = %s
					)
		)
		AND 	(
					(
						postmeta.meta_key = '_billing_email'
						AND postmeta.meta_value IN ( '" . implode( "','", array_unique( $emails ) ) . "' )
					) OR (
						postmeta.meta_key = '_customer_user'
						AND postmeta.meta_value = %s AND postmeta.meta_value > 0
					)
				)
	", $product_id, $product_id, $user_id ) );
}

/**
 * Return the count of processing orders.
 *
 * @access public
 * @return int
 */
function colorshop_processing_order_count() {
	if ( false === ( $order_count = get_transient( 'colorshop_processing_order_count' ) ) ) {
		$order_statuses = get_terms( 'shop_order_status' );
	    $order_count = false;
	    foreach ( $order_statuses as $status ) {
	        if( $status->slug === 'processing' ) {
	            $order_count += $status->count;
	            break;
	        }
	    }
	    $order_count = apply_filters( 'colorshop_admin_menu_count', intval( $order_count ) );
		set_transient( 'colorshop_processing_order_count', $order_count );
	}

	return $order_count;
}


/**
 * Get capabilities for ColorShop - these are assigned to admin/shop manager during installation or reset
 *
 * @access public
 * @return void
 */
function colorshop_get_core_capabilities() {
	$capabilities = array();

	$capabilities['core'] = array(
		"manage_colorshop",
		"view_colorshop_reports"
	);

	$capability_types = array( 'product', 'shop_order', 'shop_coupon' );

	foreach( $capability_types as $capability_type ) {

		$capabilities[ $capability_type ] = array(

			// Post type
			"edit_{$capability_type}",
			"read_{$capability_type}",
			"delete_{$capability_type}",
			"edit_{$capability_type}s",
			"edit_others_{$capability_type}s",
			"publish_{$capability_type}s",
			"read_private_{$capability_type}s",
			"delete_{$capability_type}s",
			"delete_private_{$capability_type}s",
			"delete_published_{$capability_type}s",
			"delete_others_{$capability_type}s",
			"edit_private_{$capability_type}s",
			"edit_published_{$capability_type}s",

			// Terms
			"manage_{$capability_type}_terms",
			"edit_{$capability_type}_terms",
			"delete_{$capability_type}_terms",
			"assign_{$capability_type}_terms"
		);
	}

	return $capabilities;
}


/**
 * colorshop_init_roles function.
 *
 * @access public
 * @return void
 */
function colorshop_init_roles() {
	global $wp_roles;

	if ( class_exists('WP_Roles') )
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

	if ( is_object( $wp_roles ) ) {

		// Customer role
		add_role( 'customer', __( 'Customer', 'colorshop' ), array(
		    'read' 						=> true,
		    'edit_posts' 				=> false,
		    'delete_posts' 				=> false
		) );

		// Shop manager role
		add_role( 'shop_manager', __( 'Shop Manager', 'colorshop' ), array(
			'level_9'                => true,
			'level_8'                => true,
			'level_7'                => true,
			'level_6'                => true,
			'level_5'                => true,
			'level_4'                => true,
			'level_3'                => true,
			'level_2'                => true,
			'level_1'                => true,
			'level_0'                => true,
		    'read'                   => true,
		    'read_private_pages'     => true,
		    'read_private_posts'     => true,
		    'edit_users'             => true,
		    'edit_posts'             => true,
		    'edit_pages'             => true,
		    'edit_published_posts'   => true,
		    'edit_published_pages'   => true,
		    'edit_private_pages'     => true,
		    'edit_private_posts'     => true,
		    'edit_others_posts'      => true,
		    'edit_others_pages'      => true,
		    'publish_posts'          => true,
		    'publish_pages'          => true,
		    'delete_posts'           => true,
		    'delete_pages'           => true,
		    'delete_private_pages'   => true,
		    'delete_private_posts'   => true,
		    'delete_published_pages' => true,
		    'delete_published_posts' => true,
		    'delete_others_posts'    => true,
		    'delete_others_pages'    => true,
		    'manage_categories'      => true,
		    'manage_links'           => true,
		    'moderate_comments'      => true,
		    'unfiltered_html'        => true,
		    'upload_files'           => true,
		   	'export'                 => true,
			'import'                 => true
		) );

		$capabilities = colorshop_get_core_capabilities();

		foreach( $capabilities as $cap_group ) {
			foreach( $cap_group as $cap ) {
				$wp_roles->add_cap( 'shop_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}
}

/**
 * colorshop_remove_roles function.
 *
 * @access public
 * @return void
 */
function colorshop_remove_roles() {
	global $wp_roles;

	if ( class_exists('WP_Roles') )
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

	if ( is_object( $wp_roles ) ) {

		$capabilities = colorshop_get_core_capabilities();

		foreach( $capabilities as $cap_group ) {
			foreach( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'shop_manager', $cap );
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}

		remove_role( 'customer' );
		remove_role( 'shop_manager' );
	}
}


/**
 * Add a item to an order (for example a line item).
 *
 * @access public
 * @param int $order_id
 * @param array $data
 * @return mixed
 */
function colorshop_add_order_item( $order_id, $item ) {
	global $wpdb;

	$order_id = absint( $order_id );

	if ( ! $order_id )
		return false;

	$defaults = array(
		'order_item_name' 		=> '',
		'order_item_type' 		=> 'line_item',
	);

	$item = wp_parse_args( $item, $defaults );

	$wpdb->insert(
		$wpdb->prefix . "colorshop_order_items",
		array(
			'order_item_name' 		=> $item['order_item_name'],
			'order_item_type' 		=> $item['order_item_type'],
			'order_id'				=> $order_id
		),
		array(
			'%s', '%s', '%d'
		)
	);

	$item_id = absint( $wpdb->insert_id );

	do_action( 'colorshop_new_order_item', $item_id, $item, $order_id );

	return $item_id;
}

/**
 * colorshop_delete_order_item function.
 *
 * @access public
 * @param int $item_id
 * @return bool
 */
function colorshop_delete_order_item( $item_id ) {
	global $wpdb;

	$item_id = absint( $item_id );

	if ( ! $item_id )
		return false;

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}colorshop_order_items WHERE order_item_id = %d", $item_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}colorshop_order_itemmeta WHERE order_item_id = %d", $item_id ) );

	do_action( 'colorshop_delete_order_item', $item_id );

	return true;
}

/**
 * ColorShop Order Item Meta API - Update term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param string $prev_value (default: '')
 * @return bool
 */
function colorshop_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'order_item', $item_id, $meta_key, $meta_value, $prev_value );
}


/**
 * ColorShop Order Item Meta API - Add term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param bool $unique (default: false)
 * @return bool
 */
function colorshop_add_order_item_meta( $item_id, $meta_key, $meta_value, $unique = false ){
	return add_metadata( 'order_item', $item_id, $meta_key, $meta_value, $unique );
}


/**
 * ColorShop Order Item Meta API - Delete term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param string $meta_value (default: '')
 * @param bool $delete_all (default: false)
 * @return bool
 */
function colorshop_delete_order_item_meta( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
	return delete_metadata( 'order_item', $item_id, $meta_key, $meta_value, $delete_all );
}


/**
 * ColorShop Order Item Meta API - Get term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $key
 * @param bool $single (default: true)
 * @return mixed
 */
function colorshop_get_order_item_meta( $item_id, $key, $single = true ) {
	return get_metadata( 'order_item', $item_id, $key, $single );
}

/**
 * ColorShop Date Format - Allows to change date format for everything ColorShop
 *
 * @access public
 * @return string
 */
function colorshop_date_format() {
	return apply_filters( 'colorshop_date_format', get_option( 'date_format' ) );
}

/**
 * ColorShop Time Format - Allows to change time format for everything ColorShop
 *
 * @access public
 * @return string
 */
function colorshop_time_format() {
	return apply_filters( 'colorshop_time_format', get_option( 'time_format' ) );
}

/**
 * Function for recounting product terms, ignoring hidden products.
 *
 * @access public
 * @param mixed $term
 * @param mixed $taxonomy
 * @return void
 */
function _colorshop_term_recount( $terms, $taxonomy, $callback = true, $terms_are_term_taxonomy_ids = true ) {
	global $wpdb;

	// Standard callback
	if ( $callback )
		_update_post_term_count( $terms, $taxonomy );

	// Stock query
	if ( get_option( 'colorshop_hide_out_of_stock_items' ) == 'yes' ) {
		$stock_join  = "LEFT JOIN {$wpdb->postmeta} AS meta_stock ON posts.ID = meta_stock.post_id";
		$stock_query = "
		AND (
			meta_stock.meta_key = '_stock_status'
			AND
			meta_stock.meta_value = 'instock'
		)";
	} else {
		$stock_query = $stock_join = '';
	}

	// Main query
	$count_query = $wpdb->prepare( "
		SELECT COUNT( DISTINCT posts.ID ) FROM {$wpdb->posts} as posts

		LEFT JOIN {$wpdb->postmeta} AS meta_visibility ON posts.ID = meta_visibility.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		$stock_join

		WHERE 	posts.post_status 	= 'publish'
		AND 	posts.post_type 	= 'product'
		AND 	(
			meta_visibility.meta_key = '_visibility'
			AND
			meta_visibility.meta_value IN ( 'visible', 'catalog' )
		)
		AND 	tax.taxonomy	= %s
		$stock_query
	", $taxonomy->name );

	// Store terms + counts here
	$term_counts = array();
	$counted_terms = array();
	$maybe_count_parents = array();

	// Pre-process term taxonomy ids
	if ( $terms_are_term_taxonomy_ids ) {
		$term_ids = array();

		foreach ( (array) $terms as $term ) {
			$the_term = $wpdb->get_row("SELECT term_id, parent FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = $term AND taxonomy = '$taxonomy->name'");
			$term_ids[ $the_term->term_id ] = $the_term->parent;
		}

		$terms = $term_ids;
	}

	// Count those terms!
	foreach ( (array) $terms as $term_id => $parent_id ) {

		$term_ids 		= array();

		if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {

			// Grab the parents to count later
			$parent = $parent_id;

			while ( ! empty( $parent ) && $parent > 0 ) {
				$maybe_count_parents[] = $parent;

				$parent_term = get_term_by( 'id', $parent, $taxonomy->name );

				if ( $parent_term )
					$parent = $parent_term->parent;
				else
					$parent = 0;
			}

			// We need to get the $term's hierarchy so we can count its children too
			$term_ids   = get_term_children( $term_id, $taxonomy->name );
		}

		$term_ids[] = absint( $term_id );

		// Generate term query
		$term_query = 'AND term.term_id IN ( ' . implode( ',', $term_ids ) . ' )';

		// Get the count
		$count = $wpdb->get_var( $count_query . $term_query );

		update_colorshop_term_meta( $term_id, 'product_count_' . $taxonomy->name, absint( $count ) );

		$counted_terms[] = $term_id;
	}

	// Re-count parents
	if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {

		$terms = array_diff( $maybe_count_parents, $counted_terms );

		foreach ( (array) $terms as $term ) {

			$term_ids   = get_term_children( $term, $taxonomy->name );
			$term_ids[] = $term;

			// Generate term query
			$term_query = 'AND term.term_id IN ( ' . implode( ',', $term_ids ) . ' )';

			// Get the count
			$count = $wpdb->get_var( $count_query . $term_query );

			update_colorshop_term_meta( $term, 'product_count_' . $taxonomy->name, absint( $count ) );
		}

	}
}

/**
 * colorshop_recount_after_stock_change function.
 *
 * @access public
 * @return void
 */
function colorshop_recount_after_stock_change( $product_id ) {

	$product_terms = get_the_terms( $product_id, 'product_cat' );

	if ( $product_terms ) {
		foreach ( $product_terms as $term )
			$product_cats[ $term->term_id ] = $term->parent;

		_colorshop_term_recount( $product_cats, get_taxonomy( 'product_cat' ), false, false );

	}

	$product_terms = get_the_terms( $product_id, 'product_tag' );

	if ( $product_terms ) {
		foreach ( $product_terms as $term )
			$product_tags[ $term->term_id ] = $term->parent;

		_colorshop_term_recount( $product_tags, get_taxonomy( 'product_tag' ), false, false );

	}

}

add_action( 'colorshop_product_set_stock_status', 'colorshop_recount_after_stock_change' );

/**
 * colorshop_change_term_counts function.
 * Overrides the original term count for product categories and tags with the product count
 * that takes catalog visibility into account.
 *
 * @access public
 * @param mixed $terms
 * @param mixed $taxonomies
 * @param mixed $args
 * @return void
 */
function colorshop_change_term_counts( $terms, $taxonomies, $args ) {

	if ( ! in_array( $taxonomies[0], apply_filters( 'colorshop_change_term_counts', array( 'product_cat', 'product_tag' ) ) ) )
		return $terms;

	$term_counts = $o_term_counts = get_transient( 'cs_term_counts' );

	foreach ( $terms as &$term ) {
		// If the original term count is zero, there's no way the product count could be higher.
		if ( empty( $term->count ) ) continue;

		$term_counts[ $term->term_id ] = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : get_colorshop_term_meta( $term->term_id, 'product_count_' . $taxonomies[0] , true );

		if ( $term_counts[ $term->term_id ] != '' )
			$term->count = $term_counts[ $term->term_id ];
	}

	// Update transient
	if ( $term_counts != $o_term_counts )
		set_transient( 'cs_term_counts', $term_counts );

	return $terms;
}

if ( ! is_admin() && ! is_ajax() )
	add_filter( 'get_terms', 'colorshop_change_term_counts', 10, 3 );

/**
 * Function which handles the start and end of scheduled sales via cron.
 *
 * @access public
 * @return void
 */
function colorshop_scheduled_sales() {
	global $colorshop, $wpdb;

	// Sales which are due to start
	$product_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
		LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
		LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
		WHERE postmeta.meta_key = '_sale_price_dates_from'
		AND postmeta_2.meta_key = '_price'
		AND postmeta_3.meta_key = '_sale_price'
		AND postmeta.meta_value > 0
		AND postmeta.meta_value < %s
		AND postmeta_2.meta_value != postmeta_3.meta_value
	", current_time( 'timestamp' ) ) );

	if ( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			$sale_price = get_post_meta( $product_id, '_sale_price', true );

			if ( $sale_price ) {
				update_post_meta( $product_id, '_price', $sale_price );
			} else {
				// No sale price!
				update_post_meta( $product_id, '_sale_price_dates_from', '' );
				update_post_meta( $product_id, '_sale_price_dates_to', '' );
			}

			$colorshop->clear_product_transients( $product_id );

			$parent = wp_get_post_parent_id( $product_id );

			// Sync parent
			if ( $parent ) {
				// We can force varaible product price to sync up by removing their min price meta
				delete_post_meta( $parent, 'min_variation_price' );

				// Grouped products need syncing via a function
				$this_product = get_product( $product_id );
				if ( $this_product->is_type( 'simple' ) )
					$this_product->grouped_product_sync();

				$colorshop->clear_product_transients( $parent );
			}
		}
	}

	// Sales which are due to end
	$product_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
		LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
		LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
		WHERE postmeta.meta_key = '_sale_price_dates_to'
		AND postmeta_2.meta_key = '_price'
		AND postmeta_3.meta_key = '_regular_price'
		AND postmeta.meta_value > 0
		AND postmeta.meta_value < %s
		AND postmeta_2.meta_value != postmeta_3.meta_value
	", current_time( 'timestamp' ) ) );

	if ( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			$regular_price = get_post_meta( $product_id, '_regular_price', true );

			update_post_meta( $product_id, '_price', $regular_price );
			update_post_meta( $product_id, '_sale_price', '' );
			update_post_meta( $product_id, '_sale_price_dates_from', '' );
			update_post_meta( $product_id, '_sale_price_dates_to', '' );

			$colorshop->clear_product_transients( $product_id );

			$parent = wp_get_post_parent_id( $product_id );

			// Sync parent
			if ( $parent ) {
				// We can force variable product price to sync up by removing their min price meta
				delete_post_meta( $parent, 'min_variation_price' );

				// Grouped products need syncing via a function
				$this_product = get_product( $product_id );
				if ( $this_product->is_type( 'simple' ) )
					$this_product->grouped_product_sync();

				$colorshop->clear_product_transients( $parent );
			}
		}
	}
}

add_action( 'colorshop_scheduled_sales', 'colorshop_scheduled_sales' );


/**
 * colorshop_cancel_unpaid_orders function.
 *
 * @access public
 * @return void
 */
function colorshop_cancel_unpaid_orders() {
	global $wpdb;

	$held_duration = get_option( 'colorshop_hold_stock_minutes' );

	if ( $held_duration < 1 || get_option( 'colorshop_manage_stock' ) != 'yes' )
		return;

	$date = date( "Y-m-d H:i:s", strtotime( '-' . absint( $held_duration ) . ' MINUTES', current_time( 'timestamp' ) ) );

	$unpaid_orders = $wpdb->get_col( $wpdb->prepare( "
		SELECT posts.ID
		FROM {$wpdb->posts} AS posts
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	posts.post_type   = 'shop_order'
		AND 	posts.post_status = 'publish'
		AND 	tax.taxonomy      = 'shop_order_status'
		AND		term.slug	      IN ('pending')
		AND 	posts.post_modified < %s
	", $date ) );

	if ( $unpaid_orders ) {
		foreach ( $unpaid_orders as $unpaid_order ) {
			$order = new CS_Order( $unpaid_order );

			if ( apply_filters( 'colorshop_cancel_unpaid_order', true, $order ) )
				$order->update_status( 'cancelled', __( 'Unpaid order cancelled - time limit reached.', 'colorshop' ) );
		}
	}

	wp_clear_scheduled_hook( 'colorshop_cancel_unpaid_orders' );
	wp_schedule_single_event( time() + ( absint( $held_duration ) * 60 ), 'colorshop_cancel_unpaid_orders' );
}

add_action( 'colorshop_cancel_unpaid_orders', 'colorshop_cancel_unpaid_orders' );

function colorshop_check_publish_pin($post_id) {
	$args = array(
			'post_id' => $post_id,
			'user_id' => get_current_user_id(),
			'type' => 'product_pin'
	);
	$my_comments = get_comments($args);
	return count($my_comments) > 0;
}