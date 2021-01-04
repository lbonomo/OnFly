<?php
/**
 * Main file of plugin.
 *
 * @package OnFly
 * @version 0.0.1
 *
 * Plugin Name: On Fly
 * Plugin URI: https://lucasbonomo.com/wordpress
 * Description: List of Quotes
 * Author: Lucas Bonomo
 * Version: 0.0.1
 * Author URI: https://lucasbonomo.com/
 */

add_action( 'init', 'custom_woo' );



/**
 * Execute on init.
 */
function custom_woo() {

	// add_filter( 'woocommerce_sale_flash', '__return_false' );
	// add_filter( 'woocommerce_variation_is_visible', '__return_false' );

	// Elimino el precio del loop principal.
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

	// Elimino el boton agregar al carrito en el loop principal.
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');

	// Por temas del http en el Json server.
	// add_filter( 'https_local_ssl_verify', '__return_false' );
	// add_filter( 'https_ssl_verify', '__return_false' );
	// add_filter( 'block_local_requests', '__return_false' );
}

add_action( 'woocommerce_before_single_product_summary', 'onfly_woocommerce_before_single_product' );
// add_action( 'woocommerce_before_single_product', 'onfly_woocommerce_before_single_product' );
/**
 * Search a product in Json Server
 */
function onfly_woocommerce_before_single_product() {
	if ( function_exists( 'query_product' ) ) {
		query_product();
	}
}

/**
 * Query Json server.
 */
function query_product() {
	global $product;
	$slug = $product->get_slug();
	// Objener y comparar la fecha de la ultima actalizacion.
	// $product->get_date_modified() es un objeto WC_DateTime
	// con ->getTimestamp lo paso a un entero.
	$last_update = $product->get_date_modified()->getTimestamp();
	$ttl         = 1; // En horas.
//    $ref         = time() - ( $ttl * 3600 );
	$ref         = time() - ( 1 ); // Para prueba solo un minuto

	if ( $last_update <= $ref ) {
		// Si la ultima actualizacion es menor a la referencia, consulta el API.
		
	// Datos de configuración y seguridad
		
	$endpoint = 'http://myurl';
	$username = 'user';
	$password = 'password';

	$body_out = [
    	'post_id'  => $slug,
	];
 
	$response = wp_remote_post( $endpoint, array(
    	'body' => $body_out,
    	'headers' => array(
        'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),),) );
		
		// add_filter( 'https_local_ssl_verify', '__return_false' );
		// add_filter( 'https_ssl_verify', '__return_false' );
		// add_filter( 'block_local_requests', '__return_false' );
		// https://developer.wordpress.org/reference/functions/wp_remote_get/ .


		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			// Body es un string. Convertir en diccionario.
			$body = json_decode( $response['body'], true );
			update_product( $product, $body );
		};

		// Si el precio esta "desactualizado" y el API no responde, ocultar el precio.
		if ( is_wp_error( $response ) ) {
			hidden_price();
		};

	}
}


/**
 * Oculto el precio del producto.
 */
function hidden_price() {
	echo '<style>div.onfly-alert { padding: 1rem; margin: 1rem; border: 1px red solid; text-align: center; }</style>';
	echo "<div class='onfly-alert'>No se pudo obtener el precio del producto</div>";

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
		$price = '<span class="woocommerce-Price-amount amount">Consultar</span>';
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
	
	$wc_product->set_short_description( $json_data['short_description'] ); // No se actualiza la vista si la página está en caché, 
	                                                                       // vaciando la caché sí muestra la descripción actualizada.
	
	wc_update_product_stock( $wc_product->id, $json_data['stock'] );
	$wc_product->save();
}
