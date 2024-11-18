(function($) {
    $(document).ready(function() {
        if (typeof wp === 'undefined' || typeof wp.Uploader === 'undefined') {
            console.error('wp.Uploader is not available.');
            return;
        }

        const progressBars = new Map();

        // Check for existing video files in the media library.
        setTimeout(checkExistingVideoProgress, 500);

        // Listen for new files added to the queue.
        wp.Uploader.queue.on('add', function(file) {
            wp.Uploader.queue.on('reset', function() {
                // Only show progress bar for video files.
                if (file.attributes.type !== 'video') return;

                attachmentId = file.id;
                initializeProgressBar(attachmentId);
            });
        });

        /**
         * Initialize progress bar for the given attachment.
         * 
         * @param {number} attachmentId
         */
        function initializeProgressBar(attachmentId) {
            const progressBar = $(
                `<div class="transcoder-progress-bar">
                    <div class="progress" style="width: 0%;">
                        <span class="progress-text">0%</span>
                    </div>
                </div>`
            );

            const mediaItemPreview = $(`.attachments .attachment[data-id="${attachmentId}"] .attachment-preview`);
            if (!mediaItemPreview.length) return;

            mediaItemPreview.append(progressBar);
            progressBars.set(attachmentId, progressBar);
            monitorProgress(attachmentId);
        }


        /**
         * Check for existing video files in the media.
         * 
         */
        function checkExistingVideoProgress() {
            const videoQuery = wp.media.query({
                type: 'video',
                posts_per_page: -1,
            });

            videoQuery.more().done(function() {
                const videoAttachments = videoQuery.models;
                videoAttachments.forEach(function(attachment) {
                    const attachmentId = attachment.id;
                    initializeProgressBar(attachmentId);
                })
            });
        }
    

        /**
         * Monitor the transcoding progress of the given attachment.
         * 
         * @param {number} attachmentId
         */
        function monitorProgress(attachmentId) {
            const progressBar = progressBars.get(attachmentId);
            if (!progressBar) return;

            $.ajax({
                url: `${transcoderSettings.restUrl}/${attachmentId}`,
                method: 'GET',
                beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', transcoderSettings.nonce),
                success: function(data) {
                    const progress = parseFloat(data.progress) || 0;
                    progressBar.find('.progress').css('width', `${progress}%`);
                    progressBar.find('.progress-text').text(`${progress}%`);

                    if (progress < 100) {
                        setTimeout(() => monitorProgress(attachmentId), 5000);
                    } else {
                        progressBar.remove();
                        progressBars.delete(attachmentId);
                    }
                },
                error: function() {
                    setTimeout(() => monitorProgress(attachmentId), 5000);
                }
            });
        }
    });
})(jQuery);