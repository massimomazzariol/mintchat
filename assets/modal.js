const modal = document.getElementById( 'mintchat-modal' );

if ( modal ) {
	const dialog = modal.querySelector( '.mintchat-modal__dialog' );
	const overlay = modal.querySelector( '.mintchat-modal__overlay' );
	const closeButton = modal.querySelector( '.mintchat-modal__close' );
	const map = modal.querySelector( '[data-mintchat-map]' );
	let returnFocus = null;

	const triggers = () =>
		document.querySelectorAll( '[data-mintchat-modal-open]' );
	const setExpanded = ( value ) =>
		triggers().forEach( ( trigger ) =>
			trigger.setAttribute( 'aria-expanded', value )
		);
	const focusable = () => [
		...dialog.querySelectorAll(
			'a[href], button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])'
		),
	];

	// Keys pressed inside the map iframe never reach this page, so Tab from the map
	// lands on this guard, which sends focus back to the start of the dialog.
	const guard = document.createElement( 'span' );
	guard.tabIndex = 0;
	guard.addEventListener( 'focus', () => closeButton.focus() );
	if ( map ) {
		dialog.append( guard );
	}

	const open = ( trigger ) => {
		returnFocus = trigger;
		modal.hidden = false;
		document.body.classList.add( 'mintchat-modal-open' );
		setExpanded( 'true' );
		if ( map && ! map.hasAttribute( 'src' ) ) {
			map.src = map.dataset.src;
		}
		closeButton.focus();
	};

	const close = () => {
		modal.hidden = true;
		document.body.classList.remove( 'mintchat-modal-open' );
		setExpanded( 'false' );
		returnFocus?.focus();
	};

	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-mintchat-modal-open]' );
		if ( trigger ) {
			event.preventDefault();
			open( trigger );
			return;
		}
		if (
			! modal.hidden &&
			( event.target === overlay ||
				event.target.closest( '.mintchat-modal__close' ) )
		) {
			close();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( modal.hidden ) {
			return;
		}
		if ( event.key === 'Escape' ) {
			close();
			return;
		}
		if ( event.key !== 'Tab' ) {
			return;
		}
		const items = focusable().filter( ( item ) => item !== guard );
		const first = items[ 0 ];
		const last = items[ items.length - 1 ];
		if ( event.shiftKey && dialog.ownerDocument.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if (
			! event.shiftKey &&
			dialog.ownerDocument.activeElement === last
		) {
			event.preventDefault();
			first.focus();
		}
	} );
}
