<?php
/**
 * Handle progress calculation and display of progress bar.
 *
 * @package    rtMediaTranscoder
 * @subpackage rtMediaTranscoder/Admin
 */

/**
 * Handle progress calculation and display of progress bar.
 *
 * @package    rtMediaTranscoder
 * @subpackage rtMediaTranscoder/Admin
 */
class RT_Progress {

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

	}

	/**
	 * Show progress_ui.
	 *
	 * @access public
	 *
	 * @param  float $progress	Progress value.
	 * @param  bool  $echo		If true then echo the output else return.
	 *
	 * @return string $progress_ui	Output of progress bar.
	 */
	public function progress_ui( $progress, $echo = true ) {
		$progress_ui = '
			<div id="rtprogressbar">
				<div style="width:' . esc_attr( $progress ) . '%"></div>
			</div>
			';

		if ( $echo ) {
			echo $progress_ui; // @codingStandardsIgnoreLine
		} else {
			return $progress_ui;
		}
	}

	/**
	 * Calculate progress %.
	 *
	 * @access public
	 *
	 * @param  float $progress	Progress value.
	 * @param  float $total		Total value.
	 *
	 * @return float
	 */
	public function progress( $progress, $total ) {
		if ( $total < 1 ) {
			return 100;
		}

		return ( $progress / $total ) * 100;
	}
}
