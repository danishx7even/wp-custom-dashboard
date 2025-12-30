jQuery(document).ready(function($) {
            $('.mark-read-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this),
                    notif_id = btn.data('id');
                    
                $.ajax({
                    url: larkon_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'larkon_mark_notification_read',
                        notification_id: notif_id,
                        security: btn.data('nonce')
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#notif-row-' + notif_id).removeClass('row-unread').css('background', '#fff');
                            btn.parent().html('—');
                            $('#notif-row-' + notif_id + ' .status-cell').html('<span style="color:green;">Read</span>');
                            var badge = $('.lk-badge-count'),
                                newCount = parseInt(badge.text()) - 1;
                            if (newCount <= 0) badge.hide();
                            else badge.text(newCount);
                        }
                    }
                });
            });
        });