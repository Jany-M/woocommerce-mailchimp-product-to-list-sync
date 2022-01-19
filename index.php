<?php
/*
Plugin Name: WooCommerce - Mailchimp Product to List Sync
Description: Assign WooCommerce products to Mailchimp Audiences (old Lists) and sync customers to them upon completed purchase/payment.
Author: Shambix
Version: 1.0.1-beta
Author URI: https://www.shambix.com/
License: GPL V3
Text Domain: wmptls
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

class woo_mailchimp_product_class{
	private $_plugin_ver = '1.0.0';
	private $_db_ver = '1.0';
	public  $textdomain = 'wmptls';
	protected static $_instance = null;
	
	public function __construct(){
		global $wpdb;
		
		$this->BASE_PATH = rtrim( dirname(__FILE__), '/' ); 
		$this->BASE_URL = trim( plugin_dir_url( __FILE__ ), '/' ); 
		$this->LOGS_TABLE_NAME = $wpdb->prefix . $this->textdomain . '_logs';
		
		$this->includes();
		$this->declare_hooks();
	}	
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	protected function includes(){
		require_once $this->BASE_PATH . '/includes/functions.php';
	}
	
	protected function declare_hooks(){
		## Activation & deactivation
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));
		
		## Plugin related
		add_action( 'init', array($this, 'plugins_init') );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded') );

		## Endpoint
		add_action( 'init', array($this, 'endpoint_create'), 1, 0);
		add_action( 'template_redirect', array($this, 'endpoint_handler'), 1 );
		
		## Session
		add_action( 'init', array($this, 'init_session'), 1, 0);
	}
	
	public function get_version(){ 
		return $this->_plugin_ver . ' / ' . $this->_db_ver; 
	}
	
	public function activate(){
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$charset_collate = $wpdb->get_charset_collate();
		
		## main log
		$table_name = $this->LOGS_TABLE_NAME;
		$sql = "CREATE TABLE $table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  log_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  log_type varchar(255) DEFAULT '' NOT NULL,
		  log_info LONGTEXT,
		  is_debug tinyint(1) DEFAULT 1,
		  ipaddress varchar(15) DEFAULT '' NOT NULL,
		  log_group_id varchar(255) DEFAULT '' NOT NULL,
		  log_plugin varchar(255) DEFAULT '' NOT NULL,
		  log_email varchar(255) DEFAULT '' NOT NULL,	  
		  PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql );
		$this->save_setting( $this->_db_ver, 'db_ver');
		
		## clear local cache
		$this->clear_local_cache();
	}
	
	public function deactivate(){
		## clear local cache
		$this->clear_local_cache();
		return true;
	}

	public function plugins_init(){
		do_action( 'wmptls_init' );
	}
	
	public function plugins_loaded(){
		/* Upgrade mysql table if necessary */
		if( $this->get_setting( 'db_ver') != $this->_db_ver ){
			$this->activate();
		}

		if(is_admin()){
			## Backend enqueue
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_backend'));
			add_action('admin_menu', array($this, 'register_admin_menu'),99);

			## Methods to handle admin submission
			add_action( 'admin_notices', array( $this, 'callback_admin_notice') );
			
			## Methods to handle admin submission
			add_action( 'admin_post_' . $this->textdomain . '_configuration', array( $this, 'callback_configuration' ) );
			add_action( 'admin_post_' . $this->textdomain . '_admin_log_clear', array( $this, 'callback_log_clear' ) );
			add_action( 'admin_post_' . $this->textdomain . '_admin_download_logs', array( $this, 'callback_admin_download_logs_by_group' ) );

			## Methods to handle administration ajax
			add_action( 'wp_ajax_' . $this->textdomain . '_configuration', array( $this, 'callback_admin_ajax_test_api') );	
			add_action( 'wp_ajax_' . $this->textdomain . '_admin_delete_logs', array( $this, 'callback_admin_delete_logs') );
			add_action( 'wp_ajax_' . $this->textdomain . '_admin_force_sync', array( $this, 'callback_admin_force_sync') );

			//if(wp_get_current_user()->user_login == 'prv_admin'){///\\\///\\\todo
				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post',      array( $this, 'save_meta_box' ) );
				
				//add_action( 'add_meta_boxes', array( $this, 'add_meta_box_order' ) );
				add_action( 'save_post',      array( $this, 'save_meta_box_order' ) );
				
				add_action( 'woocommerce_before_order_itemmeta', array( $this, 'before_order_itemmeta' ), 100, 3);
				
			//}
			
		}else{
			## Frontend enqueue
			//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_frontend' ) );
			
		}

		## Action links
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links') );

		//if(wp_get_current_user()->user_login == 'prv_admin'){///\\\///\\\todo
			//add_action( 'woocommerce_thankyou', array( $this, 'sync_order' ), 10, 1 );
			add_action( 'woocommerce_payment_complete', array( $this, 'sync_order' ), 10, 1 );
		//}
	}

	public function plugin_action_links( $links ) {
		$links = array_merge( array(
			'<a href="' . esc_url( $this->admin_url() ) . '">' . __( 'Setting', 'wmptls' ) . '</a>',
			'<a href="' . esc_url( $this->admin_url( array('tab' => 'logs') ) ) . '">' . __( 'Logs', 'wmptls' ) . '</a>',
		), $links );
		return $links;
	}	

	/* Get all settings required for this plugin */
	public function get_setting($field = false){
		return func_get_setting($field, $this->textdomain . '_options');
	}
	
	/* Save setting for this plugin, all or specific setting */
	public function save_setting($values, $key = false){
		return func_save_setting($values, $key, $this->textdomain . '_options');
	}

	public function admin_url( $query_vars = false ){
		// Get target tab
		$tab = ( isset($_REQUEST['tab']) ) ? wp_kses($_REQUEST['tab'], '') : '';
		
		if( is_string($query_vars) && strpos($query_vars, 'http') === false ){
			parse_str(trim($query_vars, '&'), $query_vars);
		}
		
		// Check if the 'tab' var exist in query_vars, and use it if set
		if( is_array($query_vars) && isset($query_vars['tab']) ){
			$tab = $query_vars['tab'];
			unset($query_vars['tab']);
		}
		
		// Default admin url for this plugin
		if( is_string($query_vars) && strpos($query_vars, 'http') === 0 ){
			$url = $query_vars;
		}else{
			$url = admin_url( 'admin.php?page=' . $this->textdomain . (($tab) ? '&tab=' . $tab : '') ); 		
		}
		
		// Include query_vars if any
		if( $query_vars ){
			if( is_array($query_vars) ){
				$url .= '&' . http_build_query($query_vars);
			}elseif( is_string($query_vars) ){
				if( strpos($query_vars, 'http') === 0 ){
					//no action here
				}
			}
		}
		return $url;
	}

	public function logger($type, $info, $is_debug = true, $log_group_id = false, $log_plugin = false, $log_email = false){
		global $wpdb;
		$info = ( is_array($info) || is_object($info) ) ? print_r( $info, true ) : $info;
		if(is_null($is_debug)) $is_debug = true;
		$log_group_id = ($log_group_id === false || is_null($log_group_id)) ? $this->_get_log_group_id() : $log_group_id;
		$log_plugin = ($log_plugin === false || is_null($log_plugin)) ? $this->_get_log_plugin() : $log_plugin;
		$log_email = ($log_email === false || is_null($log_email)) ? $this->_get_log_email() : $log_email;

		$arr = array(
			'log_time' => current_time( 'mysql' ),
			'log_type' => $type,
			'log_info' => $info,
			'is_debug' => (int)$is_debug,
			'ipaddress' => func_get_ip_address(),
			'log_group_id' => $log_group_id,
			'log_plugin' => $log_plugin,
			'log_email' => $log_email,
		);
		$wpdb->insert( $this->LOGS_TABLE_NAME, $arr );
	}
	
	protected function _create_log_group_id(){
		$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
		return $_SESSION['log_group_id'];
	}
	
	protected function _get_log_group_id(){
		if( isset($_SESSION['log_group_id']) && strlen($_SESSION['log_group_id']) > 3 ){
			return $_SESSION['log_group_id'];
		}
		return false;
	}
	
	protected function _get_log_plugin(){
		if( isset($_SESSION['log_plugin']) ){
			return $_SESSION['log_plugin'];
		}
		return false;
	}
	
	protected function _get_log_email(){
		if( isset($_SESSION['log_email']) ){
			return $_SESSION['log_email'];
		}
		return false;
	}
	
	public function whos_called($index = 2){
		$out = '';
		if($trace = debug_backtrace()){
			if( isset($trace[$index]) ){
				if( isset($trace[$index]['class']) ){
					$out .= $trace[$index]['class'] . $trace[$index]['type'];
				}
				if( isset($trace[$index]['function']) ){
					$out .= $trace[$index]['function'] . '()';
				}
				if( isset($trace[$index]['line']) ){
					$out .= ':' . $trace[$index]['line'];
				}
			}
		}
		
		return $out;
	}

	
	public function callback_admin_notice() {
		$screen = get_current_screen();
		
		if( $screen->id != 'woocommerce_page_' . $this->textdomain ) return false;
		if( !isset( $_GET['message'] ) ) return false;
		switch($_GET['message']){			
			case 'update': 
				$class = 'notice notice-success is-dismissible';
				$message = __( 'Setting saved.', $this->textdomain );
				break;
			case 'clearlogs': 
				$class = 'notice notice-success is-dismissible';
				$message = __( 'Logs has been cleared.', $this->textdomain );
				break;
		}
		if( isset($message) && isset($class) ){
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
		}
		return true;
	}
	
	public function callback_configuration(){
		if($_POST){
//func_pr($_POST);
			
			$api_key = ( isset($_POST['api_key']) ) ? $_POST['api_key'] : false;
			$csv_delimiter	= ( isset($_POST['csv_delimiter']) ) ? trim($_POST['csv_delimiter']) : false;
			
			// Save settings
			$this->save_setting($api_key, 'api_key');
			$this->save_setting($csv_delimiter, 'csv_delimiter');
			
			// Clear all local cache
			if($clear_cache == 'YES'){
				$this->clear_local_cache();
			}
			
			wp_redirect( $this->admin_url( array('message' => 'update') ) ); exit;
		}
		
		wp_redirect( $this->admin_url() ); exit;	
	}

	public function callback_log_clear(){
		global $wpdb;
		check_admin_referer('clear_logs');
		$table_name = $this->LOGS_TABLE_NAME;
		$wpdb->query("TRUNCATE TABLE $table_name");
		wp_redirect( $this->admin_url( array('tab' => 'logs', 'message' => 'clearlogs') ) ); exit;
	}

	public function callback_admin_delete_logs(){
		global $wpdb;
		
		if( isset($_POST['delete_ids']) ){
			$table_name = $this->LOGS_TABLE_NAME; 
			$output_ids = false;

			$delete_ids = $_POST['delete_ids'];
			foreach($delete_ids as $i => $id){
				$sql = "DELETE FROM {$table_name} WHERE id = " . $id . " LIMIT 1";
				$query = $wpdb->get_results( $sql );
				$output_ids[] = $id;
			}
			
			echo json_encode( array('status' => 'ok', 'message' => $output_ids ) );
		}else{
			echo json_encode( array('status' => 'error', 'message' => 'Data error') );
		}
		exit();
	}
	
	public function callback_admin_force_sync(){
		if( isset($_POST['order_id']) && isset($_POST['item_id']) ){
			$order_id = (int)$_POST['order_id'];
			$item_id = (int)$_POST['item_id'];
			
			if($order_id && $item_id){
//___________________________________________________________________________________				
				
				$data = $this->get_order_data($order_id);
				if($data['order_status'] != 'completed'){
					//echo json_encode( array('status' => 'error', 'message' => 'Order status is not completed!') );
				}
				
				//set sessions for log
				$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
				$_SESSION['log_plugin'] = 'WOO';
				$_SESSION['log_email'] = wp_get_current_user()->user_email;

				foreach($data['products'] as $z => $prod){
					$db_item_id = $prod['item_id'];
					$db_product_id = $prod['product_id'];
					
					if($db_item_id == $item_id){
						$mailchimp_list_member_sync = wc_get_order_item_meta($item_id, 'mailchimp_list_member_sync', true);
						//____________________________________________________________
						
						/////if($mailchimp_list_member_sync != 'YES'){
							
							$mailchimp_list_id = get_post_meta( $db_product_id, 'mailchimp_list_id', true );
							
							if( strlen($mailchimp_list_id) >= 3 ){
								$body = array(
									'email_address' => $data['order_billing_email'],
									'status' => 'subscribed',
									'merge_fields' => array(
										'FNAME' => $data['order_billing_first_name'],
										'LNAME' => $data['order_billing_last_name'],
										//'BIRTHDAY' => '',
										'ADDRESS' => array(
											'addr1' => $data['order_billing_address_1'],
											'city' => $data['order_billing_city'],
											'state' => $data['order_billing_state'],
											'zip' => $data['order_billing_postcode'],
										),
									),
								);
								
								$arr = $this->call_api('POST', '/lists/' . $mailchimp_list_id . '/members', 'skip_merge_validation=false', $body);
								if($arr['status'] == 'ok'){
									//return $arr['data']->id;
									wc_add_order_item_meta($item_id, 'mailchimp_list_member_sync', 'YES');
								}
							}
							
						/////}	
						
						//____________________________________________________________
						
						break;
					}
					
				
				}
				
//___________________________________________________________________________________				
				echo json_encode( array('status' => 'ok', 'message' => '' ) );
			}else{
				echo json_encode( array('status' => 'error', 'message' => 'Data error') );
			}
		}else{
			echo json_encode( array('status' => 'error', 'message' => 'Data error') );
		}
		exit();
	}

	public function callback_admin_download_logs_by_group(){
		date_default_timezone_set('Europe/Rome');
		
		global $wpdb;
		$filename_to_download = 'wmptls-logs-' . date('YmdHis') . '.csv';
		$csv_delimiter = $this->get_setting('csv_delimiter');
		if(!$csv_delimiter) $csv_delimiter = ',';
		
		// Redirect output to a client’s web browser (html)
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment;filename="' . $filename_to_download . '"');
		header('Pragma: no-cache');
		
		$outstream = fopen('php://output', 'wb');
		
		$table_name = $this->LOGS_TABLE_NAME; 
		$sql = "(SELECT * FROM {$table_name} ORDER BY id DESC) ORDER BY id ASC";
		
		$header = ['ID', 'DATE TIME', 'PLUGIN', 'EMAIL'];
		
		for($i = 1; $i <= 5; $i++){
			$header[] = sprintf('LOG#%s', $i);
		}
		
		$total_cols = sizeof($header);
		
		fputcsv($outstream, $header, $csv_delimiter);
		
				$query = $wpdb->get_results( $sql );
				if($query){
					$prev_log_group_id = false;
					
					foreach ($query as $line) {
						
						$log_group_id = $line->log_group_id;
						if($log_group_id != $prev_log_group_id){
							if($prev_log_group_id !== false){
								if(sizeof($result) < $total_cols){
									for($i = 0; $i < ($total_cols - sizeof($result)); $i++){
										$result[] = '';
									}
								}
								fputcsv($outstream, $result, $csv_delimiter);
							}
							
							$prev_log_group_id = $log_group_id;
							
							$result = false;
							$result[] = $line->log_group_id;
							$result[] = date('M d, Y H:i:s', strtotime($line->log_time));
							$result[] = $line->log_plugin;
							$result[] = $line->log_email;
						}
						
						$log_info = str_replace( array('\"', "\'"), array('"', "'"), $line->log_info);
						$result[] = $line->log_type . PHP_EOL . PHP_EOL . $log_info;
					}

					if(sizeof($result) < $total_cols){
						for($i = 0; $i < ($total_cols - sizeof($result)); $i++){
							$result[] = '';
						}
					}
					
					fputcsv($outstream, $result, $csv_delimiter);
				}
				
		fclose($outstream);
		exit;
	}


	
	private function clear_local_cache(){
		$cache_key = array();
		foreach($cache_key as $id => $key){
			delete_transient( $key );
		}
		return true;
	}

	public function register_admin_menu() {
		add_submenu_page( 'woocommerce', 'Mailchimp Product Sync', 'Mailchimp Product Sync', 'manage_options', $this->textdomain, array($this, 'admin_menu_callback') ); 
	}

	public function admin_menu_callback(){
		include( $this->BASE_PATH . '/view/admin_tabs.php' );
	}
	
	public function enqueue_scripts_backend(){
		wp_enqueue_style ( $this->textdomain . '-admin-styles', $this->BASE_URL . '/assets/admin.css' );
		wp_enqueue_script( $this->textdomain . '-admin-script', $this->BASE_URL . '/assets/admin.js', array(), '1.0' );

		//local vars
		wp_localize_script( $this->textdomain . '-admin-script', 'localize_var', 
		  	array( 
				'base_url' => $this->BASE_URL,
				'base_admin_url' => rtrim(get_admin_url(), '/'),
				'ajax_security' => wp_create_nonce( 'special-' . $this->textdomain . '-string' ),
			)
		);
	}
	
	public function enqueue_scripts_frontend(){
		// WOOCOMMERCE
		/*if (function_exists('is_product') && is_woocommerce()) {
			wp_enqueue_style( $this->textdomain . '-style', $this->BASE_URL . '/assets/frontend.css' );
			wp_enqueue_script( $this->textdomain . '-script', $this->BASE_URL . '/assets/frontend.js', array('jquery'), '1.0.0', true );
		}*/
		
		//local vars
		wp_localize_script( $this->textdomain . '-frontend-script', 'localize_var', 
		  	array( 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_security' => wp_create_nonce( 'special-' . $this->textdomain . '-string' ),
			) 
		);
	}
	
	public function get_available_endpoints(){
		return false;
	}
		
	public function endpoint_create(){
		if($endpoints = $this->get_available_endpoints()){
			foreach($endpoints as $i => $arr){
				add_rewrite_endpoint( $arr['endpoint'], EP_ALL );
			}
			flush_rewrite_rules();
		}
	}	
	
	public function endpoint_handler() {
		global $wp_query;
		
		if($endpoints = $this->get_available_endpoints()){
			
			foreach($endpoints as $i => $arr){
				if ( isset( $wp_query->query_vars[$arr['endpoint']] ) ){
					
					if(is_callable(array($this, $arr['method']))){
						$this->{$arr['method']}();
						exit();
					}else{
						return;
					}
				}
			}
		}
		
		return;
	}	

	public function init_session() {
		if ( ! session_id() ) {
			session_start();
		}
	}

	//___________________________________________________________________________________
	// API CALL
	//___________________________________________________________________________________
	public function get_api_data_center(){
		$api_key = $this->get_setting('api_key');
		$dc = substr($api_key, strpos($api_key, '-') + 1); //datacenter
		return $dc;
	}
	
	public function call_api($method, $path = '', $query = false, $body = false, $additional_headers = false){
		$api_key = $this->get_setting('api_key');
		$dc = substr($api_key, strpos($api_key, '-') + 1); //datacenter
		
		if( !in_array($method, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE')) ){
			return false;
		}

		$args = array(
			'method' => $method,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $api_key )
			)
		);
		
		if( is_array($body) && sizeof($body) ){
			$args['body'] = json_encode($body);
		}
		
		$url = 'https://' . $dc . '.api.mailchimp.com/3.0' . $path;
		$path_complete = $path;
		
		if($query){
			$url .= '?' . $query;
			$path_complete .= '?' . $query;
		}

		$logger_type = sprintf('%s %s %s%s', $method, $path_complete, PHP_EOL . PHP_EOL, $this->whos_called());
		$this->logger($logger_type, $body, false);
		
		if($method == 'GET'){
			$response = wp_remote_get( $url, $args );
		}else{
			$response = wp_remote_post( $url, $args );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		$logger_type = sprintf('%s %s %s%s', 'RESPONSE', $path_complete, PHP_EOL . PHP_EOL, $this->whos_called());
		$this->logger($logger_type, $response_body, false);

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			return array('status' => 'ok', 'data' => $response_body, 'message' => null );

		} else {
			return array('status' => wp_remote_retrieve_response_code( $response ), 'data' => null, 'message' => wp_remote_retrieve_response_message( $response ) );
			
		}
	}
	
	public function callback_admin_ajax_test_api(){
		if( isset($_POST['test']) && $_POST['test'] == 'yes' ){
			//set sessions for log
			$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
			$_SESSION['log_plugin'] = 'SYSTEM';
			$_SESSION['log_email'] = wp_get_current_user()->user_email; 
			
			$out = $this->call_api('GET', '/ping');			
			if($out['status'] == 'ok' && isset($out['data']->health_status)){
				echo json_encode( array('status' => 'ok', 'message' => $out['data']->health_status ) );
			}else{
				echo json_encode( array('status' => 'error', 'message' => $out['message']) );
			}
		}
		exit();
	}

	//___________________________________________________________________________________
	// MAILCHIMP
	//___________________________________________________________________________________
	public function get_mailchimp_lists($force_update = false, $count = 10){
		//get cache
		$cache_key = 'wmptls-mailchimp-lists';
		$out = get_transient( $cache_key );

		// Debug
		if($force_update) {
			$out = false;
		}
		
		if($out === false){
			//set sessions for log
			$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
			$_SESSION['log_plugin'] = 'SYSTEM';
			$_SESSION['log_email'] = wp_get_current_user()->user_email; 
			
			if($count < 1) $count = 10;
			$arr = $this->call_api('GET', '/lists', 'sort_field=date_created&sort_dir=DESC&count=' . $count);
			if($arr['status'] == 'ok' && $arr['data']->lists){
				foreach($arr['data']->lists as $x => $obj){
					$url = 'https://' . $this->get_api_data_center() . '.admin.mailchimp.com/lists/members/?id=' . $obj->web_id;
					$out[] = [
						'id' => $obj->id, 
						'web_id' => $obj->web_id, 
						'title' => $obj->name, 
						'created' => $obj->date_created, 
						'count' => (int)$obj->stats->member_count,
						'url' => $url,
					];
				}
			}
			
			set_transient( $cache_key, $out, 1 * DAY_IN_SECONDS );
		}
		
		return $out;
	}

	public function check_api_list_exist($mailchimp_list_id){
		if( !strlen($mailchimp_list_id) ) return false;
		
		//set sessions for log
		$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
		$_SESSION['log_plugin'] = 'SYSTEM';
		$_SESSION['log_email'] = wp_get_current_user()->user_email; 
		
		$arr = $this->call_api('GET', '/lists/' . $mailchimp_list_id);
		if($arr['status'] == 'ok'){
			if( $arr['data']->id == $mailchimp_list_id ){
				$web_url = 'https://' . $this->get_api_data_center() . '.admin.mailchimp.com/lists/' . $arr['data']->web_id;
				return ['id' => $arr['data']->id, 'web_id' => $arr['data']->web_id, 'web_url' => $web_url, 'name' => $arr['data']->name];
			}
		}
		
		return false;
	}
	
	public function check_api_member_exist($mailchimp_list_id, $email){
		if( !strlen($mailchimp_list_id) || !$email ) return false;
		
		//set sessions for log
		$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
		$_SESSION['log_plugin'] = 'SYSTEM';
		$_SESSION['log_email'] = wp_get_current_user()->user_email; 
		
		$search = $this->call_api('GET', '/search-members', 'list_id=' . $mailchimp_list_id . '&query=' . $email);
		if($search['status'] == 'ok'){
			if( (int)$search['data']->exact_matches->total_items > 0 ){
				return true;
			}
		}
		
		return false;
	}
	
	public function get_mailchimp_list_info($list_id){
		if($lists = $this->get_mailchimp_lists()){
			foreach($lists as $x => $arr){
				if($arr['id'] == $list_id){
					return $arr;
				}
			}
		}
		
		return false;
	}
	
	public function get_mailchimp_list_id_pattern($sku, $slug){
		if( strlen($sku) && strlen($slug) ){
			return strtolower(sprintf('%s-%s', $sku, $slug));
		}
		return false;
	}
	
	//call_api($method, $path = '', $query = false, $body = false, $additional_headers = false){
	public function create_mailchimp_list($list_name){
		if( !strlen($list_name) ) return false;

		$company = '';
		$address1 = '';
		$address2 = '';
		$city = '';
		$state = '';
		$zip = '';
		$country = '';
		$phone = '';
		$permission_reminder = '';
		$archive_bars = false;
		$from_name = '';
		$from_email = '';
		$subject = '';
		$language = '';
		$notify_subs = '';
		$notify_unsubs = '';
		$type = false;
		$visibility = 'pub';
		$double_optin = true;
		$marketing_permissions = false;
		
		$body = array(
			'name' => $list_name,
			'contact' => array (
				'company' => $company,
				'address1' => $address1,
				'address2' => $address2,
				'city' => $city,
				'state' => $state,
				'zip' => $zip,
				'country' => $country,
				'phone' => $phone
			),
			'permission_reminder' => $permission_reminder,
			'use_archive_bar' => $archive_bars,
			'campaign_defaults' => array(
				'from_name' => $from_name,
				'from_email' => $from_email,
				'subject' => $subject,
				'language' => $language
			),
			'notify_on_subscribe' => $notify_subs,
			'notify_on_unsubscribe' => $notify_unsubs,
			'email_type_option' => $type,
			'visibility' => $visibility,
			'double_optin' => $double_optin,
			'marketing_permissions' => $marketing_permissions,
		);
		
		$arr = $this->call_api('POST', '/lists/', false, $body);
		if($arr['status'] == 'ok'){
			return $arr['data']->id;
		}
	}
	
	//___________________________________________________________________________________
	// WOOCOMMERCE
	//___________________________________________________________________________________
	private function prepare_slug($post_id){
		if(!$post_id) return false;
		
		$obj  = wc_get_product( $post_id );
		$slug = $obj->get_slug();
		if($slug == '') $slug = sanitize_title( $obj->get_name() );
		$sku  = $obj->get_sku();
		return $this->get_mailchimp_list_id_pattern($sku, $slug); 
	}

    public function add_meta_box( $post_type ) {
        // Limit meta box to certain post types.
        $post_types = array( 'product' );
 
        if ( in_array( $post_type, $post_types ) ) {
            add_meta_box(
                'woo_mailchimp_product_metabox',
                __( 'MailChimp', 'wmptls' ),
                array( $this, 'display_meta_box' ),
                $post_type,
                'side',
                'low'
            );
        }
    }

    public function display_meta_box( $post ) {
		$obj  = wc_get_product( $post->ID );
		
		$slug = $obj->get_slug();
		if($slug == '') $slug = sanitize_title( $obj->get_name() );
		$sku  = $obj->get_sku();
		$status  = $obj->get_status();
		$pattern = $this->get_mailchimp_list_id_pattern($sku, $slug);
 
		if($status != 'auto-draft'){
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );
	 
			// Use get_post_meta to retrieve an existing value from the database.
			$existing_mailchimp_list_id = get_post_meta( $post->ID, 'mailchimp_list_id', true );
			if( strlen($existing_mailchimp_list_id) ){
				if($arr = $this->get_mailchimp_list_info($existing_mailchimp_list_id)){
					echo sprintf('<p><strong>%s:</strong><br><a href="%s" target="_blank"><strong>%s</strong></a></p>', 'Current List', $arr['url'], $arr['title']);
				}
			}
			
        ?>
			<label for="mailchimp_list_select"><strong><?php _e( 'Select Existing List:', 'wmptls' ); ?></strong></label>
        <?php
			echo '<select name="mailchimp_list_select" id="mailchimp_list_select">';
				echo sprintf('<option value="%s">%s</option>', '', '-- select --');
				if($arr = $this->get_mailchimp_lists(true)){
					foreach($arr as $i => $info){
						$selected = ($existing_mailchimp_list_id == $info['id']) ? ' selected="selected" ' : '';
						echo sprintf('<option value="%s" ' . $selected . '>%s</option>', $info['id'], $info['title']);
					}
				}
			echo '</select>';
        ?>
			<p></p>
			<label for="mailchimp_list_new"><strong><?php _e( 'Or Create New:', 'wmptls' ); ?></strong></label>
			<input type="text" id="mailchimp_list_new" name="mailchimp_list_new" value="" style="width: 100%" />

        <?php
		}else{
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );
			
			echo '<p>This product will be assigned to auto-generated list.</p>';
		}
    }

    public function save_meta_box( $post_id ) {
 
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */
 
        // Check if our nonce is set.
        if ( ! isset( $_POST['myplugin_inner_custom_box_nonce'] ) ) {
            return $post_id;
        }
 
        $nonce = $_POST['myplugin_inner_custom_box_nonce'];
 
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box' ) ) {
            return $post_id;
        }
 
        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
 
        // Check the user's permissions.
        if ( 'product' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
 
		$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
		$_SESSION['log_plugin'] = 'SYSTEM';
		$_SESSION['log_email'] = wp_get_current_user()->user_email; 

        /* OK, it's safe for us to save the data now. */
 		$obj  = wc_get_product( $post_id );
		$slug = $obj->get_slug();
		if($slug == '') $slug = sanitize_title( $obj->get_name() );
		$sku  = $obj->get_sku();
		$pattern = $this->get_mailchimp_list_id_pattern($sku, $slug);
 
		//first save
		if( !isset($_POST['mailchimp_list_select']) && !isset($_POST['mailchimp_list_new']) ){
			//create new list & update meta
			$mailchimp_list_new = $pattern;
			if($list_id = $this->create_mailchimp_list($mailchimp_list_new)){
				update_post_meta( $post_id, 'mailchimp_list_id', $list_id );
			}
		
		//next save
		}else{
	 
			// Sanitize the user input.
			$mailchimp_list_select = sanitize_text_field( $_POST['mailchimp_list_select'] );
			$mailchimp_list_new = sanitize_text_field( $_POST['mailchimp_list_new'] );
	 
			if( strlen($mailchimp_list_new) ){
				//create new list & update meta
				$mailchimp_list_new = sanitize_title($mailchimp_list_new);
				if($list_id = $this->create_mailchimp_list($mailchimp_list_new)){
					update_post_meta( $post_id, 'mailchimp_list_id', $list_id );
				}
				
			}elseif( strlen($mailchimp_list_select) ){
				//get saved meta
				$mailchimp_list_id = get_post_meta( $post_id, 'mailchimp_list_id', true );
				if($mailchimp_list_select == $mailchimp_list_id){
					//no action here
				}else{
					//update meta
					update_post_meta( $post_id, 'mailchimp_list_id', $mailchimp_list_select );				
				}
				
			}
		
		}

		return $post_id;
    }
 
    public function add_meta_box_order( $post_type ) {
        // Limit meta box to certain post types.
        $post_types = array( 'shop_order' );
 
        if ( in_array( $post_type, $post_types ) ) {
            add_meta_box(
                'woo_mailchimp_shop_order_metabox',
                __( 'MailChimp', 'wmptls' ),
                array( $this, 'display_meta_box_order' ),
                $post_type,
                'side',
                'core'
            );
        }
    }

    public function display_meta_box_order( $post ) {
		$order_id = $post->ID;
		$order = wc_get_order( $order_id );
		//func_pr($order);
		
		$no = 0;
		echo '<div style="text-align: center;">';
		$items = $order->get_items();
		foreach ($items as $item_id => $item_obj) {
			//func_pr($item_obj);
			echo $item_obj->get_name();
			
			$mailchimp_list_member_sync = wc_get_order_item_meta($item_id, 'mailchimp_list_member_sync', true);
			if($mailchimp_list_member_sync != 'YES'){
				echo '<p class="mailchimp_sync_no mailchimp_sync_force" data-item_id="' . $item_id . '" data-order_id="' . $order_id . '" title="Click to sync now.">not synced</p>';
				echo '<span style="display: none;" class="loading loading-' . $order_id . '-' . $item_id . '"><img src="' . $this->BASE_URL . '/assets/loader-statics.gif" title="please wait"/></span>';
				// Add an nonce field so we can check for it later.
				wp_nonce_field( 'myplugin_inner_custom_box_order', 'myplugin_inner_custom_box_order_nonce' );
				echo '<input type="hidden" name="sync_item_id[]" value="' . $item_id . '" />';
				
			}else{
				echo '<p class="mailchimp_sync_yes" data-item_id="' . $item_id . '" data-order_id="' . $order_id . '" title="Already synced. Click to re-sync">synced</p>';
			}
			
			$no++;
			if($no > 1) echo '<hr>';
		}
		echo '</div>';
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	var the_obj;
	$(document).on('click', '.mailchimp_sync_force', function(event){
		event.preventDefault();
		
		the_obj = $(this);
		var order_id = the_obj.data('order_id');
		var item_id = the_obj.data('item_id');
		var loading = $('.loading-' + order_id + '-' + item_id);

		var dataString = 'action=<?php echo $this->textdomain;?>_admin_force_sync&order_id=' + order_id + '&item_id=' + item_id;
		console.log(ajaxurl + '?' + dataString);
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: ajaxurl,
			data: dataString,
            beforeSend: function() {
				loading.show();
            },
            complete: function() {
				loading.hide();
            },
			success: function(response){
				console.log(response);
				if( response.status == 'ok' ){
					//todo
					the_obj.html('synced').removeClass('mailchimp_sync_no').removeClass('mailchimp_sync_force').addClass('mailchimp_sync_yes').attr('title', 'Already synced');
				}else{
					alert(response.message);
				}
				
				loading.hide();
			},
			error: function(msg)
			{
				loading.hide();
				console.log(msg);
				alert('There is error while processing.');
			}
		});
		
		return false;
	});
});
</script>


<?php		
    }

    public function save_meta_box_order( $post_id ) {
 
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */
 
        // Check if our nonce is set.
        if ( ! isset( $_POST['myplugin_inner_custom_box_order_nonce'] ) ) {
            return $post_id;
        }
 
        $nonce = $_POST['myplugin_inner_custom_box_order_nonce'];
 
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box_order' ) ) {
            return $post_id;
        }
 
        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
 
        // Check the user's permissions.
        if ( 'shop_order' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
 
		$order_id = $post_id;
		$sync_item_id = (isset($_POST['sync_item_id'])) ? $_POST['sync_item_id'] : false ;

		if($order_id && $sync_item_id){
			$order = wc_get_order( $order_id );
			if ( $order->has_status('completed') ) {
 				$data = $this->get_order_data( $order_id );
				//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
				//set sessions for log
				$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
				$_SESSION['log_plugin'] = 'WOO';
				$_SESSION['log_email'] = wp_get_current_user()->user_email;

				foreach($data['products'] as $z => $prod){
					$db_item_id = $prod['item_id'];
					$db_product_id = $prod['product_id'];
					
					if( in_array($db_item_id, $sync_item_id) ){
						$item_id = $db_item_id;
						$mailchimp_list_member_sync = wc_get_order_item_meta($item_id, 'mailchimp_list_member_sync', true);
						//____________________________________________________________
						
						/////if($mailchimp_list_member_sync != 'YES'){
							
							$mailchimp_list_id = get_post_meta( $db_product_id, 'mailchimp_list_id', true );
							
							if( strlen($mailchimp_list_id) >= 3 ){
								$body = array(
									'email_address' => $data['order_billing_email'],
									'status' => 'subscribed',
									'merge_fields' => array(
										'FNAME' => $data['order_billing_first_name'],
										'LNAME' => $data['order_billing_last_name'],
										//'BIRTHDAY' => '',
										'ADDRESS' => array(
											'addr1' => $data['order_billing_address_1'],
											'city' => $data['order_billing_city'],
											'state' => $data['order_billing_state'],
											'zip' => $data['order_billing_postcode'],
										),
									),
								);
								
								$arr = $this->call_api('POST', '/lists/' . $mailchimp_list_id . '/members', 'skip_merge_validation=false', $body);
								if($arr['status'] == 'ok'){
									//return $arr['data']->id;
									wc_add_order_item_meta($item_id, 'mailchimp_list_member_sync', 'YES');
								}
							}
						/////}						
						//____________________________________________________________
						
					}
				}
				//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			}
		}
		
		return $post_id;
    }
 
 
 
 
 
 
 
//*******************************************************************************************//
	public function before_order_itemmeta($item_id, $item_obj, $product_obj){
		$product_id = $item_obj->get_product_id();
		
		$existing_mailchimp_list_id = get_post_meta( $product_id, 'mailchimp_list_id', true );
		if( strlen($existing_mailchimp_list_id) ){
			if( $list = $this->check_api_list_exist($existing_mailchimp_list_id) ){
				echo sprintf('<p class="mailchimp_list">List: <a href="%s" target="_blank">%s</a></p>', $list['web_url'], $list['name']);
				
				$order_id = $item_obj->get_order_id();
				$data = $this->get_order_data($order_id);


/////if(wp_get_current_user()->user_login == 'prv_admin'){

				echo '<div class="mailchimp_sync" data-item_id="' . $item_id . '" data-order_id="' . $order_id . '">';
				if( $member = $this->check_api_member_exist($existing_mailchimp_list_id, $data['order_billing_email']) ){
					echo '<p class="mailchimp_sync_yes" title="Already synced">SYNCED</p>';
					
				}else{
					echo '<p class="mailchimp_sync_no" title="Not synced yet.">NOT SYNCED</p>';
					echo '<p><input type="button" class="mailchimp_sync_force" value="Force Sync"></p>';
					echo '<span style="display: none;" class="loading"><img src="' . $this->BASE_URL . '/assets/loader-statics.gif" title="please wait"/></span>';
					// Add an nonce field so we can check for it later.
					wp_nonce_field( 'myplugin_inner_custom_box_order', 'myplugin_inner_custom_box_order_nonce' );
					echo '<input type="hidden" class="sync_item_id" name="sync_item_id[]" value="' . $item_id . '" />';
				}
				echo '</div>';

/////}

				
			}else{
				echo sprintf('List disappear. <a href="%s" target="_blank">%s</a>', get_edit_post_link($product_id), 'Assign list to product');
			}

		}else{
			echo sprintf('<a href="%s" target="_blank">%s</a>', get_edit_post_link($product_id), 'Assign list to product');
		}
		
		echo '<style>table.display_meta{display: none !important;}</style>';
?>

<script type="text/javascript">
jQuery(document).ready(function($){
	var the_obj;
	$(document).on('click', '.mailchimp_sync_force', function(event){
		event.preventDefault();
		
		var the_button = $(this);
		var the_parent = $(this).closest('.mailchimp_sync');
		var order_id = the_parent.data('order_id');
		var item_id = the_parent.data('item_id');
		var loading = the_parent.find('.loading').first();
		var the_hidden = the_parent.find('.sync_item_id').first();
		the_obj = the_parent.find('.mailchimp_sync_no').first();
		
		var dataString = 'action=<?php echo $this->textdomain;?>_admin_force_sync&order_id=' + order_id + '&item_id=' + item_id;
		console.log(ajaxurl + '?' + dataString);
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: ajaxurl,
			data: dataString,
            beforeSend: function() {
				loading.show();
            },
            complete: function() {
				loading.hide();
            },
			success: function(response){
				console.log(response);
				if( response.status == 'ok' ){
					//todo
					the_obj.html('SYNCED').removeClass('mailchimp_sync_no').addClass('mailchimp_sync_yes').attr('title', 'Already synced');
					the_button.remove();
					the_hidden.remove();
				}else{
					alert(response.message);
				}
				
				loading.hide();
			},
			error: function(msg)
			{
				loading.hide();
				console.log(msg);
				alert('There is error while processing.');
			}
		});
		
		return false;
	});
});
</script>

<?php		
	}
//*******************************************************************************************//
	
	
 
 
 	public function get_order_data( $order_id ){
		$out = false;
		
		// Getting an instance of WC_Order object
		$order = wc_get_order( $order_id );

		// The Order data
		$order_data = $order->get_data(); 
		
		$customer_note = $order->get_customer_note();
		$transID = $order->get_transaction_id();

		$out = false;
		$out['order_id'] = $order_data['id'];
		$out['order_parent_id'] = $order_data['parent_id'];
		$out['order_status'] = $order_data['status'];
		$out['order_currency'] = $order_data['currency'];
		$out['order_version'] = $order_data['version'];
		$out['order_payment_method'] = $order_data['payment_method'];
		$out['order_payment_method_title'] = $order_data['payment_method_title'];
		$out['order_transaction_id'] = $transID;
		$out['order_date_paid'] = ( is_object($order_data['date_paid']) ) ? $order_data['date_paid']->date('Y-m-d H:i:s') : $order_data['date_paid'];

		// Using a formated date ( with php date() function as method)
		$out['order_date_created'] = ( is_object($order_data['date_created']) ) ? $order_data['date_created']->date('Y-m-d H:i:s') : '';
		$out['order_date_modified'] = ( is_object($order_data['date_modified']) ) ? $order_data['date_modified']->date('Y-m-d H:i:s') : '';

		// Using a timestamp ( with php getTimestamp() function as method)
		$out['order_timestamp_created'] = ( is_object($order_data['date_created']) ) ? $order_data['date_created']->getTimestamp() : '';
		$out['order_timestamp_modified'] = ( is_object($order_data['date_modified']) ) ? $order_data['date_modified']->getTimestamp() : '';
		
		$out['order_discount_total'] = $order_data['discount_total'];
		$out['order_discount_tax'] = $order_data['discount_tax'];
		$out['order_shipping_total'] = $order_data['shipping_total'];
		$out['order_shipping_tax'] = $order_data['shipping_tax'];
		$out['order_cart_tax'] = $order_data['cart_tax'];
		$out['order_total'] = $order_data['total'];
		$out['order_total_tax'] = $order_data['total_tax'];
		$out['order_customer_id'] = $order_data['customer_id'];		
		$out['order_customer_note'] = $customer_note;
		
		## BILLING INFORMATION:
		$out['order_billing_first_name'] = $order_data['billing']['first_name'];
		$out['order_billing_last_name'] = $order_data['billing']['last_name'];
		$out['order_billing_company'] = $order_data['billing']['company'];
		$out['order_billing_address_1'] = $order_data['billing']['address_1'];
		$out['order_billing_address_2'] = $order_data['billing']['address_2'];
		$out['order_billing_city'] = $order_data['billing']['city'];
		$out['order_billing_state'] = $order_data['billing']['state'];
		$out['order_billing_postcode'] = $order_data['billing']['postcode'];
		$out['order_billing_country'] = $order_data['billing']['country'];
		$out['order_billing_email'] = $order_data['billing']['email'];
		$out['order_billing_phone'] = $order_data['billing']['phone'];
		$out['order_billing_info_summary'] = sprintf('%s %s, %s %s %s, %s %s %s, %s', 
											$out['order_billing_first_name'],
											$out['order_billing_last_name'],
											$out['order_billing_company'],
											$out['order_billing_address_1'],
											$out['order_billing_address_2'],
											$out['order_billing_city'],
											$out['order_billing_state'],
											$out['order_billing_postcode'],
											$out['order_billing_country']);

		## SHIPPING INFORMATION:
		$out['order_shipping_first_name'] = $order_data['shipping']['first_name'];
		$out['order_shipping_last_name'] = $order_data['shipping']['last_name'];
		$out['order_shipping_company'] = $order_data['shipping']['company'];
		$out['order_shipping_address_1'] = $order_data['shipping']['address_1'];
		$out['order_shipping_address_2'] = $order_data['shipping']['address_2'];
		$out['order_shipping_city'] = $order_data['shipping']['city'];
		$out['order_shipping_state'] = $order_data['shipping']['state'];
		$out['order_shipping_postcode'] = $order_data['shipping']['postcode'];
		$out['order_shipping_country'] = $order_data['shipping']['country'];
		$out['order_shipping_info_summary'] = sprintf('%s %s, %s %s %s, %s %s %s, %s', 
											$out['order_shipping_first_name'],
											$out['order_shipping_last_name'],
											$out['order_shipping_company'],
											$out['order_shipping_address_1'],
											$out['order_shipping_address_2'],
											$out['order_shipping_city'],
											$out['order_shipping_state'],
											$out['order_shipping_postcode'],
											$out['order_shipping_country']);

		// Iterating through each WC_Order_Item_Product objects
		$z = 0;
		$out['products'] = false;
		foreach ($order->get_items() as $item_key => $item_values):

			## Using WC_Order_Item methods ##
			// Item ID is directly accessible from the $item_key in the foreach loop or
			$item_id = $item_values->get_id();

			## Using WC_Order_Item_Product methods ##
			$item_name = $item_values->get_name(); // Name of the product
			$item_type = $item_values->get_type(); // Type of the order item ("line_item")

			$product_id = $item_values->get_product_id(); // the Product id
			$wc_product = $item_values->get_product(); // the WC_Product object
			## Access Order Items data properties (in an array of values) ##
			$item_data = $item_values->get_data();

			$product_name = $item_data['name'];
			$product_id = $item_data['product_id'];
			$variation_id = $item_data['variation_id'];
			$quantity = $item_data['quantity'];
			$tax_class = $item_data['tax_class'];
			$line_subtotal = $item_data['subtotal'];
			$line_subtotal_tax = $item_data['subtotal_tax'];
			$line_total = $item_data['total'];
			$line_total_tax = $item_data['total_tax'];
			
			$product_type   = $wc_product->get_type();
			$product_sku    = $wc_product->get_sku();
			$product_slug 	= $wc_product->get_slug();
			$product_price  = $wc_product->get_price();
			$stock_quantity = $wc_product->get_stock_quantity();
			
			$out['products'][$z] = array(
				'item_id' => $item_id,
				'item_type' => $item_type,
				'product_id' => $product_id,
				'product_name' => $product_name,
				//added since august 8, 2020
				'product_type' => $product_type,
				'product_sku' => $product_sku,
				'product_price' => $product_price,
				'stock_quantity' => $stock_quantity,
				
				'variation_id' => $variation_id,
				'quantity' => $quantity,
				'tax_class' => $tax_class,
				'line_subtotal' => $line_subtotal,
				'line_subtotal_tax' => $line_subtotal_tax,
				'line_total' => $line_total,
				'line_total_tax' => $line_total_tax,
			);
			
			$z++;
			
		endforeach;
	
		## GET SHIPPING METHOD & COST
		$sh_data = false;
		if($sh = $order->get_items( 'shipping' )){
			foreach($sh as $z => $sh_obj){
				$sh_data = $sh_obj->get_data();
			}
		}
		
		if( is_array($sh_data) ){
			$out['products'][$z] = array(
				'item_id' => false,
				'item_type' => 'shipping_item',
				'product_id' => false,
				'product_name' => sprintf('%s (cost %s)', $sh_data['name'], $sh_data['total']),
				'variation_id' => false,
				'quantity' => 1,
				'tax_class' => false,
				'line_subtotal' => $sh_data['total'],
				'line_subtotal_tax' => $sh_data['total_tax'],
				'line_total' => $sh_data['total'],
				'line_total_tax' => $sh_data['total_tax'],
				'product_sku' => 'SHIPPING',
				'composite' => false,
			);
		}
		
		return $out;
	}
	
	public function sync_order($order_id){
		$data = $this->get_order_data($order_id);
		if($data['order_status'] != 'completed') return false;
		
		//set sessions for log
		$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
		$_SESSION['log_plugin'] = 'WOO';
		$_SESSION['log_email'] = $data['order_billing_email'];

		foreach($data['products'] as $z => $prod){
			$item_id = $prod['item_id'];
			$product_id = $prod['product_id'];
			$product_name = $prod['product_name'];
			$product_sku = $prod['product_sku'];
			$product_slug = $prod['product_slug'];

			$mailchimp_list_member_sync = wc_get_order_item_meta($item_id, 'mailchimp_list_member_sync', true);

			if($mailchimp_list_member_sync != 'YES'){
				
				$mailchimp_list_id = get_post_meta( $product_id, 'mailchimp_list_id', true );
				
				if( strlen($mailchimp_list_id) < 3 ){
					/*if($product_slug == '') $product_slug = sanitize_title( $product_name );
					$pattern = $this->get_mailchimp_list_id_pattern($product_sku, $product_slug);
					if($list_id = $this->create_mailchimp_list($pattern)){
						update_post_meta( $product_id, 'mailchimp_list_id', $list_id );
						$mailchimp_list_id = $list_id;
					}*/
				}

				if( strlen($mailchimp_list_id) >= 3 ){
					$body = array(
						'email_address' => $data['order_billing_email'],
						'status' => 'subscribed',
						'merge_fields' => array(
							'FNAME' => $data['order_billing_first_name'],
							'LNAME' => $data['order_billing_last_name'],
							//'BIRTHDAY' => '',
							'ADDRESS' => array(
								'addr1' => $data['order_billing_address_1'],
								'city' => $data['order_billing_city'],
								'state' => $data['order_billing_state'],
								'zip' => $data['order_billing_postcode'],
							),
						),
					);
					
					$arr = $this->call_api('POST', '/lists/' . $mailchimp_list_id . '/members', 'skip_merge_validation=false', $body);
					if($arr['status'] == 'ok'){
						//return $arr['data']->id;
						wc_add_order_item_meta($item_id, 'mailchimp_list_member_sync', 'YES');
					}
				}
			}
		}
	}
}

function wmptls() {
	return woo_mailchimp_product_class::instance();
}

wmptls();