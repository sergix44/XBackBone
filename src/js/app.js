var app = {
    init: function () {
        Dropzone.options.uploadDropzone = {
            paramName: 'upload',
            maxFilesize: window.AppConfig.max_upload_size / Math.pow(1024, 2), // MB
            dictDefaultMessage: window.AppConfig.lang.dropzone,
            error: function (file, response) {
                this.defaultOptions.error(file, response.message);
            },
            totaluploadprogress: function (uploadProgress) {
                var text = Math.round(uploadProgress) + '%';
                $('#uploadProgess').css({'width': text}).text(text);
            }
        };
    },
    run: function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        $('.user-delete').click(app.modalDelete);
        $('.public-delete').click(app.modalDelete);
        $('.media-delete').click(app.mediaDelete);
        $('.publish-toggle').click(app.publishToggle);
        $('.refresh-token').click(app.refreshToken);
        $('#themes').mousedown(app.loadThemes);
        $('.checkForUpdatesButton').click(app.checkForUpdates);

        $('.alert').fadeTo(10000, 500).slideUp(500, function () {
            $('.alert').slideUp(500);
        });

        new ClipboardJS('.btn-clipboard');
        new Plyr($('#player'), {ratio: '16:9'});

        $('.footer').fadeIn(600);

        console.log('Application is ready.');
    },
    modalDelete: function () {
        $('#modalDelete-link').attr('href', $(this).data('link'));
        $('#modalDelete').modal('show');
    },
    publishToggle: function () {
        var id = $(this).data('id');
        var $callerButton = $(this);
        var isOutline = false;
        if ($(this).data('published')) {
            isOutline = $callerButton.hasClass('btn-outline-warning');
            $.post(window.AppConfig.base_url + '/upload/' + id + '/unpublish', function () {
                $callerButton
                    .data('published', false)
                    .tooltip('dispose')
                    .attr('title', window.AppConfig.lang.publish)
                    .tooltip()
                    .removeClass(isOutline ? 'btn-outline-warning' : 'btn-warning')
                    .addClass(isOutline ? 'btn-outline-info' : 'btn-info')
                    .html('<i class="fas fa-check-circle"></i>');
                $('#published_' + id).html('<span class="badge badge-danger"><i class="fas fa-times"></i></span>');
            });
        } else {
            isOutline = $callerButton.hasClass('btn-outline-info');
            $.post(window.AppConfig.base_url + '/upload/' + id + '/publish', function () {
                $callerButton
                    .data('published', true)
                    .tooltip('dispose')
                    .attr('title', window.AppConfig.lang.hide)
                    .tooltip()
                    .removeClass(isOutline ? 'btn-outline-info' : 'btn-info')
                    .addClass(isOutline ? 'btn-outline-warning' : 'btn-warning')
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
            $.each(data, function (key, value) {
                var opt = document.createElement('option');
                opt.value = value;
                opt.innerHTML = key;
                $themes.append(opt);
            });
        });
        $themes.unbind('mousedown');
    },
    telegramShare: function () {
        window.open($('#telegram-share-button').data('url') + $('#telegram-share-text').val(), '_blank');
    },
    checkForUpdates: function () {
        $('#checkForUpdatesMessage').empty().html('<i class="fas fa-spinner fa-pulse fa-3x"></i>');
        $('#doUpgradeButton').prop('disabled', true);
        $.get(window.AppConfig.base_url + '/system/checkForUpdates?prerelease=' + $(this).data('prerelease'), function (data) {
            $('#checkForUpdatesMessage').empty().text(data.message);
            if (data.upgrade) {
                $('#doUpgradeButton').prop('disabled', false);
            } else {
                $('#doUpgradeButton').prop('disabled', true);
            }
        });
    }
};

app.init();
$(document).ready(app.run);
