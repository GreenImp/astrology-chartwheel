<?php
/**
 * Author: GreenImp
 * Date Created: 30/04/2013 18:03
 */

global $astrologyPlugin;
$formValidation = $astrologyPlugin->library('FormValidation');

if(isset($_POST['submit'])){
	// form has been submitted - validate

	$rules = array(
		array(
			'field'	=> 'apiKey',
			'label'	=> 'API Key',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'reportCode',
			'label'	=> 'Report Code',
			'rules'	=> 'trim|required'
		),

		array(
			'field'	=> 'chartSize',
			'label'	=> 'Chart Image Size',
			'rules'	=> 'is_natural_no_zero'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// form submitted successfully - update the options

		update_option($astrologyPlugin->varName . '_api-key', $_POST['apiKey']);
		update_option($astrologyPlugin->varName . '_report-code', $_POST['reportCode']);

		if(isset($_POST['chartSize'])){
			update_option($astrologyPlugin->varName . '_chart-size', $_POST['chartSize']);
		}

		// update successful
		Message::add('updated', 'The settings have been updated');
	}elseif(count($errors = FormValidation::getErrors()) > 0){
		// errors exist - output them to the user
		// loop through each error and add it to the list
		foreach($errors as $error){
			Message::add('error', $error);
		}
	}
}


// output any messages
Message::show();
?>
<div id="<?php echo $astrologyPlugin->varName; ?>" class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php echo __($astrologyPlugin->name); ?> - Settings</h2>

	<form action="<?php $currentURL; ?>" method="post" id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="postbox">
					<h3>API Settings</h3>

					<dl class="inside">
						<dt><label for="apiKey">API Key*</label></dt>
						<dd>
							<input type="text" name="apiKey" value="<?php
								echo get_option($astrologyPlugin->varName . '_api-key', $formValidation->getValue('apiKey'));
							?>" required id="apiKey">
						</dd>

						<dt><label for="reportCode">Report Code*</label></dt>
						<dd>
							<input type="text" name="reportCode" value="<?php
								echo get_option($astrologyPlugin->varName . '_report-code', $formValidation->getValue('reportCode'));
							?>" required id="reportCode">
						</dd>
					</dl>
				</div>


				<div class="postbox">
					<h3>Chart</h3>

					<dl class="inside">
						<dt><label for="chartImageSize">Image Size</label></dt>
						<dd>
							<input type="number" name="chartSize" value="<?php
								echo get_option($astrologyPlugin->varName . '_chart-size', $formValidation->getValue('chartSize'));
							?>" min="1" id="chartImageSize">
						</dd>
					</dl>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<h3>Save</h3>

					<div class="inside submitbox">
						<div id="major-publishing-actions">
							<div id="publishing-action">
								<input type="submit" name="submit" value="Save Changes" accesskey="p" id="publish" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>