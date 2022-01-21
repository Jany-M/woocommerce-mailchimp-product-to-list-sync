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
				<td colspan="2"><hr /></td>
				</tr>
				
				<?php
					$cl = $this->get_setting('create_list');
					if( !is_array($cl) ) $cl = [];
				?>
				
				<tr valign="top">
				<th scope="row"><?php echo __('For MailChimp List Creation:', $this->textdomain);?></th>
				<td>


				<p class="description" style="margin-top: 20px;">Enter company name</p>
				<input type="text" class="regular-text" name="create_list[company]" value="<?php echo $cl['company'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter address1</p>
				<input type="text" class="regular-text" name="create_list[address1]" value="<?php echo $cl['address1'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter address2</p>
				<input type="text" class="regular-text" name="create_list[address2]" value="<?php echo $cl['address2'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter city</p>
				<input type="text" class="regular-text" name="create_list[city]" value="<?php echo $cl['city'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter state</p>
				<input type="text" class="regular-text" name="create_list[state]" value="<?php echo $cl['state'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter zip</p>
				<input type="text" class="regular-text" name="create_list[zip]" value="<?php echo $cl['zip'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter country</p>
				<input type="text" class="regular-text" name="create_list[country]" value="<?php echo $cl['country'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter phone</p>
				<input type="text" class="regular-text" name="create_list[phone]" value="<?php echo $cl['phone'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter permission reminder</p>
				<input type="text" class="regular-text" name="create_list[permission_reminder]" value="<?php echo $cl['permission_reminder'];?>">
				
				<p class="description" style="margin-top: 20px;">Use the Archive Bar?</p>
				<label><input type="radio" name="create_list[archive_bars]" value="true" <?php echo ($cl['archive_bars'] == 'true') ? 'checked="checked"' : '';?>> YES</label>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="create_list[archive_bars]" value="false" <?php echo ($cl['archive_bars'] == 'false') ? 'checked="checked"' : '';?>> NO</label>  
				
				<p class="description" style="margin-top: 20px;">Enter from_name</p>
				<input type="text" class="regular-text" name="create_list[from_name]" value="<?php echo $cl['from_name'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter from_email</p>
				<input type="text" class="regular-text" name="create_list[from_email]" value="<?php echo $cl['from_email'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter subject</p>
				<input type="text" class="regular-text" name="create_list[subject]" value="<?php echo $cl['subject'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter language</p>
				<input type="text" class="regular-text" name="create_list[language]" value="<?php echo $cl['language'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter notify_subs</p>
				<input type="text" class="regular-text" name="create_list[notify_subs]" value="<?php echo $cl['notify_subs'];?>">
				
				<p class="description" style="margin-top: 20px;">Enter notify_unsubs</p>
				<input type="text" class="regular-text" name="create_list[notify_unsubs]" value="<?php echo $cl['notify_unsubs'];?>">
				
				<p class="description" style="margin-top: 20px;">Select email_type_option</p>
				<label><input type="radio" name="create_list[type]" value="true" <?php echo ($cl['type'] == 'true') ? 'checked="checked"' : '';?>> YES</label>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="create_list[type]" value="false" <?php echo ($cl['type'] == 'false') ? 'checked="checked"' : '';?>> NO</label>  
				
				<p class="description" style="margin-top: 20px;">Enter visibility</p>
				<input type="text" class="regular-text" name="create_list[visibility]" value="<?php echo $cl['visibility'];?>">
				
				<p class="description" style="margin-top: 20px;">Select double_optin?</p>
				<label><input type="radio" name="create_list[double_optin]" value="true" <?php echo ($cl['double_optin'] == 'true') ? 'checked="checked"' : '';?>> YES</label>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="create_list[double_optin]" value="false" <?php echo ($cl['double_optin'] == 'false') ? 'checked="checked"' : '';?>> NO</label>  
				
				<p class="description" style="margin-top: 20px;">Select marketing_permissions?</p>
				<label><input type="radio" name="create_list[marketing_permissions]" value="true" <?php echo ($cl['marketing_permissions'] == 'true') ? 'checked="checked"' : '';?>> YES</label>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="create_list[marketing_permissions]" value="false" <?php echo ($cl['marketing_permissions'] == 'false') ? 'checked="checked"' : '';?>> NO</label>  
				
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