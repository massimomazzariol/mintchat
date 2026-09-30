const config = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...config,
	{
		files: [ 'src/**/*.js', 'scripts/**/*.js' ],
		rules: {
			// Core supplies these packages at runtime; npm is only for development.
			'import/no-extraneous-dependencies': [ 'error', { devDependencies: true } ],
		},
	},
];
