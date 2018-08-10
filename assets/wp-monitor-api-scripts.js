(function ($) {
    $(document).ready(function () {
        app.init();
    });

    var app = {
        init: function () {
            this.generateToken();
        },

        generateToken: function () {
            if( $('#wp-monitor-api-generate-token-btn').length ){
                $('#wp-monitor-api-generate-token-btn').on('click', function (e) {
                    e.preventDefault();
                    $.ajax({
                        url: monitor.ajaxurl,
                        data: { action  : 'get_md5_hash' },
                        success: function (data) {
                            $('#wp_monitor_api_key').val(data);
                        },
                        error: function (error) {
                            console.error(error);
                        }
                    });
                })
            }

            if( $('#wp_monitor_api_key').length ){
                $('#wp_monitor_api_key').on('focus', function () {
                    this.select();
                })
            }
        }
    };
})(jQuery);