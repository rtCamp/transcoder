# Transcoder for WordPress #
<p align="center">
 <img src="https://rtmedia.io/wp-content/uploads/2016/08/trancoder-banner-01.png" alt="Transcoder Banner"/>
</p>

Transcoder is an audio/video transcoding service for any WordPress website. Once this plugin is set up and a license key added, it will automatically intercept any uploaded audio/video files and convert them to a web-friendly format (mp3/mp4) via our Transcoder service. This eliminates the need for a dedicated media node - no fiddling with installation, managing dependencies or renting servers. 

Transcoder works for any WordPress website, including on shared hosting - just install this plugin, [subscribe to a plan](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder) and go! 

⚠ Transcoder only works with an active subscription plan. ⚠

This plugin is licensed under [GPL v2 or later](http://www.gnu.org/licenses/gpl-2.0.html).

#### Supported input media types: ####
* Audio: mp3, m4a, wav, ogg, wma
* Video: 3g2, 3gp, avi, flv, m4v, mp4, mpg, ogv, webm, wmv

#### Supported output media types: ####
* Audio: mp3
* Video: mp4

#### Highlighted Features ####
1. **Works with any WordPress website** - Transcoder plugs into your current website seamlessly, improving media compatibility.
2. **Thumbnail generation** - Automatically generate up to 10 thumbnails for videos.
3. **rtMedia integration** - Create the ultimate niche community that is accessible across all desktop and mobile devices by combining Transcoder with [rtMedia](https://wordpress.org/plugins/buddypress-media/).
4. **[rt_media] shortcode** - Use our shortcode to display transcoded audio/video file on any post or page. For example, [rt_media attachment_id=xx] the attachment_id parameter specifies the file to be displayed.

#### Privacy Warning ####
In order to transcode any media file, Transcoder has to run it through a dedicated server. After transcoding is completed, the media can reside on this server for a maximum of 24 hours, before it is permanently and irreversibly removed by a Cron job.

#### Project Roadmap ####
* Additional output formats for video- ogg, webm
* Downsampling capabilities for output video resolution
* RESTful API

## Screenshots
Transcoder Settings Page 

![Transcoder Settings Page](https://rtcamp.com/wp-content/uploads/2020/09/settings_transcoder-1-without-annotations.png "Transcoder Settings Page")

Media re-transcoding in progress 

![Media re-transcoding in progress](https://rtcamp.com/wp-content/uploads/2020/09/retranscoding-screenshot-complete-sh-720x259-1.png "Media re-transcoding in progress")

## Installation
1. Install the plugin from the 'Plugins' section in your WordPress dashboard (Go to `Plugins > Add New > Search` and search for "Transcoder"). 
1. Alternatively, you can download the plugin from [this repository](http://downloads.wordpress.org/plugin/transcoder.zip "Download Transcoder"), unzip and upload it to the plugins folder of your WordPress installation (`wp-content/plugins/`).
1. Activate Transcoder through the 'Plugins' section. 
1. Add your API key after subscribing to a plan either from the plugin’s interface or from [here](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder).
1. Click on 'Enable Transcoding' to start the transcoding service. From this point onward, all audio/video uploaded to your website is automatically transcoded. 

## FAQ
Visit Transcoder’s [FAQ page](https://rtmedia.io/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder#frequently-asked-questions "Visit FAQ page") or read the [project documentation](https://rtmedia.io/docs/transcoder/?utm_source=readme&utm_medium=plugin&utm_campaign=transcoder "Visit Transcoder's Documentation page")

## Changelog ##
#### 1.3.1 [August 14, 2020] ####

* ENHANCEMENTS

 * Update thumbnails after video is transcoded on BuddyPress’ activity and rtMedia’s media page.

* FIXED

 * Compatibility issues with WordPress 5.5
 * PHP Notices and Warnings
 * PHPCS issues

#### 1.3 [October 8, 2019] ####

* NEW FEATURES

 * Extend Video mime types, to facilitate selection and transcoding of Videos in AMP Stories.

#### 1.2.2 [July 12, 2018] ####

* FIXED

 * Issue with Transcoding service on load balancing server

#### 1.2.1 [June 26, 2018] ####
* ENHANCEMENTS

 * Updated usage of existing filter 'rtt_transcoder_status_message' [Documentation](https://rtmedia.io/docs/transcoder/developers-documentation/filters/#rtttranscoderstatusmessage)

* FIXED

 * Issue with Transcoding service usage update
 * Issue with Transcoding process callback

#### 1.2 [April 24, 2018] ####
* NEW FEATURES

 * Added feature to track real-time transcoding process status on BuddyPress activity, media library page and media single page for administrators
 * Added option in transcoder settings page to enable/disable tracking of real-time transcoding process status feature
 * Added filter to change text of check status button [Documentation](https://rtmedia.io/docs/transcoder/developers-documentation/filters/#rtttranscodercheckstatusbuttontext)
 * Added filter to manage transcoding process status messages [Documentation](https://rtmedia.io/docs/transcoder/developers-documentation/filters/#rtttranscoderstatusmessage)

* ENHANCEMENTS

 * Improved security of callback handler for transcoded media

* FIXED

 * PHP notices generated while activating product license key
 * Transcoding issue for MOV file formats
 * Transcoding issue for the files having QuickTime MIME type
 * Issue with transcoded video files getting swapped in BuddyPress activity

#### 1.1.2 [July 21, 2017] ####
* ENHANCEMENTS

 * Added: Filter to allow adding a custom filename for the transcoded files [Documentation](https://rtmedia.io/docs/transcoder/developers-documentation/filters/#transcodedtempfilename)
 * Replaced file_get_contents() with wp_remote_get() to have better server compatibility

#### 1.1.1 [Jan 10, 2017] ####
* FIXED

 * False positive result of localhost checking

#### 1.1 [Dec 27, 2016] ####
* NEW FEATURES

 * Retranscoding service to regenerate media thumbnails and retranscode media
 * Added option in settings to override the current video thumbnail after retranscoding

* ENHANCEMENTS

 * Added filters to disable the emails getting sent to users and administrators [Documentation](https://rtmedia.io/docs/transcoder/developers-documentation/filters/#rttsendnotification)
 * Added action before the transcoded thumbnails are stored
 * Added action before transcoded media is stored
 * Added action after callback response is processed

* FIXED

 * Improved the condition checking and fixed several bugs
 * Fixed transcoded media not getting deleted bug

#### 1.0.8 [Oct 05, 2016] ####
* Update the notice messages
* Fix broken media URL on multisite

#### 1.0.7 [Sep 27, 2016] ####
* Fix language directory path
* Fix localhost check bug
* Update URLs of multiple media present in single activity
* Fix waiting message for files sent to the transcoder
* Remove all the actions from function file and moved them to the actions file
* Add new action when video thumbnail is set for video
* Add thumbnail automatically for the videos uploaded from the rtmedia shortcode

#### 1.0.6 [Sep 12, 2016] ####
* Fix usage bar style issue
* Display notice message when user trie to activate the transcoding service on local host
* Add filters for transcoded media URLs
* Transcoded thumbnails for videos uploaded from rtMedia plugin will get stored in respective members upload folder

#### 1.0.5 [Sep 01, 2016] ####
* Fix backward compatibility for PHP v5.3
* Delete transcoded files when attachment is deleted
* Display notice message to subscribe the transcoding service

#### 1.0.4 [Aug 31, 2016] ####
* Add rtt_wp_parse_url function to parse URL to add backward compatibility
* Fix media is transcoding message bug

#### 1.0.3 [Aug 30, 2016] ####
* Remove warnings and notices related to the activity and media related pages
* Show default media thumbnail when poster attribute is empty

#### 1.0.2 [Aug 25, 2016] ####
* Show message in rtMedia buddypress activity when media is sent to the transcoder
* Remove notices and warnings
* Update transcoded audio file URL in rtMedia activity

#### 1.0.1 [Aug 24, 2016] ####
* Add backward compatibility

#### 1.0.0 ####
Initial release
* Transcoder 1.3.1, with WordPress 5.5 compatibility and media thumbnails auto update feature after transcoding is completed on rtMedia pages.

## BTW, We're Hiring!
<a href="https://rtcamp.com/?utm_source=github&utm_medium=readme" rel="nofollow"><img src="https://rtcamp.com/wp-content/uploads/2019/04/github-banner@2x.png" alt="Handcrafted Enterprise WordPress Solutions by rtCamp" /></a>
