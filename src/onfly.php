<?php
/**
 * Main file of plugin.
 *
 * @package OnFly
 * @version 0.1.0
 *
 * Plugin Name: On Fly for WooCommerce
 * Plugin URI: https://lucasbonomo.com/wordpress
 * Description: To update products data from a Restful API (on fly)
 * Author: Lucas Bonomo
 * Version: 0.1.0
 * Author URI: https://lucasbonomo.com/
 */

/**
 * Just for PHPCS.
 */

require_once 'class-onflyadmin.php';

// Admin panel.
$onfly_admin = new OnFlyAdmin();

add_action( 'init', 'custom_woo' );
/**
 * Execute on init.
 */
function custom_woo() {
	// Elimino el precio del loop principal.
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

	// Elimino el boton agregar al carrito en el loop principal.
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
}

// https://github.com/woocommerce/woocommerce/blob/8d83f2dcc5b1414774c26a22af2321e972f4ff9a/templates/content-single-product.php .
// https://github.com/woocommerce/woocommerce/blob/8d83f2dcc5b1414774c26a22af2321e972f4ff9a/templates/content-single-product.php .
add_action( 'woocommerce_before_single_product_summary', 'onfly_query_product', 5 );

/**
 * Query Json server.
 */
function onfly_query_product() {
	global $product;

	switch ( get_option( 'onfly-field' ) ) {
		case 'slug':
			$field = $product->get_slug();
			break;
		case 'sku':
			$field = $product->get_sku();
			break;
	}

	// Objener y comparar la fecha de la ultima actalizacion.
	// $product->get_date_modified() es un objeto WC_DateTime
	// con ->getTimestamp lo paso a un entero.
	$last_update = $product->get_date_modified()->getTimestamp();
	// Vida en segundo de los datos del producto.
	$ttl = get_option( 'onfly-ttl' );

	// Si la ultima actualizacion es menor a la referencia, consulta el API.
	if ( $last_update <= ( time() - $ttl ) ) {

		// URL del endpoint de productos.
		$endpoint = get_option( 'onfly-endpoint-url' ) . '/' . $field;

		// Authorization.
		switch ( get_option( 'onfly-auth' ) ) {

			case 'none':
				$response = wp_remote_get( $endpoint );
				break;

			case 'basic':
				// TODO.
				$body_out = array( 'post_id' => $field );
				// Datos de configuración y seguridad.
				$username = get_option( 'onfly-auth-username' );
				$password = get_option( 'onfly-auth-password' );
				// @codingStandardsIgnoreStart
				$response = wp_remote_post(
					$endpoint,
					array(
						'body'    => $body_out,
						'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ) ),
					)
				);
				// @codingStandardsIgnoreEnd
				break;
		}

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			// El servidor responde.
			if ( 200 === $response['response']['code'] ) {
				// WP_DEBUG ? error_log( print_r( $response, true ) ) : none;
				// Body es un string. Convertir en diccionario.
				$body = json_decode( $response['body'], true );
				update_product( $product, $body );
			} else {
				// El servidor respondio, pero no lo esperado.
				WP_DEBUG ? error_log( print_r( $response, true ) ) : none; // phpcs:ignore
				hidden_price();
			}
		} else {
			// El servidor no respondio.
			WP_DEBUG ? error_log( print_r( $response, true ) ) : none; // phpcs:ignore
			hidden_price();
		};
	}
}


/**
 * Oculto el precio del producto.
 */
function hidden_price() {
	echo '<style>div.onfly-alert { padding: 1rem; margin: 1rem; border: 1px red solid; text-align: center; }</style>';
	echo "<div class='onfly-alert'>" . esc_attr( get_option( 'onfly-warning-message' ) ) . '</div>';

	// Elimino el agregar al carrito cuando no se pudo actualizar el precio.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

	/**
	* Filtro el precio del articulo y muestro un string. TODO - Agregar el string a la configuracion.
	*/
	add_filter( 'woocommerce_get_price_html', 'ask_us_price', 10, 2 );
	/**
	 * Filtro.
	 *
	 * @param string  $price Precio.
	 * @param integer $product integer Product ID.
	 */
	function ask_us_price( $price, $product ) {
		$price = '<span class="woocommerce-Price-amount amount">' . get_option( 'onfly-ask-us-str' ) . '</span>';
		return $price;
	}
}

/**
 * Actualiza el producto.
 *
 * @param object $wc_product Datos del producto.
 * @param array  $json_data Datos del producto.
 */
function update_product( $wc_product, $json_data ) {
	$wc_product->set_price( $json_data['regular_price'] );
	$wc_product->set_regular_price( $json_data['regular_price'] );
	$wc_product->set_sale_price( $json_data['sale_price'] );

	if ( in_array( 'short_description', array_keys( $json_data ), true ) ) {
		$short_description = $json_data['short_description'];
		$wc_product->set_short_description( $short_description );
	}
	$wc_product->set_stock_quantity( $json_data['stock'] );
	$wc_product->save();
}
