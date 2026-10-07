import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice, PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

function Edit( { attributes, setAttributes } ) {
	const modal = window.mintchatEditorData?.modal || {
		enabled: false,
		triggerText: __( 'Contact us', 'mintchat' ),
	};
	const blockProps = useBlockProps( {
		className: 'mintchat-modal-trigger wp-element-button',
		type: 'button',
	} );
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'mintchat' ) }>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Button text', 'mintchat' ) }
						value={ attributes.buttonText }
						placeholder={ modal.triggerText }
						onChange={ ( buttonText ) =>
							setAttributes( { buttonText } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<button { ...blockProps }>
				{ attributes.buttonText.trim() || modal.triggerText }
			</button>
			{ ! modal.enabled && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Enable the contact modal under Settings > Mintchat before publishing.',
						'mintchat'
					) }
				</Notice>
			) }
		</>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
