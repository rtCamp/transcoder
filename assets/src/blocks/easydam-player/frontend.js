import videojs from "video.js";
import "video.js/dist/video-js.css";
// import 'videojs-contrib-quality-levels';
import 'videojs-hls-quality-selector';

// Adding an event listener for the 'DOMContentLoaded' event to ensure the script runs after the complete page is loaded.
document.addEventListener( 'DOMContentLoaded', () => easyDAMPlayer() );

/**
 * RT Player
 *
 */
function easyDAMPlayer() {

	const videos = document.querySelectorAll(".easydam-player.video-js");

	videos.forEach(video => {
		// read the data-setup attribute
		const videoSetupOptions = video.dataset.setup ? JSON.parse(video.dataset.setup) : {
			controls: true,
			autoplay: false,
			preload: 'auto',
			fluid: true
		};

		console.log('videoOptions', videoSetupOptions);
		

		const player = videojs(video, videoSetupOptions);

		const qualityLevels = player.qualityLevels();
		
		player.hlsQualitySelector({
			vjsIconClass:'vjs-icon-cog',
		});
	});
}
