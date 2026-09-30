<?php
/** Recipient resolution and link rendering helpers.
 *
 * @package Mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

/** Read the single small option. */
function settings() {
	$value = get_option( 'mintchat_settings', array() );
	$empty = array(
		'recipients'           => array(),
		'default_recipient_id' => '',
	);
	if ( ! is_array( $value ) || ! isset( $value['recipients'] ) || ! is_array( $value['recipients'] ) ) {
		return $empty;
	}

	$recipients = array();
	$ids        = array();
	foreach ( $value['recipients'] as $row ) {
		if (
			! is_array( $row ) ||
			! isset( $row['id'], $row['label'], $row['number'] ) ||
			! is_string( $row['id'] ) ||
			! is_string( $row['label'] ) ||
			! is_string( $row['number'] ) ||
			! wp_is_uuid( $row['id'], 4 ) ||
			! preg_match( '/^[1-9][0-9]{6,14}$/D', $row['number'] ) ||
			isset( $ids[ $row['id'] ] )
		) {
			return $empty;
		}
		$row['default_message'] = isset( $row['default_message'] ) && is_string( $row['default_message'] ) ? $row['default_message'] : '';
		$ids[ $row['id'] ]      = true;
		$recipients[]           = $row;
	}

	$default = isset( $value['default_recipient_id'] ) && is_string( $value['default_recipient_id'] ) ? $value['default_recipient_id'] : '';
	return array(
		'recipients'           => $recipients,
		'default_recipient_id' => isset( $ids[ $default ] ) ? $default : '',
	);
}

/**
 * Resolve an explicit ID without ever falling back to another person.
 *
 * @param string $id Recipient UUID, or empty for the global default.
 */
function recipient( $id ) {
	if ( ! is_string( $id ) ) {
		return null;
	}
	$settings = settings();
	$id       = '' === $id ? $settings['default_recipient_id'] : $id;
	foreach ( $settings['recipients'] as $recipient ) {
		if ( $recipient['id'] === $id ) {
			return $recipient;
		}
	}
	return null;
}

/**
 * Convert a human-formatted international number to wa.me digits.
 *
 * @param mixed $number Submitted phone number.
 */
function normalize_phone( $number ) {
	if ( ! is_string( $number ) ) {
		return '';
	}
	$number = trim( $number );
	if ( '' === $number || 0 === strpos( $number, '00' ) || ! preg_match( '/^\+?[0-9\s().-]+$/D', $number ) ) {
		return '';
	}
	$number = preg_replace( '/[^0-9]/', '', $number );
	return preg_match( '/^[1-9][0-9]{6,14}$/D', $number ) ? $number : '';
}

/**
 * Encode the original message once, including newlines and Unicode.
 *
 * @param string $number Validated international number.
 * @param string $message Original message.
 */
function message_url( $number, $message ) {
	return 'https://wa.me/' . $number . ( '' === $message ? '' : '?text=' . rawurlencode( $message ) );
}

/** Reuse Core's decorative service glyph; omit it if the API is unavailable. */
function icon() {
	if ( ! function_exists( 'block_core_social_link_get_icon' ) ) {
		return '';
	}
	$processor = new \WP_HTML_Tag_Processor( block_core_social_link_get_icon( 'whatsapp' ) );
	if ( ! $processor->next_tag( 'svg' ) ) {
		return '';
	}
	$processor->set_attribute( 'aria-hidden', 'true' );
	$processor->set_attribute( 'focusable', 'false' );
	return wp_kses( $processor->get_updated_html(), icon_allowed_html() );
}

/** Only allow the SVG elements and attributes needed by the Core glyph. */
function icon_allowed_html() {
	return array(
		'svg'  => array(
			'xmlns'       => true,
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'fill'        => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'd'    => true,
			'fill' => true,
		),
	);
}
