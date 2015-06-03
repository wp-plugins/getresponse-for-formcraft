<?php

	/*
	Plugin Name: FormCraft GetResponse Add-On
	Plugin URI: http://formcraft-wp.com/addons/get-response/
	Description: GetResponse Add-On for FormCraft
	Author: nCrafts
	Author URI: http://formcraft-wp.com/
	Version: 1.0.3
	Text Domain: formcraft-getresponse
	*/

	global $fc_meta, $fc_forms_table, $fc_submissions_table, $fc_views_table, $fc_files_table, $wpdb;
	$fc_forms_table = $wpdb->prefix . "formcraft_3_forms";
	$fc_submissions_table = $wpdb->prefix . "formcraft_3_submissions";
	$fc_views_table = $wpdb->prefix . "formcraft_3_views";
	$fc_files_table = $wpdb->prefix . "formcraft_3_files";

	add_action('formcraft_after_save', 'formcraft_getresponse_trigger', 10, 4);
	function formcraft_getresponse_trigger($content, $meta, $raw_content, $integrations)
	{
		global $fc_final_response;
		if ( in_array('Get Response', $integrations['not_triggered']) ){ return false; }
		$getresponse_data = formcraft_get_addon_data('GetResponse', $content['Form ID']);

		if (!$getresponse_data){return false;}
		if (!isset($getresponse_data['validKey']) || empty($getresponse_data['validKey']) ){return false;}
		if (!isset($getresponse_data['Map'])){return false;}

		$submit_data = array();
		foreach ($getresponse_data['Map'] as $key => $line) {
			$submit_data[$line['listID']]['id'] = $line['listID'];
			if ($line['columnID']=='email')
			{
				$email = fc_template($content, $line['formField']);
				if ( !filter_var($email,FILTER_VALIDATE_EMAIL) ) { continue; }
				$submit_data[$line['listID']]['email'] = $email;
			}
			else if ($line['columnID']=='name')
			{
				$name = fc_template($content, $line['formField']);
				$submit_data[$line['listID']]['name'] = $name;
			}
			else
			{
				$submit_data[$line['listID']]['custom'][$line['columnName']] = '';
				foreach ($raw_content as $key1 => $value1) {
					if ( "[".$value1['label']."]" == $line['formField'] )
					{
						$submit_data[$line['listID']]['custom'][$line['columnName']] = is_array($value1['value']) ? implode(', ', $value1['value']) : $value1['value'];
					}
				}
			}
		}
		require_once('GetResponseAPI.class.php');
		foreach ($submit_data as $key => $list_submit) {
			if (!isset($list_submit['email']))
				{$fc_final_response['debug']['failed'][] = __('GetResponse: No Email Specified','formcraft-getresponse');continue;}

			$list_submit['name'] = empty($list_submit['name']) ? '' : $list_submit['name'];
			$list_submit['custom'] = empty($list_submit['custom']) ? array() : $list_submit['custom'];

			$getresponse = new GetResponse($getresponse_data['validKey']);
			$result = $getresponse->addContact($list_submit['id'], $list_submit['name'], $list_submit['email'], 'standard', 0, $list_submit['custom']);
			if ( isset($result->message) )
			{
				$fc_final_response['debug']['failed'][] = "(".$list_submit['email'].")<br>".__($result->message,'formcraft-getresponse');
			}
			else
			{
				$fc_final_response['debug']['success'][] = 'GetResponse Added: '.$list_submit['email'];
			}
		}
	}

	add_action('formcraft_addon_init', 'formcraft_getresponse_addon');
	add_action('formcraft_addon_scripts', 'formcraft_getresponse_scripts');

	function formcraft_getresponse_addon()
	{
		register_formcraft_addon('GR_printContent',263,'GetResponse','GetResponseController',plugins_url('assets/logo.png', __FILE__ ), plugin_dir_path( __FILE__ ).'templates/',1);
	}
	function formcraft_getresponse_scripts()
	{
		wp_enqueue_script('formcraft-getresponse-main-js', plugins_url( 'assets/builder.js', __FILE__ ));
		wp_enqueue_style('formcraft-getresponse-main-css', plugins_url( 'assets/builder.css', __FILE__ ));
	}

	add_action( 'wp_ajax_formcraft_getresponse_test_api', 'formcraft_getresponse_test_api' );
	function formcraft_getresponse_test_api()
	{
		$key = $_GET['key'];
		require_once('GetResponseAPI.class.php');
		$getresponse = new GetResponse($key);
		$ping = $getresponse->ping();
		if ($ping)
		{
			echo json_encode(array('success'=>'true'));
			die();
		}
		else
		{
			echo json_encode(array('failed'=>'true'));
			die();
		}
	}
	add_action( 'wp_ajax_formcraft_getresponse_get_lists', 'formcraft_getresponse_get_lists' );
	function formcraft_getresponse_get_lists()
	{
		$key = $_GET['key'];
		require_once('GetResponseAPI.class.php');
		$getresponse = new GetResponse($key);
		$lists = $getresponse->getCampaigns();
		if ($lists)
		{
			echo json_encode(array('success'=>'true','lists'=>$lists));
			die();
		}
		else
		{
			echo json_encode(array('failed'=>'true'));
			die();
		}
	}
	add_action( 'wp_ajax_formcraft_getresponse_get_columns', 'formcraft_getresponse_get_columns' );
	function formcraft_getresponse_get_columns()
	{
		$key = $_GET['key'];
		$id = $_GET['id'];
		require_once('GetResponseAPI.class.php');
		$fields = array();
		$fields[] = array('id'=>'name','name'=>'Name');
		$fields[] = array('id'=>'email','name'=>'Email');
		$getresponse = new GetResponse($key);
		$custom = $getresponse->getAccountCustoms();
		foreach ($custom as $key => $value) {
			$fields[] = array('id'=>$key,'name'=>$value->name);
		}
		if ($fields)
		{
			echo json_encode(array('success'=>'true','columns'=>$fields));
			die();
		}
		else
		{
			echo json_encode(array('failed'=>'true'));
			die();
		}
	}

	function GR_printContent()
	{

		?>
		<div id='gr-cover' id='gr-valid-{{Addons.GetResponse.showOptions}}'>
			<div class='loader'>
				<div class="fc-spinner small">
					<div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div>
				</div>
			</div>
			<div class='help-link'>
				<a class='trigger-help' data-post-id='265'><?php _e('how does this work?','formcraft-getresponse'); ?></a>
			</div>
			<div class='api-key hide-{{Addons.GetResponse.showOptions}}'>	
				<input placeholder='<?php _e('Enter API Key','formcraft-getresponse') ?>' style='width: 77%; margin-right: 3%; margin-left:0' type='text' ng-model='Addons.GetResponse.api_key'><button ng-click='testKey()' style='width: 20%' class='button blue'><?php _e('Check','formcraft-getresponse') ?></button>
			</div>
			<div ng-show='Addons.GetResponse.showOptions'>
				<div id='mapped-gr' class='nos-{{Addons.GetResponse.Map.length}}'>
					<div>
						<?php _e('Nothing Here','formcraft-getresponse') ?>
					</div>
					<table cellpadding='0' cellspacing='0'>
						<tbody>
							<tr ng-repeat='instance in Addons.GetResponse.Map'>
								<td style='width: 30%'>
									<span>{{instance.listName}}</span>
								</td>
								<td style='width: 30%'>
									<span>{{instance.columnName}}</span>
								</td>
								<td style='width: 30%'>
									<span><input type='text' ng-model='instance.formField'/></span>
								</td>
								<td style='width: 10%; text-align: center'>
									<i ng-click='removeMap($index)' class='icon-cancel-circled'></i>
								</td>								
							</tr>
						</tbody>
					</table>
				</div>
				<div id='gr-map'>
					<select class='select-list' ng-model='SelectedList'><option value='' selected="selected">(<?php _e('List','formcraft-getresponse') ?>)</option><option ng-repeat='(key,val) in GRLists' value='{{key}}'>{{val.name}}</option></select>

					<select class='select-column' ng-model='SelectedColumn'><option value='' selected="selected">(<?php _e('Column','formcraft-getresponse') ?>)</option><option ng-repeat='column in GRColumns' value='{{column.id}}'>{{column.name}}</option></select>

					<input class='select-field' type='text' ng-model='FieldName' placeholder='<?php _e('Form Field','formcraft-getresponse') ?>'>
					<button class='button' ng-click='addMap()'><i class='icon-plus'></i></button>
				</div>
			</div>
		</div>
		<?php
	}


	?>