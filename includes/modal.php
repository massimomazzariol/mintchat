<?php
/** Global contact modal: one dialog rendered in the footer, opened by the Modal Trigger block.
 *
 * @package Mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\modal_assets' );
add_action( 'wp_footer', __NAMESPACE__ . '\\render_modal' );

/**
 * The modal's editable fields: the single definition used for defaults, validation, saving and
 * the settings screen. Types: text, textarea, email, latitude, longitude, zoom.
 */
function modal_fields() {
	return array(
		'eyebrow'              => array( 'type' => 'text', 'label' => __( 'Small heading', 'mintchat' ) ),
		'title'                => array( 'type' => 'text', 'label' => __( 'Title', 'mintchat' ) ),
		'description'          => array( 'type' => 'textarea', 'label' => __( 'Description', 'mintchat' ) ),
		'whatsapp_button_text' => array( 'type' => 'text', 'label' => __( 'WhatsApp button text', 'mintchat' ) ),
		'email'                => array( 'type' => 'email', 'label' => __( 'Email', 'mintchat' ) ),
		'phone'                => array( 'type' => 'text', 'label' => __( 'Phone', 'mintchat' ) ),
		'address'              => array( 'type' => 'textarea', 'label' => __( 'Address', 'mintchat' ) ),
		'map_latitude'         => array( 'type' => 'latitude', 'label' => __( 'Map latitude', 'mintchat' ) ),
		'map_longitude'        => array( 'type' => 'longitude', 'label' => __( 'Map longitude', 'mintchat' ), 'help' => __( 'The map appears only when both coordinates are set.', 'mintchat' ) ),
		'map_zoom'             => array( 'type' => 'zoom', 'label' => __( 'Map zoom', 'mintchat' ) ),
		'map_label'            => array( 'type' => 'text', 'label' => __( 'Map accessible label', 'mintchat' ) ),
		'trigger_text'         => array( 'type' => 'text', 'label' => __( 'Default trigger text', 'mintchat' ) ),
	);
}

/**
 * OpenStreetMap limits: coordinates in degrees and the public tile zoom range.
 * Protocol constants, not settings.
 */
function map_limits() {
	return array(
		'latitude'  => array( -90, 90 ),
		'longitude' => array( -180, 180 ),
		'zoom'      => array( 1, 19, 15 ), // Minimum, maximum, default.
	);
}

/**
 * Normalize stored or submitted modal settings. Every value is sanitized for its type, an unknown
 * recipient falls back to the global default, so the same rules apply when reading and saving.
 *
 * @param mixed $value         Modal settings array.
 * @param array $recipient_map Accepted recipient keys mapped to recipient UUIDs.
 */
function normalize_modal( $value, $recipient_map ) {
	$value     = is_array( $value ) ? $value : array();
	$limits    = map_limits();
	$recipient = isset( $value['recipient_id'] ) && is_string( $value['recipient_id'] ) ? $value['recipient_id'] : '';
	$result    = array(
		'enabled'      => ! empty( $value['enabled'] ),
		'recipient_id' => $recipient_map[ $recipient ] ?? '',
	);
	foreach ( modal_fields() as $key => $field ) {
		$raw = $value[ $key ] ?? '';
		switch ( $field['type'] ) {
			case 'textarea':
				$result[ $key ] = is_string( $raw ) ? sanitize_textarea_field( $raw ) : '';
				break;
			case 'email':
				$result[ $key ] = is_string( $raw ) ? sanitize_email( $raw ) : '';
				break;
			case 'latitude':
			case 'longitude':
				list( $min, $max ) = $limits[ $field['type'] ];
				$number            = is_scalar( $raw ) && is_numeric( $raw ) ? (float) $raw : null;
				$result[ $key ]    = null !== $number && $number >= $min && $number <= $max ? (string) $number : '';
				break;
			case 'zoom':
				list( $min, $max, $default ) = $limits['zoom'];
				$zoom                        = is_scalar( $raw ) && is_numeric( $raw ) ? (int) $raw : $default;
				$result[ $key ]              = (string) min( $max, max( $min, $zoom ) );
				break;
			default:
				$result[ $key ] = is_string( $raw ) ? sanitize_text_field( $raw ) : '';
		}
	}
	return $result;
}

/** Active modal state, or null when it is disabled or its recipient is gone. */
function modal_configuration() {
	$settings = settings();
	if ( ! $settings['modal']['enabled'] ) {
		return null;
	}
	$recipient = recipient( $settings['modal']['recipient_id'] );
	return $recipient ? array(
		'settings'  => $settings['modal'],
		'recipient' => $recipient,
	) : null;
}

/**
 * Trigger label: the configured default, else a generic one.
 *
 * @param array $settings Modal settings.
 */
function modal_trigger_text( $settings ) {
	return '' !== $settings['trigger_text'] ? $settings['trigger_text'] : __( 'Contact us', 'mintchat' );
}

/**
 * OpenStreetMap embed URL for the configured point, or '' without coordinates.
 * The box spans about one tile around the marker at the chosen zoom.
 *
 * @param array $settings Modal settings.
 */
function modal_map_url( $settings ) {
	if ( '' === $settings['map_latitude'] || '' === $settings['map_longitude'] ) {
		return '';
	}
	$latitude  = (float) $settings['map_latitude'];
	$longitude = (float) $settings['map_longitude'];
	$delta     = 0.01 * pow( 2, map_limits()['zoom'][2] - (int) $settings['map_zoom'] );
	$bounds    = implode( ',', array( $longitude - $delta, $latitude - ( $delta * 0.6 ), $longitude + $delta, $latitude + ( $delta * 0.6 ) ) );
	return 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode( $bounds ) . '&layer=mapnik&marker=' . rawurlencode( $latitude . ',' . $longitude );
}

/** Load the modal styles and script only while the modal is active. */
function modal_assets() {
	if ( ! modal_configuration() ) {
		return;
	}
	// The WhatsApp action reuses the Chat Button styles (Fill, Outline, Theme).
	wp_enqueue_style( 'mintchat-modal', plugins_url( '../assets/modal.css', __FILE__ ), array( 'mintchat-chat-button-style' ), VERSION );
	wp_enqueue_script( 'mintchat-modal', plugins_url( '../assets/modal.js', __FILE__ ), array(), VERSION, array( 'strategy' => 'defer' ) );
}

/** Render the single global modal in the footer, outside the editable template content. */
function render_modal() {
	$config = modal_configuration();
	if ( ! $config ) {
		return;
	}
	$settings  = $config['settings'];
	$recipient = $config['recipient'];
	$post_id   = post_message_post_id();
	$message   = $post_id > 0 && '' !== post_message( $post_id ) ? post_message( $post_id ) : ( $recipient['default_message'] ?? '' );
	$map_url   = modal_map_url( $settings );
	$labelled  = '' !== $settings['title'];
	?>
	<div class="mintchat-modal" id="mintchat-modal" hidden>
		<div class="mintchat-modal__overlay" data-mintchat-modal-close>
			<div class="mintchat-modal__dialog" role="dialog" aria-modal="true" tabindex="-1"
				<?php if ( $labelled ) : ?>
					aria-labelledby="mintchat-modal-title"
				<?php else : ?>
					aria-label="<?php esc_attr_e( 'Contact options', 'mintchat' ); ?>"
				<?php endif; ?>
				<?php if ( '' !== $settings['description'] ) : ?>
					aria-describedby="mintchat-modal-description"
				<?php endif; ?>
			>
				<button class="mintchat-modal__close" type="button" data-mintchat-modal-close aria-label="<?php esc_attr_e( 'Close contact dialog', 'mintchat' ); ?>">&times;</button>
				<?php if ( '' !== $settings['eyebrow'] ) : ?>
					<p class="mintchat-modal__eyebrow"><?php echo esc_html( $settings['eyebrow'] ); ?></p>
				<?php endif; ?>
				<?php if ( $labelled ) : ?>
					<h2 class="mintchat-modal__title" id="mintchat-modal-title"><?php echo esc_html( $settings['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== $settings['description'] ) : ?>
					<p class="mintchat-modal__description" id="mintchat-modal-description"><?php echo nl2br( esc_html( $settings['description'] ) ); ?></p>
				<?php endif; ?>
				<div class="mintchat-modal__actions wp-block-button">
					<a class="wp-block-button__link wp-element-button wp-block-mintchat-chat-button" href="<?php echo esc_url( message_url( $recipient['number'], $message ) ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="mintchat-button__icon" aria-hidden="true"></span>
						<span class="mintchat-button__label"><?php echo esc_html( '' !== $settings['whatsapp_button_text'] ? $settings['whatsapp_button_text'] : __( 'Message us on WhatsApp', 'mintchat' ) ); ?></span>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'mintchat' ); ?></span>
					</a>
				</div>
				<?php if ( '' !== $settings['email'] || '' !== $settings['phone'] || '' !== $settings['address'] ) : ?>
					<div class="mintchat-modal__details">
						<?php if ( '' !== $settings['email'] ) : ?>
							<p class="mintchat-modal__email"><a href="mailto:<?php echo esc_attr( $settings['email'] ); ?>"><?php echo esc_html( $settings['email'] ); ?></a></p>
						<?php endif; ?>
						<?php if ( '' !== $settings['phone'] ) : ?>
							<p class="mintchat-modal__phone"><?php echo esc_html( $settings['phone'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $settings['address'] ) : ?>
							<address class="mintchat-modal__address"><?php echo nl2br( esc_html( $settings['address'] ) ); ?></address>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $map_url ) : ?>
					<div class="mintchat-modal__map">
						<iframe data-mintchat-map data-src="<?php echo esc_url( $map_url ); ?>" title="<?php echo esc_attr( '' !== $settings['map_label'] ? $settings['map_label'] : __( 'Location map', 'mintchat' ) ); ?>" loading="lazy" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-popups"></iframe>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
