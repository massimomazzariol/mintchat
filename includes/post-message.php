<?php
/**
 * Per-post WhatsApp message override.
 *
 * @package Mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

/** Meta key holding the per-post WhatsApp message override. */
const POST_MESSAGE_KEY = 'mintchat_post_message';

add_action( 'init', __NAMESPACE__ . '\\register_post_message_meta' );

/**
 * Register the REST-visible post meta that Gutenberg writes through the post
 * endpoint, for every post type that supports custom fields. Authorization
 * uses the standard 'edit_post' capability against the specific object ID.
 */
function register_post_message_meta() {
	register_post_meta(
		'',
		POST_MESSAGE_KEY,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_post_message',
			'auth_callback'     => __NAMESPACE__ . '\\post_message_auth_callback',
			'default'           => '',
		)
	);
}

/**
 * Keep the override as plain, tag-free text.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function sanitize_post_message( $value ) {
	return is_string( $value ) ? sanitize_textarea_field( $value ) : '';
}

/**
 * Authorization callback for the post message meta.
 *
 * Grants access when the current user can edit the specific post object.
 *
 * @param bool   $allowed   Default allowed value (ignored).
 * @param string $meta_key  The meta key being checked.
 * @param int    $object_id The post ID.
 * @param string $cap       Capability name ('edit_post_meta' or 'delete_post_meta').
 * @param int    $user_id   Current user ID.
 * @return bool
 */
function post_message_auth_callback( $allowed, $meta_key, $object_id, $cap, $user_id ) {
	return current_user_can( 'edit_post', $object_id );
}

/**
 * Current main-query post ID, or 0 when no singular post is rendered.
 *
 * @return int
 */
function post_message_post_id() {
	return is_singular() ? get_queried_object_id() : 0;
}

/**
 * Resolve the stored override for a post.
 *
 * @param int $post_id Post ID.
 * @return string Stored override, or '' when unset.
 */
function post_message( $post_id ) {
	return (string) get_post_meta( $post_id, POST_MESSAGE_KEY, true );
}
