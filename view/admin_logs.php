<?php
	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) die;

	date_default_timezone_set('Europe/Rome');

	global $wpdb;

	//if(isset($_GET['search'])) $this->pr($_GET, 0);
		
	$per_page_array = [
		['id' => 30, 'title' => '-- Per Page --'],
		['id' => 10, 'title' => 10],
		['id' => 20, 'title' => 20],
		//['id' => 30, 'title' => 30],
		['id' => 40, 'title' => 40],
		['id' => 50, 'title' => 50],
		['id' => 70, 'title' => 70],
	];
	
	$wordwrap = (!isset($_GET['wordwrap']) || $_GET['wordwrap'] != 1 ) ? false : true;
	$search = (isset($_GET['search']) && $_GET['search']) ? trim(wp_kses($_GET['search'], '')) : ''; 
	$cmd = (isset($_GET['cmd']) && $_GET['cmd']) ? $_GET['cmd'] : false; 
	$hide_debug = (isset($_GET['hide_debug']) && $_GET['hide_debug'] == 'y') ? true : false; 
	
	$start_datetime = (isset( $_GET['start_datetime']) && $_GET['start_datetime'] ) ? $_GET['start_datetime'] : '';
	$end_datetime = (isset( $_GET['end_datetime']) && $_GET['end_datetime'] ) ? $_GET['end_datetime'] : '';
	
	$table_name = $this->LOGS_TABLE_NAME; 

	$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$post_per_page = (isset( $_GET['post_per_page'])) ? absint( $_GET['post_per_page'] ) : 30;
	$offset = ($paged - 1) * $post_per_page;
	
	//_______________________________________________________________________________________________//
	// SQL WHERE
	//_______________________________________________________________________________________________//
	$cmd_sql = "";
	
	$datetime_sql = "";
	if($start_datetime || $end_datetime){
		if($start_datetime){
			$start_datetime_mod = date('Y-m-d H:i:00', strtotime($start_datetime));
			if( trim($datetime_sql) ) $datetime_sql .= " AND ";
			$datetime_sql .= " log_time >= '{$start_datetime_mod}' ";
		}
		
		if($end_datetime){
			$end_datetime_mod = date('Y-m-d H:i:59', strtotime($end_datetime));
			if( trim($datetime_sql) ) $datetime_sql .= " AND ";
			$datetime_sql .= " log_time <= '{$end_datetime_mod}' ";
		}
	}
	
	$search_sql = "";
	if($search){
		if(strpos($search, 'gid:') !== false){
			$gid = str_replace('gid:', '', $search);
			$search_sql .= " log_group_id like '" . $gid. "' ";
		}else{
			$search_sql .= " log_info like '%" . $search . "%' ";
		}
	}

	$hide_debug_sql = "";
	if($hide_debug){
		$hide_debug_sql .= " is_debug = 0 ";
	}

	//_______________________________________________________________________________________________//
	// MAIN SQL
	//_______________________________________________________________________________________________//
	$sql_raw 	= "SELECT * FROM {$table_name} WHERE 1 ";
	$sql_total 	= "SELECT COUNT(*) FROM {$table_name} WHERE 1 ";
	
	if($cmd_sql){
		$sql_raw .= " AND {$cmd_sql} ";
		$sql_total .= " AND {$cmd_sql} ";
	}
	
	if($datetime_sql){
		$sql_raw .= " AND {$datetime_sql} ";
		$sql_total .= " AND {$datetime_sql} ";
	}
	
	if($search_sql){
		$sql_raw .= " AND {$search_sql} ";
		$sql_total .= " AND {$search_sql} ";
	}
	
	if($hide_debug_sql){
		$sql_raw .= " AND {$hide_debug_sql} ";
		$sql_total .= " AND {$hide_debug_sql} ";
	}
	
	$sql_total_all = "select COUNT(*) from {$table_name}";

	/* Determine the total of results found to calculate the max_num_pages for navigation */
	$sql_posts_total = $wpdb->get_var( $sql_total );
	$max_num_pages = ceil($sql_posts_total / $post_per_page);

	$sql_total_all = $wpdb->get_var( $sql_total_all );
?>
<div class="wrap" id="deka-page">
	<h2><?php _e('API Communication Logs', $this->textdomain);?></h2>
	<p style="float: right; margin: -25px 0 15px;"><a href="admin-post.php?action=<?php echo $this->textdomain;?>_admin_download_logs" download><?php _e('Download All Logs', $this->textdomain);?></a> | <a href="#" style="color: red;" onclick="if(confirm('Are you sure want to clear all the API logs?')){jQuery('#clear_logs_form').submit();}">Clear all logs (<?php echo number_format($sql_total_all);?> records)</a></p>
	<table class="widefat wp-header">
		<tr><td style="width:100%; vertical-align: middle; text-align: center;">
				<form method="get" action="<?php echo get_admin_url(null, '/admin.php');?>">
				<input type="hidden" name="page" value="<?php echo $this->textdomain;?>">
				<input type="hidden" name="tab" value="logs">
				
				<label><input type="checkbox" name="hide_debug" value="y" <?php echo ($hide_debug) ? 'checked="checked"' : ''; ?>> Hide debug?</label> 
				<label><input type="datetime-local" name="start_datetime" style="width:210px;" value="<?php echo $start_datetime;?>" placeholder="Start Datetime"></label> - 
				<label><input type="datetime-local" name="end_datetime" style="width:210px;" value="<?php echo $end_datetime;?>" placeholder="End Datetime"></label>
				<select name="post_per_page">
					<?php
						foreach($per_page_array as $x => $r){
							$selected = ($r['id'] == $post_per_page) ? 'selected' : '';
							echo sprintf('<option value="%s" %s>%s</option>', $r['id'], $selected, $r['title']);
						}
					?>
				</select>
				<input type="text" name="search" value="<?php echo $search;?>" placeholder="search" style="width:150px;">
				<input type="submit" name="submit" value="GO" class="button">
				</form>
			</td>
		</tr>
	</table><br />
	<form method="post" id="clear_logs_form" action="admin-post.php" style="display: none;">
		<input type="hidden" name="action" value="<?php echo $this->textdomain;?>_admin_log_clear">
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('clear_logs');?>">
	</form>

	<table class="widefat delete-table" cellspacing="0">
		<thead>
		<tr>
			<th scope="col" class="manage-column select_all" style="width: 50px !important; cursor: pointer; color: blue;">SELECT</th>
			<th scope="col" class="manage-column"><?php echo __('ID', $this->textdomain);?></th>
			<th scope="col" class="manage-column"><?php echo __('DATE / IP ADDRESS', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style=""><?php echo __('PLUGIN', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style=""><?php echo __('EMAIL', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style=""><?php echo __('TYPE', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style="width: 10px !important;">&plusmn;</th>
			<th scope="col" class="manage-column" style="width: 50% !important;"><?php echo __('INFO', $this->textdomain);?></th>
		</tr>
		</thead>
		<tbody>
<?php		
			$sql = $sql_raw . " order by id desc LIMIT " . $offset . ", " . $post_per_page;
			//echo $sql;
			
			$query = $wpdb->get_results( $sql );
			if($query){

				$no = 0;
				foreach ($query as $line) {
					$log_type = nl2br($line->log_type);
					
					$is_debug = (int)$line->is_debug;
					$is_debug_class = ($is_debug) ? ' row_log debug_log ' : ' row_log main_log ';
					
					$tr_class = ($no % 2) ? 'alternate' . $is_debug_class : $is_debug_class; $no++;
?>        
					<tr class="<?php echo $tr_class;?> log_<?php echo $line->id; ?>">
						<td><input type="checkbox" name="delete_ids[]" value="<?php echo $line->id; ?>" class="delete_ids"></td>
						<td><a href="<?php echo add_query_arg( array('search' => 'gid:' . $line->log_group_id, 'paged' => false) );?>" title="Click to see all logs with this ID"><u><?php echo $line->log_group_id; ?></u></a></td>
						<td><?php echo date('M d, Y H:i:s', strtotime($line->log_time)); ?><br /><?php echo $line->ipaddress; ?></td>
						<td><?php echo $line->log_plugin; ?></td>
						<td><?php echo $line->log_email; ?></td>
						<td><?php echo $log_type; ?></td>
						<td class="plusmn" title="Click to maximize">+</td>
						<?php if($wordwrap):?>
						<td class="plusmn_target"><div class="height100" style="max-height: 120px; overflow: auto; font-size: smaller;"><pre><?php echo wordwrap( htmlentities($line->log_info), 80, '<br>', true); ?></pre></div></td>
						<?php else:?>
						<td class="plusmn_target"><div class="height100" style="xmax-height: 400px; overflow: auto; font-size: smaller;"><pre><?php echo htmlentities($line->log_info); ?></pre></div></td>
						<?php endif;?>
					</tr>
<?php        
				}
			}			
?>
		</tbody>
		<tfoot>
		<tr>
			<th scope="col" class="manage-column select_all" style="width: 50px !important; cursor: pointer; color: blue;">SELECT</th>
			<th scope="col" class="manage-column"><?php echo __('ID', $this->textdomain);?></th>
			<th scope="col" class="manage-column"><?php echo __('DATE / IP ADDRESS', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style=""><?php echo __('PLUGIN', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style=""><?php echo __('EMAIL', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style=""><?php echo __('TYPE', $this->textdomain);?></th>
			<th scope="col" class="manage-column" style="width: 10px !important;">&plusmn;</th>
			<th scope="col" class="manage-column" style="width: 50% !important;"><?php echo __('INFO', $this->textdomain);?></th>
		</tr>
		</tfoot>
	</table>
	<br>
	<p><input type="button" name="submit" value="Delete Selected Logs" class="button button-secondary delete_submit" /></p>
	<span class="loading" style="display: none;"><img src="<?php echo $this->BASE_URL;?>/assets/loader-statics.gif" title="please wait"/></span>
	
	<hr />
<?php
$page_links = paginate_links( array(
    'base' => add_query_arg( 'paged', '%#%' ),
    'format' => '',
    'prev_text' => __( '&laquo; PREV', 'text-domain' ),
    'next_text' => __( 'NEXT &raquo;', 'text-domain' ),
    'total' => $max_num_pages,
    'current' => $paged
) );

if ( $page_links ) {
    echo '<div class="tablenav" style="text-align: center;"><div class="tablenav-pages" style="margin: 1em 0; float: none;">' . $page_links . '</div></div>';
}
?>
</div>
<style>
.plusmn{font-weight: bold; cursor: pointer;}
.height100{max-height: 120px !important;}
</style>
<script>
function set_delete_checkbox(){
	var del_status = jQuery('.delete_ids:first-child').is(":checked");
	if(del_status == true){
		del_status = false;
	}else{
		del_status = true;
	}
	console.log('del_status: ' + del_status);
	jQuery('.delete_ids').each(function(){
		jQuery(this).prop('checked', del_status);
	});
	return true;
}

function submit_delete_checkbox(){
	var loading = jQuery('.loading');
	var result = jQuery('.result');
	loading.hide();
	
	var dataString = jQuery('.delete-table :input').serialize();
	console.log(dataString);
	
	jQuery.ajax({
		type: 'POST',
		dataType: 'json',
		url: ajaxurl,
		data: dataString + '&action=<?php echo $this->textdomain;?>_admin_delete_logs',
		beforeSend: function() {
			loading.show();
			result.html('');
		},
		complete: function() {
			loading.hide();
		},
		success: function(response){
			console.log(response);
			if( response.status == 'ok' ){
				result.html(response.message);
				if(jQuery.isArray(response.message)){
					var obj = response.message;
					for (var i = 0, length = obj.length; i < length; i++) {
						jQuery('tr.log_' + obj[i]).fadeOut(300, function() { $(this).remove(); });
						//console.log(obj[i]);
					}					
					
				}
				
			}else{
				//dispay error message
				result.html(response.message);
			}
			
			loading.hide();
		},
		error: function(msg)
		{
			loading.hide();
			result.html(msg);
		}
	});
	
	return true;
}

jQuery(document).ready(function($){
	$(document).on('click', '.select_all', function(){
		set_delete_checkbox();
		return false;
	});
	
	$(document).on('click', '.delete_submit', function(){
		submit_delete_checkbox();
		return false;
	});
	
	$(document).on('click', '.plusmn', function(){
		var td = $(this);
		var td_target = td.parent().children('.plusmn_target');
		var div_target = td_target.children('div');
		if(td.text() == '+'){
			td.text('-').attr('title', 'Click to minimize');
			div_target.removeClass('height100');
		}else{
			td.text('+').attr('title', 'Click to maximize');
			div_target.addClass('height100');
		}
		return false;
	});
});
</script>	
