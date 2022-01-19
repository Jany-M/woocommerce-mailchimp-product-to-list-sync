<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;
?>
	<div class="wrap" id="deka-page">
		<h2><?php _e('API Setting', $this->textdomain);?></h2>
		<form method="post" id="api_setting_form" action="admin-post.php">
			<input type="hidden" name="action" value="<?php echo $this->textdomain;?>_configuration">
			<table class="form-table">

				<tr><td colspan="2"><hr /></td></tr>
				
				<tr valign="top">
				<th scope="row"><?php echo __('Plugin / Database Version:', $this->textdomain);?></th>
				<td>v<?php echo $this->get_version();?></td>
				</tr>
				
				<tr valign="top">
				<td colspan="2"><hr /></td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php echo __('API Key:', $this->textdomain);?> *)</th>
				<td><input type="text" class="regular-text" name="api_key" value="<?php echo $this->get_setting('api_key');?>">
				<p class="description">Enter MailChimp API Key</p>
				</td>
				</tr>
				
				<tr valign="top">
				<td colspan="2"><hr /></td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php echo __('CSV Delimiter:', $this->textdomain);?></th>
				<td><input type="text" class="regular-text" name="csv_delimiter" value="<?php echo $this->get_setting('csv_delimiter');?>">
				<p class="description">Enter a character as CSV field delimiter (default is comma).</p>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td>
					<table><tr valign="top">
						<td style="vertical-align: top;"><?php submit_button('Save Setting', 'primary'); ?><br>*) required</td>
						<td style="vertical-align: top;"><?php echo str_repeat('&nbsp;', 20); ?></td>
						<td style="vertical-align: top;"><p class="submit"><?php echo '<input type="button" value="Test API Communication" id="api-testing" class="button button-secondary" />';?></p><br><span class="loading"><img src="<?php echo $this->BASE_URL;?>/assets/loader-statics.gif" title="please wait"/></span><div class="result"></div></td>
					</tr></table>
				</td>
				</tr>
			</table>
		</form>
	</div>
	<?php //$this->create_mailchimp_list('test-auto-list-1');?>
<script type="text/javascript">
jQuery(document).ready(function($){
	var loading = $('.loading');
	var result = $('.result');
	loading.hide();
	
	$('#api-testing').on( 'click', function(event){
		event.preventDefault();
		
		var dataString = $('#api_setting_form').serialize();
		console.log(ajaxurl + '?' + dataString + '&test=yes');
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: ajaxurl,
			data: dataString + '&test=yes',
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
		
		return false;
	});
});
</script>