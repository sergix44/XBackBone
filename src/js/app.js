var app = {
    run: function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        $('.user-delete').click(app.modalDelete);
        $('.media-delete').click(app.mediaDelete);
        $('.publish-toggle').click(app.publishToggle);
        $('.refresh-token').click(app.refreshToken);
        $('#themes').mousedown(app.loadThemes);

        $('.alert').fadeTo(4000, 500).slideUp(500, function () {
            $('.alert').slideUp(500);
        });

        new ClipboardJS('.btn-clipboard');

        console.log('Application is ready.');
    },
    modalDelete: function () {
        $('#modalDelete-link').attr('href', $(this).data('link'));
        $('#modalDelete').modal('show');
    },
    publishToggle: function () {
        var id = $(this).data('id');
        var $callerButton = $(this);
        if ($(this).data('published')) {
            $.post(window.AppConfig.base_url + '/upload/' + id + '/unpublish', function () {
                $callerButton
                    .data('published', false)
                    .tooltip('dispose')
                    .attr('title', 'Publish')
                    .tooltip()
                    .removeClass('btn-outline-warning')
                    .addClass('btn-outline-info')
                    .html('<i class="fas fa-check-circle"></i>');
                $('#published_' + id).html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>');
            });
        } else {
            $.post(window.AppConfig.base_url + '/upload/' + id + '/publish', function () {
                $callerButton
                    .data('published', true)
                    .tooltip('dispose')
                    .attr('title', 'Unpublish')
                    .tooltip()
                    .removeClass('btn-outline-info')
                    .addClass('btn-outline-warning')
                    .html('<i class="fas fa-times-circle"></i>');
                $('#published_' + id).html('<span class="badge badge-success"><i class="fas fa-check"></i></span>');
            });
        }
    },
    mediaDelete: function () {
        var id = $(this).data('id');
        var $callerButton = $(this);
        $.post(window.AppConfig.base_url + '/upload/' + id + '/delete', function () {
            $callerButton.tooltip('dispose');
            $('#media_' + id).fadeOut(200, function () {
                $(this).remove();
            });
        });
    },
    refreshToken: function () {
        var id = $(this).data('id');
        $.post(window.AppConfig.base_url + '/user/' + id + '/refreshToken', function (data) {
            $('#token').val(data);
        });
    },
    loadThemes: function (e) {
        e.preventDefault();
        var $themes = $('#themes');
        $.get(window.AppConfig.base_url + '/system/themes', function (data) {
            $themes.empty();
            Object.keys(data).forEach(function (key) {
                var opt = document.createElement('option');
                opt.value = data[key];
                opt.innerHTML = key;
                $themes.append(opt);
            });
            $('#themes-apply').prop('disabled', false);
        });
        $themes.unbind('mousedown');
    },
    telegramShare: function () {
        window.open($('#telegram-share-button').data('url') + $('#telegram-share-text').val(), '_blank');
    }
};

$(document).ready(app.run);
