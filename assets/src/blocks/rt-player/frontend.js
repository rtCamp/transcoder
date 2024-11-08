/* global videojs */

// Adding an event listener for the 'DOMContentLoaded' event to ensure the script runs after the complete page is loaded.
document.addEventListener( 'DOMContentLoaded', () => rtPlayerFn() );

/**
 * RT Player
 *
 */
function rtPlayerFn() {
	const players = document.querySelectorAll( '.video-js' );
	players.forEach( ( playerEl ) => {
		const player = videojs( playerEl );

		// Initialize the quality menu.
		player.ready( () => {
			if ( typeof player.qualityMenu === 'function' ) {
				player.qualityMenu();
			} else {
				console.error( 'Quality Menu plugin is not available.' );
			}
		} );
	} );
}
