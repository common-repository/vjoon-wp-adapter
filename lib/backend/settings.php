<?php
/**
 * Settings page for vjoon WP Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$_option      = App::$settings->get_options(); // force to get new settings.
$nonce        = isset( $_POST['_nonce'] ) ? sanitize_text_field( $_POST['_nonce'] ) : '';
$support_mode = isset( $_GET['SupportMode'] ) ? 1 : 0;
$support_mode = isset( $_option->general->debug ) && ( ! empty( $_option->general->debug ) ) ? 1 : $support_mode;
$uuid         = isset( $_option->api->uuid ) ? $_option->api->uuid : '';

// note: check if uuid exists in APW.
$apw_exists = Functions::check_application_password();

if ( isset( $_POST['_hidden'] ) && $_POST['_hidden'] == 'true' && wp_verify_nonce( $nonce, 'vj-wp-adapter' ) ) { // Form data sent.
	if ( $support_mode ) {
		$_option->general->debug            = isset( $_POST['debug'] ) ? 1 : 0;
		$_option->general->compression_ajax = isset( $_POST['compression_ajax'] ) ? 1 : 0;
		$_option->general->minified_jscss   = isset( $_POST['minified_jscss'] ) ? '.min' : '';
		$_option->general->inline_style     = isset( $_POST['inline_style'] ) ? 1 : 0;
	} else {
		$_option->general->debug            = isset( $_option->general->debug ) ? $_option->general->debug : 0;
		$_option->general->compression_ajax = isset( $_option->general->compression_ajax ) ? $_option->general->compression_ajax : 1;
		$_option->general->minified_jscss   = isset( $_option->general->minified_jscss ) && ! empty( $_option->general->minified_jscss ) ? '.min' : '';
		$_option->general->inline_style     = isset( $_option->general->inline_style ) ? $_option->general->inline_style : 1;

	}

	if ( ! isset( $_option->api ) ) {
		$_option->api = (object) array();
	}
	$_option->api->apw    = isset( $_POST['api_apw'] ) ? Crypt::encrypt( sanitize_text_field( $_POST['api_apw'] ) ) : '';
	$_option->api->key    = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
	$_option->api->secret = isset( $_POST['api_secret'] ) ? sanitize_text_field( $_POST['api_secret'] ) : '';

	App::$settings->set_options( $_option );
	App::$settings->save();

	?><script> jQuery(document).ready(function($) { $('.updated').fadeIn().delay(10000).fadeOut();});</script>
	<?php

} else { // Normal page display.
}

?>
<div class="wrap">
	<h1><?php _e( 'vj-wp-adapter.settings.admin.headline', 'vj-wp-adapter' ); ?></h1>
	<div class="updated" style="display:none"><p><strong><?php _e( 'vj-wp-adapter.settings.admin.saved.msg', 'vj-wp-adapter' ); ?></strong></p>
	</div>

	<form novalidate="novalidate" name="_form" method="post" action="<?php echo esc_url( str_replace( '%7E', '~', isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) ); ?>">
		<input type="hidden" name="_hidden" value="true">
		<input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'vj-wp-adapter' ) ); ?>">
		<table class="form-table settings">
			<tbody>

				<tr>
					<th scope="row"><label for="vjwpad_init"><?php _e( 'vj-wp-adapter.settings.admin.reinit.label', 'vj-wp-adapter' ); ?></label></th>
					<td>
						<button name="vjwpad_init" id="initApp"><?php _e( 'vj-wp-adapter.settings.admin.btn.reinit.label', 'vj-wp-adapter' ); ?></button>
						<span class="description" id="vjwpad_init_desc"><?php _e( 'vj-wp-adapter.settings.admin.reinit.tt', 'vj-wp-adapter' ); ?></span>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="api_apw"><?php _e( 'vj-wp-adapter.settings.admin.apw.label', 'vj-wp-adapter' ); ?></label></th>
					<td>
						<input type="password" id="apw" name="api_apw" value="<?php echo isset( $_option->api->apw ) && ! empty( $apw_exists ) ? esc_attr( Crypt::decrypt( $_option->api->apw ) ) : ''; ?>" data-uuid="<?php echo esc_attr( $uuid ); ?>" aria-describedby="api_apw_desc" class="regular-text" <?php echo $support_mode ? '' : 'readonly'; ?>>
						<button id="rcAPW"><?php _e( 'vj-wp-adapter.settings.admin.btn.apwcreate.label', 'vj-wp-adapter' ); ?></button>
						<button id="copyAPW" class="copyToClipboard" ><?php _e( 'vj-wp-adapter.settings.admin.btn.apwcopy.label', 'vj-wp-adapter' ); ?></button>
						<?php echo $support_mode ? '<button id="btnSHAPW" data-sh=0 class="supportmode">Show password</button>' : ''; ?>
						<br>
						<span class="description" id="api_apw_desc"><?php _e( 'vj-wp-adapter.settings.admin.apw.tt', 'vj-wp-adapter' ); ?></span>
					</td>

				</tr>

				<?php
				if ( ! is_multisite() ) {
					?>
						<tr>
							<th scope="row"><label for="api_key"><?php _e( 'vj-wp-adapter.settings.admin.apikey.label', 'vj-wp-adapter' ); ?></label></th>
							<td>
								<input type="text" id="key" name="api_key" value="<?php echo esc_attr( $_option->api->key ); ?>" aria-describedby="api_key_desc" class="regular-text" <?php echo $support_mode ? '' : 'readonly'; ?>>
								<button id="rcKey"><?php _e( 'vj-wp-adapter.settings.admin.btn.keycreate.label', 'vj-wp-adapter' ); ?></button>
								<button id="copyKey" class="copyToClipboard" ><?php _e( 'vj-wp-adapter.settings.admin.btn.keycopy.label', 'vj-wp-adapter' ); ?></button>
								<br>
								<span class="description" id="api_key_desc"><?php _e( 'vj-wp-adapter.settings.admin.apikey.tt', 'vj-wp-adapter' ); ?></span>
							</td>

						</tr>

						<tr>
							<th scope="row"><label for="api_secret"><?php _e( 'vj-wp-adapter.settings.admin.apisecret.label', 'vj-wp-adapter' ); ?></label></th>
							<td>
								<input type="password" id="secret" name="api_secret" value="<?php echo esc_attr( $_option->api->secret ); ?>" aria-describedby="api_secret_desc" class="regular-text" <?php echo $support_mode ? '' : 'readonly'; ?>>
								<button id="rcSecret"><?php _e( 'vj-wp-adapter.settings.admin.btn.secretcreate.label', 'vj-wp-adapter' ); ?></button>
								<button id="copySecret" class="copyToClipboard" ><?php _e( 'vj-wp-adapter.settings.admin.btn.secretcopy.label', 'vj-wp-adapter' ); ?></button>
								<?php echo $support_mode ? '<button id="btnSH" data-sh=0 class="supportmode">Show secret</button>' : ''; ?>
								<br>
								<span class="description" id="api_secret_desc"><?php _e( 'vj-wp-adapter.settings.admin.apisecret.tt', 'vj-wp-adapter' ); ?></span>
							</td>

						</tr>  
					<?php
				}

				?>


				<tr>
					<th scope="row"><label for="api_url"><?php _e( 'vj-wp-adapter.settings.admin.apiurl.label', 'vj-wp-adapter' ); ?></label></th>
					<td>
						<input type="text" class="as-label-text" id="api_url" name="api_url" value="<?php echo esc_url( home_url() ); ?>">
						<button name="vjwpad_apiurl" id="copyURL" class="copyToClipboard" ><?php _e( 'vj-wp-adapter.settings.admin.btn.urlcopy.label', 'vj-wp-adapter' ); ?></button>
					</td>
				</tr>
				</tbody>
		</table>				
		<?php
		if ( $support_mode ) {
			?>
					<fieldset class="supportmode">
						<legend>&nbsp;SupportMode&nbsp;</legend>
						<table class="form-table settings supportmode">
							<tbody>

								<tr>
									<th scope="row"><label for="debug">SupportMode</label></th>
									<td>
										<input type="checkbox" name="debug" value="1" <?php checked( $_option->general->debug ); ?> aria-describedby="debug_desc" class="regular-text">
										<span class="description" id="debug_desc">Enable only on advice</span>
									</td>

								</tr>

								<tr>
									<th scope="row"><label for="minified_jscss">Minified JS/CSS</label></th>
									<td>
										<input type="checkbox" name="minified_jscss" value="1" <?php checked( $_option->general->minified_jscss == '.min' ); ?> aria-describedby="minified_jscss_desc" class="regular-text">
										<span class="description" id="minified_jscss_desc">Enable to use internal js/css minified files</span>
									</td>
								</tr>

								<tr>
									<th scope="row"><label for="inline_style">Inline JS/CSS</label></th>
									<td>
										<input type="checkbox" name="inline_style" value="1" <?php checked( $_option->general->inline_style ); ?> aria-describedby="inline_style_desc" class="regular-text">
										<span class="description" id="inline_style_desc">Enable to load internal js/css files inline</span>
									</td>
								</tr>

								<tr>
									<th scope="row"><label for="compression_ajax">Compress Data</label></th>
									<td>
										<input type="checkbox" name="compression_ajax" value="1" <?php checked( $_option->general->compression_ajax ); ?> aria-describedby="compression_ajax_desc" class="regular-text">
										<span class="description" id="compression_ajax_desc">Enable to compress data between client and server.</span>
									</td>
								</tr>

								<tr>
									<th scope="row"><label for="vjwpad_gc">Clean Up</label></th>
									<td>
										<button name="vjwpad_gc" id="garbageCollect">Clean up now</button>
										<span class="description" id="vjwpad_gc_desc">Clean up and delete previews older than 24 hours</span>
									</td>
								</tr>

							</tbody>
						</table>

					</fieldset>
				<?php
		}
		?>
					   
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'vj-wp-adapter.settings.admin.btn.save.label', 'vj-wp-adapter' ); ?>" />
		</p>
	</form>
</div>

<script>

	jQuery(document).ready(function($){ //on Document ready
		init( function() { //initialize vars
			appConsole.log('load settings...');
		}); 

		$(document).on('click','table.form-table tbody button', function(e) {
			e.preventDefault();

			var btnId = $(this).attr('id');
			var btnClass = $(this).attr('class');
			appConsole.log('action requested',btnId );

			if (btnId != 'btnSH' && btnId != 'btnSHAPW' && btnClass !='copyToClipboard') {
				$.post({
					url: _ajaxurl,
					data: { 'action': 'app_ajaxcall', 'operation': btnId },
					success:function(data) {
						try {
							var jsonData = '';
							if ( compression_ajax == 1 ) {
								var lz = atob(data);
								jsonData = JSON.parse(lz);
							} else {
								jsonData = JSON.parse(data);
							}

							appConsole.log('Response returned', jsonData);
							switch (jsonData.return) {
								case 'initApp':
									$('.updated').html('<p><strong><?php _e( 'vj-wp-adapter.settings.admin.exec.msg', 'vj-wp-adapter' ); ?></strong></p>')
									$('.updated').fadeIn().delay(10000).fadeOut();
									break;

								case 'rcKey':
									$('input#key').val(jsonData.result);
									$('input#key').attr('value', jsonData.result);
									break;

								case 'rcSecret':
									$('input#secret').val(jsonData.result);
									$('input#secret').attr('value', jsonData.result);
									break;

								case 'rcAPW':
									$('input#apw').val(jsonData.result);
									$('input#apw').attr('value', jsonData.result);
									$('input#apw').data('uuid', jsonData.uuid);
									break;

								case 'garbageCollect':
									$('.updated').html('<p><strong><?php _e( 'vj-wp-adapter.settings.admin.exec.msg', 'vj-wp-adapter' ); ?></strong></p>')
									$('.updated').fadeIn().delay(10000).fadeOut();
									break;

								default:
								appConsole.log('notKnownOrImplemented - returned',jsonData);
									break;

							}
						} catch (e) {
							appConsole.log('error ', e)
						}   
					},
					error:function(errorThrown) {
						appConsole.log('error',errorThrown);
						return false;
					}
				});
			} else if (btnId == 'copyURL') {
				var copy  = copyToClipboard('api_url');
				$('.updated').html('<p><strong><?php _e( 'vj-wp-adapter.settings.admin.apiurl.msg', 'vj-wp-adapter' ); ?></strong></p>')
				fade_out('.updated');
			} else if (btnId == 'copyKey') {
				var copy  = copyToClipboard('key');
				$('.updated').html('<p><strong><?php _e( 'vj-wp-adapter.settings.admin.apikey.msg', 'vj-wp-adapter' ); ?></strong></p>')
				fade_out('.updated');
			} else if (btnId == 'copySecret') {
				$('table.form-table tbody input#secret').prop('type','text');
				var copy  = copyToClipboard('secret');
				$('table.form-table tbody input#secret').prop('type','password');
				$('.updated').html('<p><strong><?php _e( 'vj-wp-adapter.settings.admin.apisecret.msg', 'vj-wp-adapter' ); ?></strong></p>')
				fade_out('.updated');
			} else if (btnId == 'copyAPW') {
				$('table.form-table tbody input#apw').prop('type','text');
				var copy  = copyToClipboard('apw');
				$('table.form-table tbody input#apw').prop('type','password');
				$('.updated').html('<p><strong><?php _e( 'vj-wp-adapter.settings.admin.apw.msg', 'vj-wp-adapter' ); ?></strong></p>')
				fade_out('.updated');
			} else if (btnId == 'btnSH') {
				var action = $('table.form-table tbody button#btnSH').data('sh');
				if (action == 0) { //note: show Secret.
					toggle_hide('table.form-table tbody button#btnSH', 'table.form-table tbody input#secret', 'secret' );
				} else { //note: hide Secret.
					toggle_show( 'table.form-table tbody button#btnSH', 'table.form-table tbody input#secret', 'secret' );
				}
			} else if (btnId == 'btnSHAPW') {
				var action = $('table.form-table tbody button#btnSHAPW').data('sh');
				if (action == 0) { //note: show.
					toggle_hide('table.form-table tbody button#btnSHAPW', 'table.form-table tbody input#apw', 'password' );
				} else { //note: hide.
					toggle_show( 'table.form-table tbody button#btnSHAPW', 'table.form-table tbody input#apw', 'secret' );
				}
			}
			
			function fade_out( elem ) {
				$( elem ).fadeIn().delay( 10000 ).fadeOut();
			}

			function toggle_hide( elem1, elem2, text ) {
				$( elem1 ).text('Hide ' + text);
				$( elem1 ).data('sh',1);
				$( elem2 ).prop('type','text');
			}

			function toggle_show( elem1, elem2, text ) {
				$( elem1 ).text('Show ' + text);
				$( elem1 ).data('sh',0);
				$( elem2 ).prop('type','password');
			}

		});
	});
</script>
