/* globals module: true, require: false */

const js      = require( '@eslint/js' );
const globals = require( 'globals' );

module.exports = [
	js.configs.recommended,
	{
		languageOptions: {
			// Match the legacy .eslintrc.json defaults: ES5 scripts, not ES modules.
			ecmaVersion: 5,
			sourceType: 'script',
			parserOptions: {
				ecmaFeatures: {
					impliedStrict: true
				}
			},
			globals: {
				...globals.browser,
				ajaxurl: 'readonly',
				Promise: 'readonly',
				jQuery: 'readonly',
				_: 'readonly',
				Chart: 'readonly',
				swal: 'readonly',
				imagify: 'writable',
				imagifyAdminBar: 'writable',
				imagifyBulk: 'writable',
				ImagifyGulp: 'readonly',
				imagifyLibrary: 'writable',
				imagifyNotices: 'writable',
				imagifyOptions: 'writable',
				imagifyPricingModal: 'writable',
				imagifySwal: 'writable',
				imagifyTTT: 'writable',
				imagifyFiles: 'writable'
			}
		},
		linterOptions: {
			// ESLint 8 did not report unused disable directives by default.
			reportUnusedDisableDirectives: 'off'
		},
		rules: {
			// Keep ESLint 8's `eslint:recommended` behaviour, which the newer preset changed.
			'no-extra-semi': 'error',
			'no-inner-declarations': 'error',
			'no-mixed-spaces-and-tabs': 'error',
			'no-unused-vars': [ 'error', { caughtErrors: 'none' } ],
			'no-useless-assignment': 'off',

			'consistent-this': 'error',
			curly: [ 'error', 'all' ],
			'dot-notation': [ 'error', { allowKeywords: true } ],
			'eol-last': 'error',
			eqeqeq: 'error',
			'for-direction': 'error',
			indent: [ 'error', 'tab', { SwitchCase: 1 } ],
			'key-spacing': [
				'error',
				{
					align: {
						beforeColon: false,
						afterColon: true,
						on: 'value',
						mode: 'minimum'
					},
					multiLine: {
						beforeColon: false,
						afterColon: true
					}
				}
			],
			'keyword-spacing': 'error',
			'linebreak-style': [ 'error', 'unix' ],
			'new-cap': 'error',
			'new-parens': 'error',
			'no-alert': 'error',
			'no-array-constructor': 'error',
			'no-caller': 'error',
			'no-catch-shadow': 'error',
			'no-eval': 'error',
			'no-extend-native': 'error',
			'no-extra-bind': 'error',
			'no-extra-parens': [ 'error', 'functions' ],
			'no-implied-eval': 'error',
			'no-iterator': 'error',
			'no-label-var': 'error',
			'no-labels': 'error',
			'no-lone-blocks': 'error',
			'no-loop-func': 'error',
			'no-multi-str': 'error',
			'no-native-reassign': 'error',
			'no-new': 'error',
			'no-new-func': 'error',
			'no-new-object': 'error',
			'no-new-wrappers': 'error',
			'no-octal-escape': 'error',
			'no-process-exit': 'error',
			'no-proto': 'error',
			'no-return-assign': 'error',
			'no-script-url': 'error',
			'no-sequences': 'error',
			'no-shadow': [
				'error',
				{
					builtinGlobals: true,
					allow: [ '$this', 'name' ]
				}
			],
			'no-shadow-restricted-names': 'error',
			'no-spaced-func': 'error',
			'no-trailing-spaces': 'error',
			'no-undef-init': 'error',
			'no-underscore-dangle': 'error',
			'no-unused-expressions': 'error',
			'no-use-before-define': 'error',
			'no-with': 'error',
			semi: 'error',
			'semi-spacing': [ 'error', { before: false, after: true } ],
			'space-infix-ops': 'error',
			'space-unary-ops': [
				'error',
				{
					words: true,
					nonwords: true,
					overrides: {
						'++': false,
						'--': false,
						'-': false
					}
				}
			],
			strict: [ 'error', 'function' ]
		}
	},
	{
		files: [ 'src/lib/**/*.js', 'Tests/js/**/*.js' ],
		languageOptions: {
			ecmaVersion: 2020,
			sourceType: 'module',
			globals: {
				...globals.browser,
				...globals.node,
				...globals.jest,
				...globals.es2020
			}
		}
	}
];
