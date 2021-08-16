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
            },
            queuecomplete: function () {
                $('#uploadProgess').css({'width': '0%'}).text('');
            },
            success: function (file, response) {
                $(file.previewElement)
                    .find('.dz-filename')
                    .children()
                    .html('<a href="' + response.url + '">' + file.name + '</a>');
            },
            timeout: 0
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

        $('.bulk-selector').contextmenu(app.bulkSelect);
        $('#bulk-delete').click(app.bulkDelete);

        $('.tag-add').click(app.addTag);
        $('.tag-item').contextmenu(app.removeTag);


        $('.alert').not('.alert-permanent').fadeTo(10000, 500).slideUp(500, function () {
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
                if (value === null) {
                    opt.disabled = true;
                }
                $themes.append(opt);
            });
        });
        $themes.unbind('mousedown');
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
    },
    bulkSelect: function (e) {
        e.preventDefault();
        $(this).toggleClass('bg-light').toggleClass('text-danger').toggleClass('bulk-selected');
        var $bulkDelete = $('#bulk-delete');
        if ($bulkDelete.hasClass('disabled')) {
            $bulkDelete.removeClass('disabled');
        }
    },
    bulkDelete: function () {
        $('.bulk-selected').each(function (index, media) {
            $.post(window.AppConfig.base_url + '/upload/' + $(media).data('id') + '/delete', function () {
                $(media).fadeOut(200, function () {
                    $(this).remove();
                });
            });
        });
        $(this).addClass('disabled');
    },
    addTag: function (e) {
        var $caller = $(this);
        var $newAddTag = $caller.clone()
            .click(app.addTag)
            .appendTo($caller.parent());

        var tagInput = $(document.createElement('input'))
            .addClass('form-control form-control-verysm tag-input')
            .attr('data-id', $caller.data('id'))
            .attr('maxlength', 32)
            .css('width', '90px')
            .attr('onchange', 'this.value = this.value.toLowerCase();')
            .keydown(function (e) {
                if (e.keyCode === 13) { // enter -> save tag
                    app.saveTag.call($(this)); // change context
                    return false;
                }
                if (e.keyCode === 32) { // space -> save and add new tag
                    $newAddTag.click();
                    return false;
                }
            })
            .focusout(app.saveTag);

        $caller.off()
            .removeClass('badge-success badge-light')
            .html(tagInput)
            .children()
            .focus();
    },
    saveTag: function () {
        var tag = $(this).val();
        var mediaId = $(this).data('id');
        var $parent = $(this).parent();
        if (tag === '') {
            $parent.remove();
            return false;
        }
        $.ajax({
            type: 'POST',
            url: window.AppConfig.base_url + '/tag/add' + window.location.search,
            data: {'tag': tag, 'mediaId': mediaId},
            dataType: 'json',
            success: function (data) {
                if (!data.limitReached) {
                    $parent.replaceWith(
                        $(document.createElement('a'))
                            .addClass('badge badge-pill badge-light shadow-sm tag-item mr-1')
                            .attr('data-id', data.tagId)
                            .attr('data-media', mediaId)
                            .attr('href', data.href)
                            .contextmenu(app.removeTag)
                            .text(tag)
                    );
                } else {
                    $parent.remove();
                }
            }
        });
    },
    removeTag: function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $tag = $(this);

        $.post(window.AppConfig.base_url + '/tag/remove', {
            'tagId': $tag.data('id'),
            'mediaId': $tag.data('media')
        }, function (data) {
            $tag.remove();
            if (data.deleted) {
                $('#dropdown-tag-list > a[data-id="' + $tag.data('id') + '"]').remove();
            }
        });
    }
};

app.init();
$(document).ready(app.run);
