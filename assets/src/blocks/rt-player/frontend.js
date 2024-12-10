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
		const videoSetupOptions = JSON.parse( playerEl.getAttribute( 'data-attributes' ) );

		const player = videojs( playerEl, {
			...videoSetupOptions,
			plugins: {
				qualityMenu: {}
			}
		});
	} );
}
