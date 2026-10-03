<?php
/**
 * Abilities API: lets AI agents and other clients (REST, MCP) list the chat
 * contacts and add a chat button to a post. Phone numbers never leave the server.
 *
 * @package Mintchat
 */

namespace Mintchat;

defined( 'ABSPATH' ) || exit;

add_action( 'wp_abilities_api_categories_init', __NAMESPACE__ . '\\register_ability_category' );
add_action( 'wp_abilities_api_init', __NAMESPACE__ . '\\register_abilities' );

/** Register the Mintchat ability category. */
function register_ability_category() {
	wp_register_ability_category(
		'mintchat',
		array(
			'label'       => __( 'Mintchat', 'mintchat' ),
			'description' => __( 'Click-to-chat contacts and buttons.', 'mintchat' ),
		)
	);
}

/** Register the Mintchat abilities. */
function register_abilities() {
	wp_register_ability(
		'mintchat/list-contacts',
		array(
			'label'               => __( 'List chat contacts', 'mintchat' ),
			'description'         => __( 'Lists the WhatsApp contacts configured in Mintchat, with their IDs, names and which one is the default. Use an ID with mintchat/add-chat-button. Phone numbers are not returned.', 'mintchat' ),
			'category'            => 'mintchat',
			'output_schema'       => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'                  => array( 'type' => 'string' ),
						'label'               => array( 'type' => 'string' ),
						'is_default'          => array( 'type' => 'boolean' ),
						'has_default_message' => array( 'type' => 'boolean' ),
					),
				),
			),
			'execute_callback'    => __NAMESPACE__ . '\\ability_list_contacts',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'public'      => true,
				'mcp'         => array( 'public' => true ), // MCP Adapter default server.
				'annotations' => array(
					'readonly'   => true,
					'idempotent' => true,
				),
			),
		)
	);

	wp_register_ability(
		'mintchat/add-chat-button',
		array(
			'label'               => __( 'Add a chat button to a post', 'mintchat' ),
			'description'         => __( 'Adds a Mintchat WhatsApp button block at the start or end of a post or page. Without contact_id the default contact is used. The message pre-fills the chat; leave it empty to use the post message or none.', 'mintchat' ),
			'category'            => 'mintchat',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id'             => array(
						'type'        => 'integer',
						'description' => __( 'ID of the post or page to edit.', 'mintchat' ),
						'minimum'     => 1,
					),
					'contact_id'          => array(
						'type'        => 'string',
						'description' => __( 'Contact ID from mintchat/list-contacts. Empty for the default contact.', 'mintchat' ),
						'default'     => '',
					),
					'button_text'         => array(
						'type'        => 'string',
						'description' => __( 'Visible button label.', 'mintchat' ),
						'default'     => '',
					),
					'message'             => array(
						'type'        => 'string',
						'description' => __( 'Message that pre-fills the chat.', 'mintchat' ),
						'default'     => '',
					),
					'use_default_message' => array(
						'type'        => 'boolean',
						'description' => __( "Use the contact's default message instead of message.", 'mintchat' ),
						'default'     => false,
					),
					'style'               => array(
						'type'    => 'string',
						'enum'    => array( 'fill', 'outline', 'theme' ),
						'default' => 'fill',
					),
					'position'            => array(
						'type'    => 'string',
						'enum'    => array( 'start', 'end' ),
						'default' => 'end',
					),
				),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer' ),
					'block'   => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => __NAMESPACE__ . '\\ability_add_chat_button',
			'permission_callback' => static function ( $input ) {
				$post_id = is_array( $input ) && isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
				return $post_id > 0 && current_user_can( 'edit_post', $post_id );
			},
			'meta'                => array(
				'public'      => true,
				'mcp'         => array( 'public' => true ), // MCP Adapter default server.
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);
}

/** Contacts without their numbers. */
function ability_list_contacts() {
	$settings = settings();
	return array_map(
		static function ( $recipient ) use ( $settings ) {
			return array(
				'id'                  => $recipient['id'],
				'label'               => $recipient['label'],
				'is_default'          => $recipient['id'] === $settings['default_recipient_id'],
				'has_default_message' => '' !== $recipient['default_message'],
			);
		},
		$settings['recipients']
	);
}

/**
 * Insert a chat button block into a post.
 *
 * @param array $input Validated ability input.
 * @return array|\WP_Error
 */
function ability_add_chat_button( $input ) {
	$post = get_post( (int) $input['post_id'] );
	if ( ! $post ) {
		return new \WP_Error( 'mintchat_post_not_found', __( 'Post not found.', 'mintchat' ) );
	}

	$contact_id = isset( $input['contact_id'] ) ? (string) $input['contact_id'] : '';
	if ( null === recipient( $contact_id ) ) {
		return new \WP_Error(
			'mintchat_contact_not_found',
			'' === $contact_id ? __( 'No default contact is configured in Mintchat.', 'mintchat' ) : __( 'Unknown contact ID.', 'mintchat' )
		);
	}

	// Only non-default attributes, so the saved markup stays as short as the editor's.
	$attrs = array_filter(
		array(
			'recipientId'       => $contact_id,
			'buttonText'        => isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : '',
			'message'           => isset( $input['message'] ) ? sanitize_textarea_field( $input['message'] ) : '',
			'useDefaultMessage' => ! empty( $input['use_default_message'] ),
		)
	);
	$style = isset( $input['style'] ) ? $input['style'] : 'fill';
	if ( 'fill' !== $style ) {
		$attrs['className'] = 'is-style-' . $style;
	}

	$block = serialize_block(
		array(
			'blockName'    => 'mintchat/chat-button',
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);

	$content = trim( $post->post_content );
	$content = ( isset( $input['position'] ) && 'start' === $input['position'] )
		? $block . ( '' === $content ? '' : "\n\n" . $content )
		: ( '' === $content ? '' : $content . "\n\n" ) . $block;

	$result = wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => wp_slash( $content ),
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return array(
		'post_id' => $post->ID,
		'block'   => $block,
	);
}
