<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

	//available tabs
	$array_tabs = array(
		'main' 			=> __('API Setting', 'wmptls'), 
		//'woo' 			=> __('WooCommerce Setting', 'wmptls'), 
		'logs' 			=> __('API Logs', 'wmptls'), 
		'browser' 		=> __('API Browser', 'wmptls'), 
	);

	// Set the first tab as default active tab
	$default_active_tab = array_keys($array_tabs)[0];
	
	//set default active tab
	if( !isset($_GET['tab']) ){
		$active_tab = $default_active_tab;
	}else{
		$active_tab = ( in_array($_GET['tab'], array_keys($array_tabs) ) ) ? $_GET['tab'] : $default_active_tab; 
	}	

	echo '<div class="wrap" id="deka-page">
			<h1>' . __('Mailchimp Product to List Sync', 'wmptls') . '</h1>';
			
	echo '	<div id="tabs">
				<h2 class="nav-tab-wrapper">';

	foreach($array_tabs as $key => $value){
		echo '<a href="' . $this->admin_url( 'tab=' . $key ) . '" class="nav-tab ' . ($active_tab == $key ? 'nav-tab-active' : '') . '">' . $value . '</a>';
	}
	
	echo '  </h2>';
	echo '  <div class="content-tab-wrapper">';
	
	$file_include = dirname(__FILE__) . '/admin_' . $active_tab . '.php';
	if( is_file( $file_include ) ){
		include( $file_include );
	}else{
		echo 'File not exist: ' . $file_include;
	}
	
	echo '		</div>';
	echo '	</div>';
	echo '</div>';
?>