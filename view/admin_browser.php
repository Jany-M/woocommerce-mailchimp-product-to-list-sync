<?php
	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) die;

	global $wpdb;
	
	//set sessions for log
	$_SESSION['log_group_id'] = substr(md5(uniqid(mt_rand(), true)), 0, 5);
	$_SESSION['log_plugin'] = 'SYSTEM';
	$_SESSION['log_email'] = wp_get_current_user()->user_email; 
	
	$data = $this->get_mailchimp_lists(true, 50);			
//func_pr($data); die;
?>
<div class="wrap" id="deka-page"><h2><?php _e('API Browser', $this->textdomain);?></h2>
<?php 
	if( !is_array($data) ){
		echo sprintf('<p>%s</p>', $data);
	}else{
		//$this->pr($data);
		
?>		
	<table class="widefat" cellspacing="0">
		<thead>
		<tr>
			<th scope="col" class="manage-column"><?php echo 'NO'; ?></th>
			<th scope="col" class="manage-column"><?php echo 'MailChimp List'; ?></th>
			<th scope="col" class="manage-column"><?php echo 'Member Count'; ?></th>
			<th scope="col" class="manage-column"><?php echo 'Created'; ?></th>
			<th scope="col" class="manage-column"><?php echo 'Action'; ?></th>
		</tr>
		</thead>
		<tbody>
<?php		
			foreach($data as $no => $arr){
				$tr_class = ($no % 2) ? 'alternate' : '';
				$action = '<a href="' . $arr['url'] . '" target="_blank">Dashboard</a>';
?>
					<tr class="<?php echo $tr_class;?>">
						<td><?php echo number_format($no + 1); ?>.</td>
						<td><?php echo $arr['title']; ?></td>
						<td><?php echo $arr['count']; ?></td>
						<td><?php echo date('F d, Y', strtotime($arr['created'])); ?></td>
						<td><?php echo $action; ?></td>
					</tr>
<?php				
			}			
?>
		</tbody>
	</table>
	
<?php } ?>	
</div>