/*global jQuery, galahadPhotoWallConfig*/

jQuery && jQuery(function($) {
    if (!galahadPhotoWallConfig) {
        console.error('No photo wall configuration.');
        return;
    }

    console.log('Photo wall plugin loaded.');
    $(function() {
        $(window).on('resize.zoomerang', function() {
            Zoomerang.config({
                bgColor: '#000',
                maxWidth: $(window).width() - 30,
                maxHeight: $(window).height() - 30,
                bgOpacity: 0.95,
                onClose: function(el) {
                    $(el).css('width', '100%').css('height', 'auto');
                }
            });
        }).trigger('resize.zoomerang');

        var req = {
            action: galahadPhotoWallConfig.action,
            with_photos: 1,
            page: 1
        };

        $.get(galahadPhotoWallConfig.endpoint, req, function(res) {
            // console.log('Response: ', res);
            if (!res.success) {
                console.error('Error querying photos.');
                return;
            }

            $outlet = $('#' + galahadPhotoWallConfig.outlet_id);
            $.each(res.data, function(idx, row) {
                $outlet.append('<div class="galahad-photo-wall-photo-container" id="member-' + parseInt(row.ID, 10) + '">' +
                    '<img src="' + galahadPhotoWallConfig.placeholder + '" data-src="' + row.photo + '" class="galahad-photo-wall-photo" />' +
                    '<h3 class="galahad-photo-wall-caption">' + row.display_name + '</h3>' +
                    '</div>');
            });

            $('.galahad-photo-wall-photo').unveil().click(function(e) {
                $img = $(this);
                $img.css('width', $img.width() + 'px').css('height', $img.height() + 'px');
                Zoomerang.open($img.get(0));
            });

            // Check for hash
            if (window.location.hash && "" !== window.location.hash) {
                $target = $(window.location.hash + ' img');
                if ($target.length) {
                    $target.trigger('click');
                }
            }
        });
    });
});