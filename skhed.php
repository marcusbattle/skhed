<?php
/**
 * Plugin Name: Skhed
 * Plugin URI: http://marcusbattle.com/plugins/skhed
 * Description: The coolest, most flexible appointments app you'll ever use
 * Version: 0.1.0
 * Author: Marcus Battle
 * Author URI: http://marcusbattle.com
 * Text Domain: shked
 * License: GPL2
 */


class Skhed {

	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'styles_and_scripts' ) );

		// Require CMB2 for Metabox support
		if ( file_exists(plugin_dir_path( __FILE__ ) . 'includes/CMB2/init.php' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/CMB2/init.php';
		}

		// Register Custom Post Types
		add_action( 'init', array( $this, 'post_type_products' ) );
		add_action( 'init', array( $this, 'post_type_services' ) );
		add_action( 'init', array( $this, 'post_type_availability' ) );
		add_action( 'init', array( $this, 'post_type_appointments' ) );

		// Register Metaboxes for Custom Post Types
		add_action( 'cmb2_init', array( $this, 'service_metaboxes' ) );
		add_action( 'cmb2_init', array( $this, 'availability_metaboxes' ) );
		add_action( 'cmb2_init', array( $this, 'appointment_metaboxes' ) );
		add_action( 'cmb2_init', array( $this, 'product_metaboxes' ) );

		// Register custom CMB2 fields for Post Type Editors
		add_action( 'cmb2_render_services_select', array( $this, 'cmb2_services_select' ), 10, 5 );
		add_action( 'cmb2_render_availability_select', array( $this, 'cmb2_availability_select' ), 10, 5 );
		add_action( 'cmb2_render_product_select', array( $this, 'cmb2_product_select' ), 10, 5 );
		add_action( 'cmb2_render_user_display', array( $this, 'cmb2_user_display' ), 10, 5 );
		add_action( 'cmb2_render_products_display', array( $this, 'cmb2_products_display' ), 10, 5 );

		//  Modify tables for Custom Post Types
		add_filter( 'manage_availability_posts_columns', array( $this, 'availability_columns' ) );
		add_action( 'manage_availability_posts_custom_column' , array( $this, 'availability_column_data' ), 10, 2 );
		add_filter( 'manage_appointment_posts_columns', array( $this, 'appointment_columns' ) );
		add_action( 'manage_appointment_posts_custom_column' , array( $this, 'appointment_column_data' ), 10, 2 );

		add_action( 'init', array( $this, 'submit_appointment' ) );
	}

	/**
	 * Registers all of the CSS and JS for use within the plugin
	 */
	public function styles_and_scripts() {
		wp_enqueue_script( 'skhed', plugin_dir_url( __FILE__ ) . '/assets/js/skhed.js', array('jquery'), '0.1.0', true );
	}

	/**
	 * Returns all of the 'products'
	 */
	public function get_services() {

		$meta = array();

		$services = $this->get( 'service', $meta );

		return $services;
	}

	/**
	 * Returns all of the 'products'
	 */
	public function get_products() {

		$meta = array();

		$products = $this->get( 'product', $meta );

		return $products;
	}

	/**
	 * Returns all of the 'availability'
	 */
	public function get_availability( ) {

		$meta = array( 
			'_availability_service' => get_the_ID() 
		);

		$args = array(
			'post_type' => 'availability',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array( $meta_query ),
			'orderby'   => 'meta_value',
			'meta_key' => '_availability_time_of_day',
			'meta_type'  => 'time',
			'order' => 'ASC'
		);

		$query = new WP_Query( $args );
		
		return $query->posts;

	}

	/**
	 * Returns all of the 'availability'
	 */
	public function get_appointments( $availability_id ) {

		$args = array(
			'post_type' => 'appointment',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array( 
				array(
					'key' => '_appointment_time',
					'value' => $availability_id,
					'compare' => '='
				) 
			),
		);

		$query = new WP_Query( $args );

		return $query->posts;

	}

	public function get( $object = 'services', $count = 10, $meta = array() ) {

		$meta_query = array();

		foreach ( $meta as $key => $value ) {
			
			array_push( $meta_query, array( 
				'key' => $key,
				'value' => $value,
				'compare' => '='
			) );

		}

		$args = array(
			'post_type' => $object,
			'posts_per_page' => $count,
			'post_status' => 'publish',
			'meta_query' => array( $meta_query )
		);

		$query = new WP_Query( $args );
		
		return $query->posts;

	}

	/**
	 * Registers the 'services' post type
	 */
	public function post_type_services() {

		$labels = array(
			'name'               => _x( 'Services', 'post type general name', 'skhed' ),
			'singular_name'      => _x( 'Service', 'post type singular name', 'skhed' ),
			'menu_name'          => _x( 'Services', 'admin menu', 'skhed' ),
			'name_admin_bar'     => _x( 'Service', 'add new on admin bar', 'skhed' ),
			'add_new'            => _x( 'Add New', 'service', 'skhed' ),
			'add_new_item'       => __( 'Add New Service', 'skhed' ),
			'new_item'           => __( 'New Service', 'skhed' ),
			'edit_item'          => __( 'Edit Service', 'skhed' ),
			'view_item'          => __( 'View Service', 'skhed' ),
			'all_items'          => __( 'All Services', 'skhed' ),
			'search_items'       => __( 'Search Services', 'skhed' ),
			'parent_item_colon'  => __( 'Parent Services:', 'skhed' ),
			'not_found'          => __( 'No services found.', 'skhed' ),
			'not_found_in_trash' => __( 'No services found in Trash.', 'skhed' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'services' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'comments' )
		);

		register_post_type( 'service', $args );

	}

	/**
	 * Registers metabox for 'service' post type
	 */
	public function service_metaboxes() {

		$prefix = '_service_';

		$services_metabox = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'Service Settings', 'skhed' ),
			'object_types'  => array( 'service' ),
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true,
		) );

		$services_metabox->add_field( array(
			'name' => __( 'Appointment Length', 'shked' ),
			'desc' => __( 'Enter 10 for 10 Minutes, 30 for 30 Minutes, 60 for 1 Hour', 'shked' ),
			'id'   => $prefix . 'length',
			'type' => 'text_small'
		) );

		$services_metabox->add_field( array(
			'name' => __( 'Break Time', 'shked' ),
			'desc' => __( 'Enter amount of time to pad between appointments', 'shked' ),
			'id'   => $prefix . 'break_length',
			'type' => 'text_small'
		) );

		$services_metabox->add_field( array(
			'name' => __( 'Location of Services', 'shked' ),
			'desc' => __( 'Enter address', 'shked' ),
			'id'   => $prefix . 'location',
			'type' => 'text'
		) );

		$services_metabox->add_field( array(
			'name' => __( 'Location Visible?', 'shked' ),
			'id'   => $prefix . 'location_visible',
			'type' => 'select',
			'options' => array(
				'1' => __( 'Yes', 'skhed' ),
		        '0'   => __( 'No', 'skhed' )
			)
		) );

		// Services provided offsite?
		$services_metabox->add_field( array(
			'name' => __( 'Is Delivery?', 'shked' ),
			'desc' => __( 'If the services is a delivery then user will be prompted for their address', 'skhed' ),
			'id'   => $prefix . 'is_delivery',
			'type' => 'select',
			'options' => array(
				'1' => __( 'Yes', 'skhed' ),
		        '0'   => __( 'No', 'skhed' )
			)
		) );

		$services_metabox->add_field( array(
			'name' => __( 'Price', 'shked' ),
			'id'   => $prefix . 'price',
			'type' => 'text_money'
		) );

		$services_metabox->add_field( array(
		    'name'    => 'Notification',
		    'desc'    => 'You can create a custom notification for your users to see',
		    'id'      => $prefix . 'notification',
		    'type'    => 'wysiwyg',
		    'options' => array(
		    	'textarea_rows' => 4
		    ),
		) );

		// Add-on Products
		$addon_products_metabox = new_cmb2_box( array(
			'id'           => $prefix . 'addon',
			'title'        => __( 'Add-on Products', 'skhed' ),
			'object_types' => array( 'service' ),
		) );
		
		$addon_products_id = $addon_products_metabox->add_field( array(
			// 'description' => __( 'Add-on Products to be displayed for checkout', 'skhed' ),
			'id'          => $prefix . 'addon_products',
			'type'        => 'group',
			'options'     => array(
				'group_title'   => __( 'Product {#}', 'skhed' ),
				'add_button'    => __( 'Add Another Product', 'skhed' ),
				'remove_button' => __( 'Remove Product', 'skhed' ),
				'sortable'      => true, 
			),
		) );
		
		$addon_products_metabox->add_group_field( $addon_products_id, array(
			'name'       => __( 'Product', 'skhed' ),
			'id'         => 'product_id',
			'type'       => 'product_select'
		) );

		$addon_products_metabox->add_group_field( $addon_products_id, array(
			'name'       => __( 'Default Quantity', 'skhed' ),
			'id'         => 'default_quantity',
			'type'       => 'text_small'
		) );
		

	}

	/**
	 * Registers the 'availability' post type
	 */
	public function post_type_availability() {

		$labels = array(
			'name'               => _x( 'Availability', 'post type general name', 'skhed' ),
			'singular_name'      => _x( 'Availability', 'post type singular name', 'skhed' ),
			'menu_name'          => _x( 'Availability', 'admin menu', 'skhed' ),
			'name_admin_bar'     => _x( 'Availability', 'add new on admin bar', 'skhed' ),
			'add_new'            => _x( 'Add New', 'service', 'skhed' ),
			'add_new_item'       => __( 'Add New Availability', 'skhed' ),
			'new_item'           => __( 'New Availability', 'skhed' ),
			'edit_item'          => __( 'Edit Availability', 'skhed' ),
			'view_item'          => __( 'View Availability', 'skhed' ),
			'all_items'          => __( 'All Availability', 'skhed' ),
			'search_items'       => __( 'Search Availability', 'skhed' ),
			'parent_item_colon'  => __( 'Parent Availability:', 'skhed' ),
			'not_found'          => __( 'No availability found.', 'skhed' ),
			'not_found_in_trash' => __( 'No availability found in Trash.', 'skhed' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'availability' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => false
		);

		register_post_type( 'availability', $args );

	}

	/**
	 * Registers metabox for 'services' post type
	 */
	public function availability_metaboxes() {

		$prefix = '_availability_';

		$availability_settings_box = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'Availability Settings', 'skhed' ),
			'object_types'  => array( 'availability' ),
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true,
		) );

		// Service Picker
		$availability_settings_box->add_field( array(
			'name' => __( 'Service', 'shked' ),
			'id'   => $prefix . 'service',
			'type' => 'services_select',
			'required' => true
		) );

		// Day of Week
		$availability_settings_box->add_field( array(
			'name' => __( 'Day Of Week', 'shked' ),
			'id'   => $prefix . 'day_of_week',
			'type' => 'select',
			'options' => array(
				'Sunday' => __( 'Sunday', 'skhed' ),
				'Monday' => __( 'Monday', 'skhed' ),
				'Tuesday' => __( 'Tuesday', 'skhed' ),
				'Wednesday' => __( 'Wednesday', 'skhed' ),
				'Thursday' => __( 'Thursday', 'skhed' ),
				'Friday' => __( 'Friday', 'skhed' ),
				'Saturday' => __( 'Saturday', 'skhed' )
			)
		) );

		// Time of Day
		$availability_settings_box->add_field( array(
			'name' => __( 'Time of Day', 'shked' ),
			'id'   => $prefix . 'time_of_day',
			'type' => 'text_time'
		) );

		// Is Active?
		$availability_settings_box->add_field( array(
			'name' => __( 'Is Active?', 'shked' ),
			'id'   => $prefix . 'is_active',
			'type' => 'select',
			'options' => array(
				'1' => __( 'Yes', 'skhed' ),
		        '0'   => __( 'No', 'skhed' )
			)
		) );

		// Capacity
		$availability_settings_box->add_field( array(
			'name' => __( 'Capacity', 'shked' ),
			'desc' => __( 'Enter the number of persons who can book this time', 'skhed' ),
			'id'   => $prefix . 'capacity',
			'type' => 'text_small'
		) );

	}

	/**
	 * Modify WP Tables to display custom columns
	 *
	 */
	public function availability_columns( $columns ) { 

		// unset( $columns['title'] );
		unset( $columns['date'] );

		$columns['service'] = __( 'Service', 'skhed' );
		$columns['day_of_week'] = __( 'Day of Week', 'skhed' );
		$columns['time_of_day'] = __( 'Time of Day', 'skhed' );
		$columns['capacity'] = __( 'Capacity', 'skhed' );
		$columns['is_active'] = __( 'Is Active?', 'skhed' );

		return $columns;

	}

	public function availability_column_data( $column, $post_id ) { 

		switch ( $column ) {

			case 'service':
				$service_id = get_post_meta( $post_id, '_availability_service', true );
				echo get_the_title( $service_id );
				break;
			
			case 'day_of_week':
				$day_of_week = get_post_meta( $post_id, '_availability_day_of_week', true );
				echo $day_of_week;
				break;

			case 'time_of_day':
				$time_of_day = get_post_meta( $post_id, '_availability_time_of_day', true );
				echo $time_of_day;
				break;

			case 'capacity':
				$capacity = get_post_meta( $post_id, '_availability_capacity', true );
				echo $capacity;
				break;

			case 'is_active':
				$is_active = get_post_meta( $post_id, '_availability_is_active', true );
				echo ( $is_active ) ? 'Yes' : 'No';
				break;

			default:
				# code...
				break;

		}

	}

	public function appointment_columns( $columns ) {

		unset( $columns['comments'] );
		unset( $columns['date'] );

		$columns['service'] = __( 'Service', 'skhed' );
		$columns['customer'] = __( 'Customer', 'skhed' );
		$columns['location'] = __( 'Location', 'skhed' );
		$columns['total_cost'] = __( 'Total Cost', 'skhed' );
		$columns['date'] = __( 'Date', 'skhed' );

		return $columns;

	}

	public function appointment_column_data( $column, $post_id ) {

		switch ( $column ) {

			case 'service':
				
				$service_id = get_post_meta( $post_id, '_appointment_service_id', true );
				echo ( $service_id ) ? get_the_title( $service_id ) : '--';
				break;

			case 'location':
				
				$delivery_location = get_post_meta( $post_id, '_appointment_delivery_location', true );
				echo ( $delivery_location ) ? $delivery_location : '--';

				break;

			case 'customer':
				
				$customer = get_post_meta( $post_id, '_appointment_customer', true );
				$first_name = isset( $customer['first_name'] ) ? $customer['first_name'] : '';
				$last_name = isset( $customer['last_name'] ) ? $customer['last_name'] : '';
				
				echo $first_name . ' ' . $last_name;

				break;

			case 'total_cost':
				
				$total_cost = get_post_meta( $post_id, '_appointment_total_cost', true );
				echo ( $total_cost ) ? "$" . $total_cost : '--';

				break;

			default:
				# code...
				break;

		}

	}

	/**
	 * Registers the 'appointments' post type
	 */
	public function post_type_appointments() {

		$labels = array(
			'name'               => _x( 'Appointments', 'post type general name', 'skhed' ),
			'singular_name'      => _x( 'Appointment', 'post type singular name', 'skhed' ),
			'menu_name'          => _x( 'Appointments', 'admin menu', 'skhed' ),
			'name_admin_bar'     => _x( 'Appointments', 'add new on admin bar', 'skhed' ),
			'add_new'            => _x( 'Add New', 'service', 'skhed' ),
			'add_new_item'       => __( 'Add New Appointment', 'skhed' ),
			'new_item'           => __( 'New Appointment', 'skhed' ),
			'edit_item'          => __( 'Edit Appointment', 'skhed' ),
			'view_item'          => __( 'View Appointment', 'skhed' ),
			'all_items'          => __( 'All Appointments', 'skhed' ),
			'search_items'       => __( 'Search Appointments', 'skhed' ),
			'parent_item_colon'  => __( 'Parent Appointment:', 'skhed' ),
			'not_found'          => __( 'No appointment found.', 'skhed' ),
			'not_found_in_trash' => __( 'No appointment found in Trash.', 'skhed' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'appointments' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'comments' )
		);

		register_post_type( 'appointment', $args );

	}

	/**
	 * Registers metabox for 'appointments' post type
	 */
	public function appointment_metaboxes() {

		$prefix = '_appointment_';

		// Define the Metabox
		$appointment_metabox = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'Appointment Details', 'skhed' ),
			'object_types'  => array( 'appointment' ),
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true,
		) );

		// Service
		$appointment_metabox->add_field( array(
			'name' => __( 'Service', 'shked' ),
			'id'   => $prefix . 'service_id',
			'type' => 'services_select'
		) );

		// Time of Appointment
		$appointment_metabox->add_field( array(
			'name' => __( 'Appointment Time', 'shked' ),
			'id'   => $prefix . 'time',
			'type' => 'availability_select'
		) );

		// Customer
		$appointment_metabox->add_field( array(
			'name' => __( 'Customer', 'shked' ),
			'id'   => $prefix . 'customer',
			'type' => 'user_display'
		) );

		// Delivery Location
		$appointment_metabox->add_field( array(
			'name' => __( 'Delivery Location', 'shked' ),
			'id'   => $prefix . 'delivery_location',
			'type' => 'text'
		) );

		// Products
		$appointment_metabox->add_field( array(
			'name' => __( 'Products', 'shked' ),
			'id'   => $prefix . 'products',
			'type' => 'products_display'
		) );

		$appointment_metabox->add_field( array(
			'name' => __( 'Special Comments', 'shked' ),
			'id'   => $prefix . 'comments',
			'type' => 'textarea'
		) );
	}

	/**
	 * Registers the 'products' post type
	 */
	public function post_type_products() {

		if ( ! post_type_exists( 'product' ) ) {

			$labels = array(
				'name'               => _x( 'Products', 'post type general name', 'skhed' ),
				'singular_name'      => _x( 'Product', 'post type singular name', 'skhed' ),
				'menu_name'          => _x( 'Products', 'admin menu', 'skhed' ),
				'name_admin_bar'     => _x( 'Products', 'add new on admin bar', 'skhed' ),
				'add_new'            => _x( 'Add New', 'service', 'skhed' ),
				'add_new_item'       => __( 'Add New Product', 'skhed' ),
				'new_item'           => __( 'New Product', 'skhed' ),
				'edit_item'          => __( 'Edit Product', 'skhed' ),
				'view_item'          => __( 'View Product', 'skhed' ),
				'all_items'          => __( 'All Products', 'skhed' ),
				'search_items'       => __( 'Search Products', 'skhed' ),
				'parent_item_colon'  => __( 'Parent Product:', 'skhed' ),
				'not_found'          => __( 'No product found.', 'skhed' ),
				'not_found_in_trash' => __( 'No product found in Trash.', 'skhed' )
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'products' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'thumbnail', 'comments' )
			);

			register_post_type( 'product', $args );

		}

	}

	/**
	 * Registers metabox for 'product' post type
	 */
	public function product_metaboxes() {

		$prefix = '_product_';

		// Define the Metabox
		$product_metabox = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'Product Settings', 'skhed' ),
			'object_types'  => array( 'product' ),
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true,
		) );

		// Time of Day
		$product_metabox->add_field( array(
			'name' => __( 'Price', 'shked' ),
			'id'   => $prefix . 'price',
			'type' => 'text_money'
		) );

		// Time of Day
		$product_metabox->add_field( array(
			'name' => __( 'Inventory', 'shked' ),
			'id'   => $prefix . 'inventory',
			'desc' => __( 'Users will not be able to order more than the number assigned here. O = unlimited', 'skhed' ),
			'type' => 'text_small'
		) );

	}

	/**
	 * Register the cmb field type 'services_select' 
	 *
	 * @param $field | 
	 * @param $escaped_value | 
	 * @param $object_id | 
	 * @param $object_type | 
	 * @param $field_type_object | 
	 */
	public function cmb2_services_select( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$service_options_html = '';

		if ( $services = $this->get_services() ) {

		    foreach( $services as $service ) {
		    
		    	$service_options_html .= '<option value="'. $service->ID .'" '. selected( $escaped_value, $service->ID, false ) .'>'. $service->post_title .'</option>';
		    }

		}

	    // Output the field type object
		echo $field_type_object->select( array(
			'options' => $service_options_html
		) );

	}

	/**
	 * Register the cmb field type 'availability_select' 
	 *
	 * @param $field | 
	 * @param $escaped_value | 
	 * @param $object_id | 
	 * @param $object_type | 
	 * @param $field_type_object | 
	 */
	public function cmb2_availability_select( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$availability = $this->get_availability();

		if ( $availability ) {

			$availability_options_html = '<option value="">--</option>';

		    foreach( $availability as $time ) {
		    	
		    	$day_of_week = get_post_meta( $time->ID, '_availability_day_of_week', true );
		    	$time_of_day = get_post_meta( $time->ID, '_availability_time_of_day', true );

		    	$availability_options_html .= '<option value="'. $time->ID .'" '. selected( $escaped_value, $time->ID, false ) .'>'. $day_of_week . ' ' . $time_of_day .'</option>';

		    }

		    // Output the field type object
			echo $field_type_object->select( array(
				'options' => $availability_options_html
			) );

		}

	}

	/**
	 * Register the cmb field type 'product_select' 
	 *
	 * @param $field | 
	 * @param $escaped_value | 
	 * @param $object_id | 
	 * @param $object_type | 
	 * @param $field_type_object | 
	 */
	public function cmb2_product_select( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$products = $this->get('product');

		if ( $products ) {

			$product_options_html = '<option value="">--</option>';

		    foreach( $products as $product ) {
		    	$product_options_html .= '<option value="'. $product->ID .'" '. selected( $escaped_value, $product->ID, false ) .'>'. $product->post_title .'</option>';
		    }

		    // Output the field type object
			echo $field_type_object->select( array(
				'options' => $product_options_html
			) );

		}

	}

	/**
	 * This CMB2 field displays user data
	 */
	public function cmb2_user_display( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$customer_meta = get_post_meta( $object_id, '_appointment_customer_meta', true );

		echo "<pre>";
		print_r( $escaped_value );
		print_r( $customer_meta ); 
		echo "</pre>";

	}

	public function cmb2_products_display( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$quantities = get_post_meta( $object_id, '_appointment_quantities', true );

		echo "<table>";
		echo "<thead>";
		echo "<tr><td><strong>Product</strong></td><td><strong>Quantity</strong></td></tr>";
		echo "</thead>";

		foreach ( $quantities as $product_id => $quantity ) {

			if ( $quantity ) {

				echo "<tr>";
				echo "<td>" . get_the_title( $product_id ) . "</td>";
				echo "<td>" . $quantity . "</td>";
				echo "</tr>";

			}

		}

		echo "</table>";

	}

	/**
	 * Processes a form submit to create a new appointment
	 */
	public function submit_appointment() {

		if ( ! empty( $_POST ) && isset( $_POST['action'] ) && ( $_POST['action'] == 'submit_appointment' ) ) {

			// echo "<pre>";
			// print_r( $_POST );
			// exit;

			$customer_data = isset( $_POST['customer'] ) ? $_POST['customer'] : array();
			$service_id = isset( $_POST['service_id'] ) ? $_POST['service_id'] : 0;
			$appointment_total_cost = isset( $_POST['appointment_total_cost'] ) ? $_POST['appointment_total_cost'] : 0;
			$availability_id = isset( $_POST['availability_id'] ) ? $_POST['availability_id'] : 0;
			$delivery_location = isset( $_POST['delivery_location'] ) ? $_POST['delivery_location'] : 0;
			$additional_comments = isset( $_POST['additional_comments'] ) ? $_POST['additional_comments'] : 0;
			$quantities = isset( $_POST['quantities'] ) ? $_POST['quantities'] : array();

			// Create customer if they don't exist in WP
			if ( ! $customer = $this->get_customer( $customer_data ) ) {
				$customer = $this->create_customer( $customer_data );
			}
			
			$customer_id = isset( $_POST['customer_id'] ) ? $_POST['customer_id'] : $customer->ID;

			if ( ! $availability_id ) {
				echo "Appointment Time undefined";
				exit;
			}

			$appointment_data = array(
				'post_type' => 'appointment',
				'post_title'    => 'Data',
				'post_status'   => 'publish',
			);

			// Insert the post into the database
			$appointment_created = wp_insert_post( $appointment_data );
			
			// Add some meta data
			if ( $appointment_created ) {

				update_post_meta( $appointment_created, '_appointment_service_id', $service_id );
				update_post_meta( $appointment_created, '_appointment_time', $availability_id );
				update_post_meta( $appointment_created, '_appointment_user_id', $customer_id );
				update_post_meta( $appointment_created, '_appointment_total_cost', $appointment_total_cost );
				update_post_meta( $appointment_created, '_appointment_delivery_location', $delivery_location );
				update_post_meta( $appointment_created, '_appointment_quantities', $quantities );
				update_post_meta( $appointment_created, '_appointment_additional_comments', $additional_comments );

				$customer = array(
					'first_name' => $customer_data['meta']['first_name'],
					'last_name' => $customer_data['meta']['last_name'],
					'email' => $customer_data['user_email'],
					'mobile' => $customer_data['meta']['mobile_number']
				);

				update_post_meta( $appointment_created, '_appointment_customer', $customer );

				echo "Your order has been successfully placed. You may close this window.";
				exit;

			}

			exit;

		}

	}

	/**
	 *
	 */
	public function get_customer( $customer_data = array() ) {

		$user_args['user_email'] = isset( $customer_data['user_email'] ) ? $customer_data['user_email'] : '';

		if ( empty( $user_args['user_email'] ) ) {
			$user_args['meta_key'] = 'mobile_number';
			$user_args['meta_value'] = isset( $customer_data['meta']['mobile_number'] ) ? $customer_data['meta']['mobile_number'] : '';

			$users = get_users( $user_args );
			$user = isset( $users[0] ) ? $users[0] : array();

		} else {

			$user = get_user_by( 'email', $user_args['user_email'] );

		}	
		
		if ( $user ) {

			$this->update_customer_meta( $user->ID, $customer_data );

		}

		return $user;

	}

	public function update_customer_meta( $customer_id = 0, $customer_data ) {

		if ( isset( $customer_data['meta'] ) ) {
			
			foreach( $customer_data['meta'] as $meta_key => $meta_value ) {
				update_user_meta( $customer_id, $meta_key, $meta_value );
			}

		}

	}

	/**
	 *
	 */
	public function create_customer( $customer_data = array() ) {

		$customer_data['user_login'] = isset( $customer_data['meta']['first_name'], $customer_data['meta']['last_name'] ) ? sanitize_user( $customer_data['meta']['first_name'] . $customer_data['meta']['last_name'] ) : '';

		$user_created = wp_insert_user( $customer_data );

		
		if ( $user_created ) {

			$this->update_customer_meta( $user_created, $customer_data );

			return $user_created;

		}

		return false;

	}

	public function check_if_available( $availability_id ) {

		$availability_capacity = get_post_meta( $availability_id, '_availability_capacity', true );

		$appointments = count( $this->get_appointments( $availability_id ) );

		if ( $availability_capacity === 0 ) {
			return true;
		}

		if ( $appointments >= $availability_capacity ) {
			return false;
		}

		return true;

	}


}

$skhed = new Skhed();