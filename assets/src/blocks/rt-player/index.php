<?php
/**
 * RT Player Block.
 *
 * @package transcoder
 */

use Transcoder\Inc\Traits\Singleton;
use Transcoder\Inc\Assets;

/**
 * RT_Player
 */
class RT_Player {

	use Singleton;

	/**
	 * Block Slug.
	 *
	 * @var string
	 */
	const SLUG = 'rt-player';

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup action/filter hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets_handler' ) );
	}

	/**
	 * Enqueue scripts and styles for the block.
	 *
	 * @return void
	 */
	public function assets_handler() {
		// Only enqueue assets if the block is present on the page.
		if ( has_block( 'transcoder/' . self::SLUG ) ) {
			// Define the base path for local library assets.
			$lib_path = plugin_dir_url( __FILE__ ) . 'lib';

			// Enqueue Video.js CSS.
			wp_enqueue_style(
				'videojs-css',
				$lib_path . '/css/video-js.min.css',
				array(),
				'8.19.1'
			);

			// Enqueue Video.js core script.
			wp_enqueue_script(
				'videojs',
				$lib_path . '/js/video.min.js',
				array(),
				'8.19.1',
				true
			);

			// Enqueue DASH plugin for Video.js.
			wp_enqueue_script(
				'videojs-dash',
				$lib_path . '/js/videojs-dash.min.js',
				array( 'videojs' ),
				'5.1.1',
				true
			);

			// Enqueue Quality Levels plugin.
			wp_enqueue_script(
				'videojs-quality-levels',
				$lib_path . '/js/videojs-contrib-quality-levels.min.js',
				array( 'videojs' ),
				'4.1.0',
				true
			);

			// Enqueue Quality Menu plugin.
			wp_enqueue_script(
				'videojs-quality-menu',
				$lib_path . '/js/videojs-contrib-quality-menu.min.js',
				array( 'videojs', 'videojs-quality-levels' ),
				'1.0.3',
				true
			);
		}
	}

	/**
	 * Register block.
	 *
	 * @return void
	 */
	public function register_block() {
		Assets::get_instance()->register_style(
			self::SLUG . '-style',
			'assets/build/blocks/' . self::SLUG . '/style-index.css'
		);

		register_block_type(
			TRANSCODER_BLOCK_BUILD . '/' . self::SLUG
		);
	}
}

RT_Player::get_instance();
