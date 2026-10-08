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
	$map          = array();
	$seen         = array();
	$translations = array(); // array( message, locale, translation ), handed over once the input is valid.
	foreach ( $input['recipients'] as $key => $row ) {
		// The empty sentinel allows an intentionally empty list to be submitted.
		if ( '_empty' === $key ) {
			continue;
		}
		// Ignore an unused row added in the browser; existing rows still require explicit removal.
		if (
			0 === strpos( (string) $key, 'new-' ) &&
			is_array( $row ) &&
			isset( $row['label'], $row['number'] ) &&
			is_string( $row['label'] ) &&
			is_string( $row['number'] ) &&
			'' === trim( $row['label'] ) &&
			'' === trim( $row['number'] )
		) {
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
		$message                = end( $result['recipients'] )['default_message'];
		foreach ( (array) ( $row['translations'] ?? array() ) as $locale => $text ) {
			if ( '' !== $message && is_string( $locale ) && is_string( $text ) ) {
				$translations[] = array( $message, $locale, sanitize_textarea_field( $text ) );
			}
		}
	}
	$default                        = isset( $input['default_recipient_id'] ) && is_string( $input['default_recipient_id'] ) ? $input['default_recipient_id'] : '';
	$result['default_recipient_id'] = $map[ $default ] ?? ( $result['recipients'][0]['id'] ?? '' );
	$result['modal']                = normalize_modal( $input['modal'] ?? array(), $map );
	// Translations of the default messages belong to the translation plugin (LangSail): hand them over.
	foreach ( $translations as $translation ) {
		do_action( 'langsail_set_translation', $translation[0], $translation[1], $translation[2] );
	}
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
		wp_enqueue_script( 'mintchat-settings', plugins_url( '../assets/settings.js', __FILE__ ), array(), VERSION, true );
		wp_enqueue_style( 'mintchat-settings', plugins_url( '../assets/settings.css', __FILE__ ), array(), VERSION );
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
		<td>
			<label><span class="screen-reader-text"><?php esc_html_e( 'Default message', 'mintchat' ); ?></span><textarea name="mintchat_settings[recipients][<?php echo esc_attr( $key ); ?>][default_message]" rows="2"><?php echo esc_textarea( $row['default_message'] ?? '' ); ?></textarea></label>
			<?php
			// One field per site language when a translation plugin offers them (LangSail filters).
			foreach ( apply_filters( 'langsail_languages', array() ) as $locale => $language ) :
				$translation = '' === ( $row['default_message'] ?? '' ) ? '' : apply_filters( 'langsail_get_translation', '', $row['default_message'], $locale );
				?>
				<label class="mintchat-translation">
					<span>
						<?php if ( ! empty( $language['flag'] ) ) : ?>
							<img src="<?php echo esc_url( $language['flag'] ); ?>" alt="" width="16" height="16">
						<?php endif; ?>
						<?php echo esc_html( $language['name'] ); ?>
					</span>
					<textarea name="mintchat_settings[recipients][<?php echo esc_attr( $key ); ?>][translations][<?php echo esc_attr( $locale ); ?>]" rows="2" lang="<?php echo esc_attr( $language['prefix'] ?? '' ); ?>"><?php echo esc_textarea( $translation ); ?></textarea>
				</label>
			<?php endforeach; ?>
		</td>
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
			<?php settings_modal( $value ); ?>
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

/**
 * Render the contact modal section from the single field definition.
 *
 * @param array $value Current settings.
 */
function settings_modal( $value ) {
	$modal  = $value['modal'];
	$limits = map_limits();
	?>
	<h2><?php esc_html_e( 'Contact modal', 'mintchat' ); ?></h2>
	<p><?php esc_html_e( 'Mintchat renders this modal automatically in the site footer. Add a Mintchat Modal Trigger block where visitors should open it.', 'mintchat' ); ?></p>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><?php esc_html_e( 'Enable modal', 'mintchat' ); ?></th><td><label><input type="checkbox" name="mintchat_settings[modal][enabled]" value="1" <?php checked( $modal['enabled'] ); ?>> <?php esc_html_e( 'Render the contact modal on the frontend', 'mintchat' ); ?></label></td></tr>
		<tr><th scope="row"><label for="mintchat-modal-recipient_id"><?php esc_html_e( 'WhatsApp recipient', 'mintchat' ); ?></label></th><td><select id="mintchat-modal-recipient_id" name="mintchat_settings[modal][recipient_id]"><option value=""><?php esc_html_e( 'Use global default', 'mintchat' ); ?></option><?php foreach ( $value['recipients'] as $row ) : ?><option value="<?php echo esc_attr( $row['id'] ); ?>" <?php selected( $modal['recipient_id'], $row['id'] ); ?>><?php echo esc_html( $row['label'] ); ?></option><?php endforeach; ?></select></td></tr>
		<?php
		foreach ( modal_fields() as $key => $field ) :
			$id   = 'mintchat-modal-' . $key;
			$name = 'mintchat_settings[modal][' . $key . ']';
			$help = isset( $field['help'] ) ? $id . '-help' : '';
			?>
			<tr><th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th><td>
			<?php if ( 'textarea' === $field['type'] ) : ?>
				<textarea class="large-text" rows="3" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $modal[ $key ] ); ?></textarea>
			<?php elseif ( isset( $limits[ $field['type'] ] ) ) : ?>
				<input class="<?php echo 'zoom' === $field['type'] ? 'small-text' : 'regular-text'; ?>" id="<?php echo esc_attr( $id ); ?>" type="number" min="<?php echo esc_attr( $limits[ $field['type'] ][0] ); ?>" max="<?php echo esc_attr( $limits[ $field['type'] ][1] ); ?>" step="<?php echo 'zoom' === $field['type'] ? '1' : 'any'; ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $modal[ $key ] ); ?>"<?php echo $help ? ' aria-describedby="' . esc_attr( $help ) . '"' : ''; ?>>
			<?php else : ?>
				<input class="regular-text" id="<?php echo esc_attr( $id ); ?>" type="<?php echo 'email' === $field['type'] ? 'email' : 'text'; ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $modal[ $key ] ); ?>">
			<?php endif; ?>
			<?php if ( $help ) : ?>
				<p class="description" id="<?php echo esc_attr( $help ); ?>"><?php echo esc_html( $field['help'] ); ?></p>
			<?php endif; ?>
			</td></tr>
		<?php endforeach; ?>
	</table>
	<?php
}