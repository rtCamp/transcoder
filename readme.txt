=== Transcoder ===
Contributors: rtcamp, mangeshp, chandrapatel, manishsongirkar36, bhargavbhandari90, kiranpotphode
Tags: media, multimedia, audio, songs, music, video, ffmpeg, media-node, rtMedia, WordPress, kaltura, transcode, transcoder, encoding, encode
Donate link: https://rtcamp.com/donate/
Requires at least: 4.1
Tested up to: 4.7
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Transcoding services for ANY WordPress website. Convert audio/video files of any format to a web-friendly format (mp3/mp4).

== Description ==
Transcoder easily converts all audio and video files uploaded to your website to a web-friendly format.

Transcoder eliminates the need for a dedicated media node- no fiddling with installation, managing dependancies or renting servers! Transcoder also works on shared hosting- just install, subscribe and go!

All transcoding services are available via a subscription plan through this plugin.
Subscribe to our free plan from the plugin's settings or from our [product page](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder). Note that Transcoder will not provide any services without a subscription plan.

= Supported input media types: =
* Audio: mp3, m4a, wav, ogg, wma
* Video: 3g2, 3gp, avi, flv, m4v, mp4, mpg, ogv, webm, wmv

= Supported output media types: =
* Audio: mp3
* Video: mp4

Create the ultimate niche community by combining Transcoder with our [rtMedia](https://wordpress.org/plugins/buddypress-media/) plugin. Transcoder works perfectly with rtMedia to create a social experience that is accessible across all desktop and mobile devices.

= Transcoder Features =
1. **Works with ANY WordPress website** - Transcoder plugs into your current website seamlessly, instantly improving user audio/video experience.
2. **rtMedia integration** - Works perfectly with our own [rtMedia](https://rtmedia.io/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder), for a complete social media platform.
3. **Thumbnail generation** - Automatically generate up to 10 thumbnails for every video, from which your users can choose one.
4. **[rt_media] shortcode** - Use our shortcode to display transcoded audio/video file on any post or page. For example, [rt_media attachment_id=xx] the attachment_id parameter specifies the file to be displayed.

= Privacy Warning =
In order for us to transcode your media files, we need to copy it over to our server.
After transcoding is completed, the media can reside on our server for a maximum of 24 hours, before it is permanently and irreversibly removed by a Cron job.

= Future Roadmap =
* Additional output formats for video- ogg, webm
* Downsampling capabilities for output video resolution
* RESTful API

= Important Links =
* [Project Homepage](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder "Visit Transcoder's Homepage")
* [Documentation](https://rtmedia.io/docs/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder "Visit Transcoder's Documentation page")
* [FAQ](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder#frequently-asked-questions "Visit FAQ page")
* [GitHub](https://github.com/rtCamp/transcoder/) - Please mention your wordpress.org username when sending pull requests.

== Installation ==
* Install the plugin from the 'Plugins' section in your dashboard (Go to `Plugins > Add New > Search` and search for "Transcoder").
* Alternatively, you can download the plugin from this [plugin directory](http://downloads.wordpress.org/plugin/transcoder.zip "Download Transcoder"). After downloading, unzip and upload it to the plugins folder of your WordPress installation (`wp-content/plugins/` directory of your WordPress installation).
* Activate it through the 'Plugins' section.

== Frequently Asked Questions ==
Please visit [FAQ page](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder#frequently-asked-questions "Visit FAQ page").
Read [Documentation](https://rtmedia.io/docs/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder "Visit Transcoder's Documentation page")

== Screenshots ==
1. Transcoder Settings

== Changelog ==
= 1.1.1 [Jan 10, 2017] =
* FIXED

 * False positive result of localhost checking

= 1.1 [Dec 27, 2016] =
* NEW FEATURES

 * Retranscoding service to regenerate media thumbnails and retranscode media
 * Added option in settings to override the current video thumbnail after retranscoding

* ENHANCEMENTS

 * Added filters to disable the emails getting sent to users and administrators
 * Added action before the transcoded thumbnails are stored
 * Added action before transcoded media is stored
 * Added action after callback response is processed

* FIXED

 * Improved the condition checking and fixed several bugs
 * Fixed transcoded media not getting deleted bug

= 1.0.8 [Oct 05, 2016] =
* Update the notice messages
* Fix broken media URL on multisite

= 1.0.7 [Sep 27, 2016] =
* Fix language directory path
* Fix localhost check bug
* Update URLs of multiple media present in single activity
* Fix waiting message for files sent to the transcoder
* Remove all the actions from function file and moved them to the actions file
* Add new action when video thumbnail is set for video
* Add thumbnail automatically for the videos uploaded from the rtmedia shortcode

= 1.0.6 [Sep 12, 2016] =
* Fix usage bar style issue
* Display notice message when user trie to activate the transcoding service on local host
* Add filters for transcoded media URLs
* Transcoded thumbnails for videos uploaded from rtMedia plugin will get stored in respective members upload folder

= 1.0.5 [Sep 01, 2016] =
* Fix backward compatibility for PHP v5.3
* Delete transcoded files when attachment is deleted
* Display notice message to subscribe the transcoding service

= 1.0.4 [Aug 31, 2016] =
* Add rtt_wp_parse_url function to parse URL to add backward compatibility
* Fix media is transcoding message bug

= 1.0.3 [Aug 30, 2016] =
* Remove warnings and notices related to the activity and media related pages
* Show default media thumbnail when poster attribute is empty

= 1.0.2 [Aug 25, 2016] =
* Show message in rtMedia buddypress activity when media is sent to the transcoder
* Remove notices and warnings
* Update transcoded audio file URL in rtMedia activity

= 1.0.1 [Aug 24, 2016] =
* Add backward compatibility

= 1.0.0 =
Initial release

== Upgrade Notice ==
= 1.1.1 =
Fix the false positive localhost checking bug.