<?php
if( !function_exists('func_get_ip_address') ){
	function func_get_ip_address(){
		if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"]) { 
			// IP addresses can be chained, separated with commas, we want the first one. 
			if (strpos($ip_address, ',') !== false) { 
				$ip_address = explode(',', $ip_address); 
				$ip_address = $ip_address[0]; 
			} 
		}else { 
			$ip_address = $_SERVER["REMOTE_ADDR"]; 
		} 
		return $ip_address; 
 	}
}

if( !function_exists('func_get_browser') ){
	function func_get_browser(){
		return $_SERVER['HTTP_USER_AGENT'];
	}
}

if( !function_exists('func_get_domain') ){
	function func_get_domain(){
		$domain = parse_url(get_bloginfo('wpurl'), PHP_URL_HOST);
		return $domain;
	}
}

if( !function_exists('func_pr') ){
	function func_pr($arr = false, $return = false){
		$out = '<pre>';
		if( is_array($arr) || is_object($arr) ) $out .= print_r($arr, 1); else $out .= $arr;
		$out .= '</pre>';
		
		if($return){
			return $out;
		}else{
			echo $out;
		}
	}
}

//https://stackoverflow.com/questions/3954599/generate-random-string-from-4-to-8-characters-in-php
if( !function_exists('func_unique_string') ){
	function func_unique_string($length = 8) {
		return substr(md5(uniqid(mt_rand(), true)), 0, $length);
	}
}

if( !function_exists('func_called') ){
	function func_called($index = 2){
		$out = '';
		if( function_exists('debug_backtrace') && $trace = debug_backtrace()){
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
}	

if( !function_exists('func_get_setting') ){
	function func_get_setting($field = false, $option_name = false){
		if(!$option_name) return false;
		
		$options = get_option($option_name);				
		if($field){
			if( isset($options[$field]) ) return $options[$field];
			return false;
		}
		return $options;
	}
}

if( !function_exists('func_save_setting') ){
	function func_save_setting($values, $key = false, $option_name = false){
		if(!$option_name) return false;
		
		$options = func_get_setting(false, $option_name);
		
		if( $key ){
			$options[ $key ] = $values;
			update_option( $option_name, $options );
			
		}else{
			$options = array_merge($options, $values);
			update_option( $option_name, $options );
		}
		
		return true;
	}
}

if( !function_exists('func_backup_setting') ){
	function func_backup_setting($option_name = false, $file_output = false){
		global $wpdb;
		$sql = "SELECT option_value FROM {$wpdb->prefix}options WHERE option_name LIKE '{$option_name}' LIMIT 1";
		$txt = $wpdb->get_var($sql);
		if($file_output){
			file_put_contents($file_output, $txt);
		}
	}
}

if( !function_exists('var_dump_return') ){
	function var_dump_return($mixed = null) {
		ob_start();
		var_dump($mixed);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}
