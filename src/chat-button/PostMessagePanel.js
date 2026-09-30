import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextareaControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const META_KEY = 'mintchat_post_message';

/**
 * Per-post WhatsApp message override in the document settings sidebar.
 * Shown for every post type where the meta is available (custom fields support).
 */
export default function MintchatPostMessagePanel() {
	const meta = useSelect(
		( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
		[]
	);
	const { editPost } = useDispatch( 'core/editor' );

	if ( ! meta || ! ( META_KEY in meta ) ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="mintchat-post-message"
			title={ __( 'WhatsApp message', 'mintchat' ) }
			className="mintchat-post-message-panel"
		>
			<p className="mintchat-post-message-panel__description">
				{ __(
					'Overrides the contact default message for Mintchat buttons that use it on this page. Leave empty to use the contact default message.',
					'mintchat'
				) }
			</p>
			<TextareaControl
				label={ __( 'WhatsApp message', 'mintchat' ) }
				value={ meta[ META_KEY ] || '' }
				onChange={ ( value ) =>
					editPost( { meta: { [ META_KEY ]: value } } )
				}
				rows={ 3 }
				help={ __(
					'The text is added to the wa.me link. Phone numbers are managed in Settings > Mintchat.',
					'mintchat'
				) }
			/>
		</PluginDocumentSettingPanel>
	);
}
