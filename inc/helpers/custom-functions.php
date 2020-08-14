<?php
/**
 * Custom functions.
 *
 * @package transcoder
 */

/**
 * This method is an improved version of PHP's filter_input() and
 * works well on PHP CLI as well which PHP default method does not.
 * Also Provide support INPUT_REQUEST.
 *
 * Reference:
 * - https://bugs.php.net/bug.php?id=49184
 * - https://bugs.php.net/bug.php?id=54672
 *
 * @param int    $type          One of INPUT_GET, INPUT_POST, INPUT_REQUEST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
 * @param string $variable_name Name of a variable to get.
 * @param int    $filter        The ID of the filter to apply.
 * @param mixed  $options       filter to apply.
 *
 * @return mixed Value of the requested variable on success, FALSE if the filter fails, or NULL if the
 *               variable_name variable is not set.
 */
function transcoder_filter_input( $type, $variable_name, $filter = FILTER_DEFAULT, $options = null ) {

	/**
	 * Provide support of INPUT_REQUEST
	 *
	 * Reference: https://bugs.php.net/bug.php?id=54672
	 */
	if ( INPUT_REQUEST === $type ) {
		if ( isset( $_POST[ $variable_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$type = INPUT_POST;
		} elseif ( isset( $_GET[ $variable_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$type = INPUT_GET;
		} else {
			return null;
		}
	}

	if ( php_sapi_name() !== 'cli' ) {

		/**
		 * We can not have code coverage since.
		 * Since this will only execute when sapi is "fpm-fcgi".
		 * While Unit test case run on "cli"
		 */
		// @codeCoverageIgnoreStart

		$sanitized_variable = filter_input( $type, $variable_name, $filter, $options );

		/**
		 * Code is not running on PHP Cli and we are in clear.
		 * Use the PHP method and bail out.
		 */
		if ( ! empty( $sanitized_variable ) && FILTER_SANITIZE_STRING === $filter ) {
			$sanitized_variable = sanitize_text_field( $sanitized_variable );
		}

		return $sanitized_variable;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Code is running on PHP Cli and INPUT_SERVER returns NULL
	 * even for set vars when run on Cli
	 * See: https://bugs.php.net/bug.php?id=49184
	 *
	 * This is a workaround for that bug till its resolved in PHP binary
	 * which doesn't look to be anytime soon. This is a friggin' 10 year old bug.
	 */

	$input = '';

	$allowed_html_tags = wp_kses_allowed_html( 'post' );

	/**
	 * Marking the switch() block below to be ignored by PHPCS
	 * because PHPCS squawks on using superglobals like $_POST or $_GET
	 * directly but it can't be helped in this case as this code
	 * is running on Cli.
	 */

	// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

	switch ( $type ) {

		case INPUT_GET:
			if ( ! isset( $_GET[ $variable_name ] ) ) {
				return null;
			}

			$input = wp_kses( $_GET[ $variable_name ], $allowed_html_tags );
			break;

		case INPUT_POST:
			if ( ! isset( $_POST[ $variable_name ] ) ) {
				return null;
			}

			$input = wp_kses( $_POST[ $variable_name ], $allowed_html_tags );
			break;

		case INPUT_COOKIE:
			if ( ! isset( $_COOKIE[ $variable_name ] ) ) {
				return null;
			}

			$input = wp_kses( $_COOKIE[ $variable_name ], $allowed_html_tags );
			break;

		case INPUT_SERVER:
			if ( ! isset( $_SERVER[ $variable_name ] ) ) {
				return null;
			}

			$input = wp_kses( $_SERVER[ $variable_name ], $allowed_html_tags );
			break;

		case INPUT_ENV:
			if ( ! isset( $_ENV[ $variable_name ] ) ) {
				return null;
			}

			$input = wp_kses( $_ENV[ $variable_name ], $allowed_html_tags );
			break;

		default:
			return null;

	}

	// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

	return filter_var( $input, $filter );

}
