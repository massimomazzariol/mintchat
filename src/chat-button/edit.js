import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const {
		recipientId,
		message,
		useDefaultMessage,
		buttonText,
		showIcon,
		openInNewTab,
	} = attributes;
	const data = window.mintchatEditorData || {
		recipients: [],
		defaultId: '',
	};
	const missing =
		recipientId &&
		! data.recipients.some( ( item ) => item.id === recipientId );
	const unavailable =
		! data.recipients.length ||
		missing ||
		( ! recipientId && ! data.defaultId );
	const blockProps = useBlockProps( {
		className: 'wp-block-button__link wp-element-button',
	} );
	const options = [
		{ label: __( 'Use global default', 'mintchat' ), value: '' },
		...data.recipients.map( ( item ) => ( {
			label: item.label,
			value: item.id,
		} ) ),
	];
	if ( missing ) {
		options.push( {
			label: __( 'Deleted recipient', 'mintchat' ),
			value: recipientId,
		} );
	}

	// Per-post override (empty outside the post editor, e.g. in the Site Editor).
	const postOverride = useSelect(
		( select ) =>
			select( 'core/editor' )?.getEditedPostAttribute( 'meta' )
				?.mintchat_post_message || '',
		[]
	);

	const activeRecipient = data.recipients.find(
		( item ) => item.id === ( recipientId || data.defaultId )
	);
	let resolvedMessage = '';
	if ( useDefaultMessage ) {
		if ( postOverride ) {
			resolvedMessage = postOverride;
		} else {
			resolvedMessage = activeRecipient
				? activeRecipient.default_message || ''
				: '';
		}
	} else {
		resolvedMessage = message;
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'mintchat' ) }>
					<SelectControl
						label={ __( 'Recipient', 'mintchat' ) }
						value={ recipientId }
						options={ options }
						onChange={ ( value ) =>
							setAttributes( { recipientId: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Use contact default message',
							'mintchat'
						) }
						checked={ useDefaultMessage }
						onChange={ ( value ) =>
							setAttributes( { useDefaultMessage: value } )
						}
					/>
					<TextareaControl
						label={ __( 'Message', 'mintchat' ) }
						value={ message }
						disabled={ useDefaultMessage }
						onChange={ ( value ) =>
							setAttributes( { message: value } )
						}
					/>
					<TextControl
						label={ __( 'Button text', 'mintchat' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show WhatsApp icon', 'mintchat' ) }
						checked={ showIcon }
						onChange={ ( value ) =>
							setAttributes( { showIcon: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Open in new tab', 'mintchat' ) }
						checked={ openInNewTab }
						onChange={ ( value ) =>
							setAttributes( { openInNewTab: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div className="wp-block-button">
				<div { ...blockProps } title={ resolvedMessage }>
					{ showIcon && (
						<span className="mintchat-button__icon" aria-hidden />
					) }
					<span className="mintchat-button__label">
						{ buttonText.trim()
							? buttonText
							: __( 'Send WhatsApp message', 'mintchat' ) }
					</span>
					{ openInNewTab && (
						<span className="screen-reader-text">
							{ __( '(opens in a new tab)', 'mintchat' ) }
						</span>
					) }
				</div>
			</div>
			{ isSelected && resolvedMessage && (
				<div className="mintchat-button__note">
					{ __( 'Message:', 'mintchat' ) } { resolvedMessage }
				</div>
			) }
			{ unavailable && (
				<Notice status="warning" isDismissible={ false }>
					{ missing
						? __(
								'Selected recipient no longer exists. Choose another recipient.',
								'mintchat'
						  )
						: __(
								'No Mintchat contacts configured.',
								'mintchat'
						  ) }{ ' ' }
					{ __(
						'Go to Settings > Mintchat. This button is hidden on the frontend until its contact is configured.',
						'mintchat'
					) }
				</Notice>
			) }
		</>
	);
}
