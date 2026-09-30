import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { registerPlugin } from '@wordpress/plugins';
import metadata from './block.json';
import Edit from './edit';
import PostMessagePanel from './PostMessagePanel';
import './style.scss';

// Allow the chat button inside Core "Buttons", next to regular buttons: its markup
// is already a .wp-block-button item. Runs before Core blocks are registered.
addFilter(
	'blocks.registerBlockType',
	'mintchat/allow-in-buttons',
	( settings, name ) =>
		'core/buttons' === name
			? {
					...settings,
					allowedBlocks: [
						...( settings.allowedBlocks || [ 'core/button' ] ),
						metadata.name,
					],
			  }
			: settings
);

registerBlockType( metadata.name, { edit: Edit, save: () => null } );

registerPlugin( 'mintchat-post-message', { render: PostMessagePanel } );
