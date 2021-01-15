<?php
/**
 * Panel de control.
 *
 * @package OnFly
 */

/**
 * Admin class.
 */
class OnFlyAdmin {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'onfly_admin' ) );
		add_action( 'admin_menu', array( $this, 'onfly_menu' ) );
	}

	// Salidas HTML permitidas.
	const ALLOWEDHTML = array(
		'input'  => array(
			'type'        => true,
			'name'        => true,
			'class'       => true,
			'value'       => true,
			'placeholder' => true,
		),
		'select' => array(
			'name'  => true,
			'class' => true,
			'value' => true,
		),
		'option' => array(
			'value'    => true,
			'selected' => true,
		),
		'p'      => array(
			'class' => true,
		),
	);

	/**
	 * Admin settings
	 */
	public function onfly_admin() {

		add_settings_section(
			'onfly-settings-section',
			'Opciones de OnFly for WooCommerce',
			array( $this, 'onfly_section_callback' ), // Nonce, action, and option_page fields for a settings page.
			'onfly-settings'
		);

		register_setting( 'onfly-settings', 'onfly-endpoint-url' );
		register_setting( 'onfly-settings', 'onfly-ttl' );
		register_setting( 'onfly-settings', 'onfly-field' );
		register_setting( 'onfly-settings', 'onfly-warning-message' );
		register_setting( 'onfly-settings', 'onfly-ask-us-str' );
		register_setting( 'onfly-settings', 'onfly-auth' );
		register_setting( 'onfly-settings', 'onfly-auth-username' );
		register_setting( 'onfly-settings', 'onfly-auth-password' );

		// URL del endpoint de productos.
		add_settings_field(
			'onfly-endpoint-url',
			'URL',
			array( $this, 'draw_input' ),
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-endpoint-url',
				'description' => 'URL del endpoint de productos. Ej: http://localhost:3000/products',
				'class'       => 'regular-text',
				'type'        => 'url',
			)
		);

		// TTL.
		add_settings_field(
			'onfly-ttl',
			'Tiempo de vida del producto',
			array( $this, 'draw_input' ),
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-ttl',
				'description' => 'Validez de los datos del producto en segundos',
				'class'       => 'regular-text',
				'type'        => 'number',
			)
		);

		// Field.
		add_settings_field(
			'onfly-field',
			'Campo',
			array( $this, 'draw_select' ), // Función encargada de pintar el campo.
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-field',
				'description' => 'Capo a utilizar para identificar el producto en el API. Ej: http://localhost:3000/products/[campo]',
				'class'       => 'regular-text',
				'options'     => array(
					'sku'  => 'SKU',
					'slug' => 'slug',
				),
			)
		);

		// Ask Us (string).
		add_settings_field(
			'onfly-ask-us-str',
			'Texto',
			array( $this, 'draw_input' ), // Función encargada de pintar el campo.
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-ask-us-str',
				'description' => 'Texto a utilizar en lugar del precio del producto en caso de error',
				'class'       => 'regular-text',
				'type'        => 'text',
			)
		);

		// Warning message.
		add_settings_field(
			'onfly-warning-message',
			'Mensaje',
			array( $this, 'draw_input' ), // Función encargada de pintar el campo.
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-warning-message',
				'description' => 'Mensaje a mostrar en caso de tener problema de conexión con el API',
				'class'       => 'regular-text',
				'type'        => 'text',
				'placeholder' => 'No se puedo obtener la información del producto',
			)
		);

		// Auth.
		add_settings_field(
			'onfly-auth',
			'Autenticación',
			array( $this, 'draw_select' ), // Función encargada de pintar el campo.
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-auth',
				'description' => 'Metodo de autenticación',
				'class'       => 'regular-text',
				'options'     => array(
					'none'  => 'None',
					'basic' => 'Basic',
				),
			)
		);

		// Auth username.
		add_settings_field(
			'onfly-auth-username',
			'Usuario',
			array( $this, 'draw_input' ), // Función encargada de pintar el campo.
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-auth-username',
				'description' => 'Usuario para autenticación en el API',
				'class'       => 'regular-text',
				'type'        => 'text',
			)
		);

		// Auth password.
		add_settings_field(
			'onfly-auth-password',
			'Contraseña',
			array( $this, 'draw_input' ), // Función encargada de pintar el campo.
			'onfly-settings',             // Menu donde se muestra.
			'onfly-settings-section',     // Seccion donde se agraga.
			array(
				'label_for'   => 'onfly-auth-password',
				'description' => 'Contraseña para autenticación en el API',
				'class'       => 'regular-text',
				'type'        => 'password',
			)
		);

	}

	/**
	 * OnFly Menu
	 */
	public function onfly_menu() {
		add_options_page(
			'OnFly Config',
			'OnFly',
			'manage_options',
			'onfly-settings',
			array( $this, 'onfly_page_display' )
		);
	}

	/**
	 * OnFly page config
	 */
	public function onfly_page_display() {
		// Verifico permisos.
		if ( current_user_can( 'manage_options' ) ) {
			echo '<form action="options.php" method="post">';
			// Prints out all settings sections added to a particular settings page.
			do_settings_sections( 'onfly-settings' );
			submit_button( 'Grabar' );
			echo '</form>';
		}
	}

	/**
	 * Callback
	 */
	public function onfly_section_callback() {
		// Output nonce, action, and option_page fields for a settings page.
		settings_fields( 'onfly-settings' );
	}


	/**
	 * Funcion Callback del Input text.
	 *
	 *  @param array $args Settings values.
	 */
	public function draw_input( $args ) {
		$value       = get_option( $args['label_for'] );
		$value       = isset( $value ) ? esc_attr( $value ) : '';
		$name        = $args['label_for'];
		$type        = $args['type'];
		$description = $args['description'];
		$class       = $args['class'];
		$placeholder = in_array( 'placeholder', array_keys( $args ), true ) ? $args['placeholder'] : '';
		$html        = "<input type='$type' name='$name' class='$class' value='$value' placeholder='$placeholder' >";
		if ( null !== $description ) {
			$html .= "<p class='description'>$description</p>";
		}

		// Pinto el componente.
		echo wp_kses( $html, self::ALLOWEDHTML );
	}

	/**
	 * Funcion Callback del Input text.
	 *
	 *  @param array $args Settings values.
	 */
	public function draw_select( $args ) {
		$value       = get_option( $args['label_for'] );
		$value       = isset( $value ) ? esc_attr( $value ) : '';
		$name        = $args['label_for'];
		$options     = $args['options'];
		$description = $args['description'];
		$class       = $args['class'];
		$html        = "<select name='$name' class='$class' value='$value' >";

		foreach ( $options as $key => $option ) {
			$html .= "<option value='$key'" . ( ( $key === $value ) ? ' selected' : null ) . ">$option</option>";
		}

		$html .= '</select>';

		if ( null !== $description ) {
			$html .= "<p class='description'>$description</p>";
		}

		// Pinto el componente.
		echo wp_kses( $html, self::ALLOWEDHTML );
	}


}
