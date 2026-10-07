const table = document.getElementById( 'mintchat-recipients' );
const add = document.getElementById( 'mintchat-add' );
let nextRow = 0;

add.addEventListener( 'click', () => {
	const row = document
		.getElementById( 'mintchat-row' )
		.content.querySelector( 'tr' )
		.cloneNode( true );
	const key = `new-${ nextRow++ }`;
	row.querySelectorAll( 'input, textarea' ).forEach( ( input ) => {
		input.name = input.name.replace( '__KEY__', key );
		if ( input.type === 'radio' ) {
			input.value = key;
			input.checked = ! table.querySelector(
				'input[type="radio"]:checked'
			);
		}
	} );
	table.append( row );
	row.querySelector( 'input' ).focus();
} );

table.addEventListener( 'click', ( event ) => {
	if ( ! event.target.closest( '.mintchat-remove' ) ) {
		return;
	}
	const row = event.target.closest( 'tr' );
	const focusTarget = row.nextElementSibling || row.previousElementSibling;
	row.remove();
	if ( ! table.querySelector( 'input[type="radio"]:checked' ) ) {
		const first = table.querySelector( 'input[type="radio"]' );
		if ( first ) {
			first.checked = true;
		}
	}
	( focusTarget?.querySelector( 'input' ) || add ).focus();
} );
