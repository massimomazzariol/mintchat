/* Verify the installable archive after the standard WordPress packaging step. */
const assert = require( 'node:assert/strict' );
const AdmZip = require( 'adm-zip' );

const archive = new AdmZip( 'mintchat.zip' );
// npm-packlist always adds package.json; the installed plugin does not need it.
archive.deleteFile( 'mintchat/package.json' );
archive.deleteFile( 'mintchat/TESTING.md' );
archive.deleteFile( 'mintchat/README.md' );
const entries = archive.getEntries().map( ( entry ) => entry.entryName );
assert(
	entries.every(
		( name ) =>
			name.startsWith( 'mintchat/' ) &&
			! /(?:^|\/)(?:\.[^/]+|node_modules|tests|scripts|package[^/]*|eslint[^/]*)(?:\/|$)/.test(
				name
			)
	)
);
for ( const required of [
	'mintchat.php',
	'uninstall.php',
	'LICENSE',
	'readme.txt',
	'build/chat-button/block.json',
	'build/chat-button/index.js',
	'build/chat-button/index.asset.php',
	'build/chat-button/render.php',
	'build/chat-button/style-index.css',
] ) {
	assert( entries.includes( `mintchat/${ required }` ), required );
}
archive.writeZip( 'mintchat.zip' );
process.stdout.write( `Verified distribution: ${ entries.length } files.\n` );
