jQuery && jQuery(function($) {
    var uploaderFrame, $this, $parent, userId;

    $('.wp-list-table').on('click', '.add-photo, .update-photo', function(e) {
        e.preventDefault();

        $this = $(this);
        $parent = $this.parent();
        userId = $this.data('user-id');

        if (!uploaderFrame) {
            uploaderFrame = wp.media.frames.file_frame = wp.media({
                title: 'Upload Wall Photo',
                button: {
                    text: 'Set Photo'
                },
                multiple: false
            });

            uploaderFrame.on('select', function() {
                $parent.addClass('loading');

                var attachment = uploaderFrame.state().get('selection').first().toJSON(),
                    aspectRatio = attachment.height / attachment.width;

                if (0.5625 !== aspectRatio) {
                    alert('Wall photos must have a 16:9 aspect ratio.'); // FIXME
                    return;
                }

                console.log(attachment);

                // Store attachment.id in user_meta
                var req = {
                    action: 'galahad_photo_wall_set_photo',
                    user_id: userId,
                    attachment_id: attachment.id
                };
                console.log('Sending: ', req);
                $.post(ajaxurl, req, function(res) {
                    $parent.removeClass('loading');

                    if (!res.success) {
                        alert('Error setting photo: ' + res.message);
                        return;
                    }

                    $parent.removeClass('needs-photo').addClass('has-photo');
                    $('#wall-photo-' + userId).html('<img src="' + res.data.thumbnail + '" class="wall-photo" />');
                });
            });
        }

        uploaderFrame.open();
    });

    $('.wp-list-table').on('click', '.add-photo, .update-photo', function(e) {

    });
});