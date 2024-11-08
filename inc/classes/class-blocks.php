<?php
/**
 * Class to handle file system.
 *
 * @package transcoder
 */

namespace Transcoder\Inc;

use Transcoder\Inc\Traits\Singleton;

/**
 * Class Blocks
 */
class Blocks {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		$this->setup_hooks();
		$this->register_blocks();
	}

	/**
	 * Setup hooks.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all custom gutenberg blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {

		$transcoder_block_files = glob( TRANSCODER_BLOCK_SRC . '/**/index.php' );

		if ( ! empty( $transcoder_block_files ) && is_array( $transcoder_block_files ) ) {

			foreach ( $transcoder_block_files as $transcoder_block ) {

				require_once $transcoder_block;

			}
		}
	}
}
