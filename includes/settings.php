<?php
/** Global recipient Settings API screen.
 *
 * @package Mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
add_action( 'admin_menu', __NAMESPACE__ . '\\settings_menu' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\settings_assets' );

/** Register an authenticated, non-REST option with atomic validation. */
function register_settings() {
	register_setting(
		'mintchat',
		'mintchat_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings',
			'default'           => array(),
		)
	);
}

/**
 * Validate the complete submission; an error preserves all previous data.
 *
 * @param mixed $input Submitted option value.
 */
function sanitize_settings( $input ) {
	$old = settings();
	if ( ! is_array( $input ) || ! isset( $input['recipients'] ) || ! is_array( $input['recipients'] ) ) {
		add_settings_error( 'mintchat_settings', 'invalid_input', __( 'Invalid recipient settings. Nothing was changed.', 'mintchat' ) );
		return $old;
	}
	$result = array(
		'recipients'           => array(),
		'default_recipient_id' => '',
	);
	$map    = array();
	$seen   = array();
	foreach ( $input['recipients'] as $key => $row ) {
		// The empty sentinel allows an intentionally empty list to be submitted.
		if ( '_empty' === $key ) {
			continue;
		}
		if ( ! is_array( $row ) || ! isset( $row['label'], $row['number'] ) || ! is_string( $row['label'] ) || ! is_string( $row['number'] ) ) {
			add_settings_error( 'mintchat_settings', 'invalid_row', __( 'Invalid recipient. Nothing was changed.', 'mintchat' ) );
			return $old;
		}
		$label  = sanitize_text_field( $row['label'] );
		$number = normalize_phone( $row['number'] );
		if ( '' === $label || '' === $number ) {
			add_settings_error( 'mintchat_settings', 'invalid_recipient', __( 'Each recipient needs a name and an international number of 7-15 digits, starting with the country code (no 00 prefix). Nothing was changed.', 'mintchat' ) );
			return $old;
		}
		// Settings API can sanitize again on first insert; preserve normalized UUIDs.
		$id = isset( $row['id'] ) && is_string( $row['id'] ) ? $row['id'] : (string) $key;
		$id = wp_is_uuid( $id, 4 ) ? $id : wp_generate_uuid4();
		if ( isset( $seen[ $id ] ) ) {
			add_settings_error( 'mintchat_settings', 'duplicate_id', __( 'Duplicate recipient ID. Nothing was changed.', 'mintchat' ) );
			return $old;
		}
		$seen[ $id ]            = true;
		$map[ $key ]            = $id;
		$map[ $id ]             = $id;
		$result['recipients'][] = array(
			'id'              => $id,
			'label'           => $label,
			'number'          => $number,
			'default_message' => isset( $row['default_message'] ) && is_string( $row['default_message'] ) ? sanitize_textarea_field( $row['default_message'] ) : '',
		);
	}
	$default                        = isset( $input['default_recipient_id'] ) && is_string( $input['default_recipient_id'] ) ? $input['default_recipient_id'] : '';
	$result['default_recipient_id'] = $map[ $default ] ?? ( $result['recipients'][0]['id'] ?? '' );
	return $result;
}

/** Add Settings > Mintchat. */
function settings_menu() {
	add_options_page( __( 'Mintchat', 'mintchat' ), __( 'Mintchat', 'mintchat' ), 'manage_options', 'mintchat', __NAMESPACE__ . '\\settings_page' );
}

/**
 * Load the small local row editor only on our settings screen.
 *
 * @param string $hook Current admin screen hook.
 */
function settings_assets( $hook ) {
	if ( 'settings_page_mintchat' === $hook ) {
		wp_enqueue_script( 'mintchat-settings', plugins_url( '../assets/settings.js', __FILE__ ), array(), '1.0.0', true );
	}
}

/**
 * Render one accessible form row, also used by the inert template.
 *
 * @param string $key Persistent UUID or a new-row placeholder.
 * @param array  $row Recipient label and number.
 * @param string $default_id Selected default UUID.
 */
function settings_row( $key, $row, $default_id ) {
	?>
	<tr>
		<td><label><span class="screen-reader-text"><?php esc_html_e( 'Recipient name', 'mintchat' ); ?></span><input type="text" name="mintchat_settings[recipients][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" required></label></td>
		<td><label><span class="screen-reader-text"><?php esc_html_e( 'International number', 'mintchat' ); ?></span><input type="tel" name="mintchat_settings[recipients][<?php echo esc_attr( $key ); ?>][number]" value="<?php echo esc_attr( '' === $row['number'] ? '' : '+' . $row['number'] ); ?>" aria-describedby="mintchat-number-help" required></label></td>
		<td><label><span class="screen-reader-text"><?php esc_html_e( 'Default message', 'mintchat' ); ?></span><textarea name="mintchat_settings[recipients][<?php echo esc_attr( $key ); ?>][default_message]" rows="2"><?php echo esc_textarea( $row['default_message'] ?? '' ); ?></textarea></label></td>
		<td><label><input type="radio" name="mintchat_settings[default_recipient_id]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $default_id ); ?>><?php esc_html_e( 'Default', 'mintchat' ); ?></label></td>
		<td><button type="button" class="button mintchat-remove"><?php esc_html_e( 'Remove recipient', 'mintchat' ); ?></button></td>
	</tr>
	<?php
}

/** Settings API supplies the capability enforcement and nonce on submission. */
function settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$value = settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mintchat', 'mintchat' ); ?></h1>
		<p id="mintchat-number-help"><?php esc_html_e( 'Enter the country code and phone number: 7-15 digits, without a leading 00. A single leading + is optional. Spaces, parentheses, dots, and hyphens are removed when saving. Numbers are not verified with WhatsApp.', 'mintchat' ); ?></p>
		<p><?php esc_html_e( 'Removing a recipient hides buttons explicitly assigned to them. If you remove the default, the first remaining recipient becomes the default. Save changes, then reload any open block editor.', 'mintchat' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'mintchat' ); ?>
			<input type="hidden" name="mintchat_settings[recipients][_empty]" value="1">
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Recipient name', 'mintchat' ); ?></th><th><?php esc_html_e( 'International number', 'mintchat' ); ?></th><th><?php esc_html_e( 'Default message', 'mintchat' ); ?></th><th><?php esc_html_e( 'Default recipient', 'mintchat' ); ?></th><th><?php esc_html_e( 'Actions', 'mintchat' ); ?></th></tr></thead>
				<tbody id="mintchat-recipients">
					<?php
					foreach ( $value['recipients'] as $row ) {
						settings_row( $row['id'], $row, $value['default_recipient_id'] );
					}
					?>
				</tbody>
			</table>
			<p><button type="button" class="button" id="mintchat-add"><?php esc_html_e( 'Add recipient', 'mintchat' ); ?></button></p>
			<?php submit_button(); ?>
		</form>
		<template id="mintchat-row"><table><tbody>
		<?php
		settings_row(
			'__KEY__',
			array(
				'label'           => '',
				'number'          => '',
				'default_message' => '',
			),
			''
		);
		?>
		</tbody></table></template>
	</div>
	<?php
}
