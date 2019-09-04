$(document).ready(function () {
    var $connection = $('#connection');
    var $all_database_options = $('.hook-database');
    var $all_database_inputs = $('.hook-database-input');

    var $storage_driver = $('#storage_driver');
    var $all_storage_inputs = $('.hook-storage-input');
    var $all_storage_options = $('.hook-storage');

    $connection.change(function () {
        $all_database_options.hide();
        $all_database_inputs.prop('required', '');
        switch ($(this).val()) {
            case 'sqlite':
                $('#dsn').val('resources/database/xbackbone.db');
                break;
            case 'mysql':
                $('#dsn').val('host=localhost;port=3306;dbname=xbackbone');
                $('#db_user').prop('required', 'required').parent().parent().show();
                $('#db_password').prop('required', 'required').parent().parent().show();
                break;
        }
    });

    $storage_driver.change(function () {
        $all_storage_options.hide();
        $all_storage_inputs.prop('required', '');
        switch ($(this).val()) {
            case 'local':
                $('#storage_path').val($('#storage_path').data('default-local')).prop('required', 'required').parent().parent().show();
                break;
            case 'ftp':
                $('#storage_path').val('/storage').prop('required', 'required').parent().parent().show();
                $('#storage_host').prop('required', 'required').parent().parent().show();
                $('#storage_username').prop('required', 'required').parent().parent().show();
                $('#storage_password').prop('required', 'required').parent().parent().show();
                $('#storage_port').prop('required', 'required').parent().parent().show();
                $('#storage_passive').prop('required', 'required').parent().parent().show();
                $('#storage_ssl').prop('required', 'required').parent().parent().show();
                break;
            case 's3':
                $('#storage_path').val('/storage').prop('required', 'required').parent().parent().show();
                $('#storage_key').prop('required', 'required').parent().parent().show();
                $('#storage_secret').prop('required', 'required').parent().parent().show();
                $('#storage_region').prop('required', 'required').parent().parent().show();
                $('#storage_bucket').prop('required', 'required').parent().parent().show();
                break;
            case 'google-cloud':
                $('#storage_project_id').prop('required', 'required').parent().parent().show();
                $('#storage_key_path').prop('required', 'required').parent().parent().show();
                $('#storage_bucket').prop('required', 'required').parent().parent().show();
                break;
            case 'dropbox':
                $('#storage_token').prop('required', 'required').parent().parent().show();
                break;
        }
    });

    $all_database_options.hide();
    $all_storage_options.hide();
    $storage_driver.trigger('change');
    $connection.trigger('change');
});