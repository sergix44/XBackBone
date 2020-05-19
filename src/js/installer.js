$(document).ready(function () {
    var $connection = $('#connection');
    var $allDatabaseOptions = $('.hook-database');
    var $allDatabaseInputs = $('.hook-database-input');
    var sqliteDSN = $('#dsn').val();

    var $storageDriver = $('#storage_driver');
    var $allStorageInputs = $('.hook-storage-input');
    var $allStorageOptions = $('.hook-storage');

    $connection.change(function () {
        $allDatabaseOptions.hide();
        $allDatabaseInputs.prop('required', '').prop('disabled', 'disabled');
        switch ($(this).val()) {
            case 'sqlite':
                $('#dsn').val(sqliteDSN);
                break;
            case 'mysql':
                $('#dsn').val('host=localhost;port=3306;dbname=xbackbone');
                $('#db_user').prop('disabled', '').prop('required', 'required').parent().parent().show();
                $('#db_password').prop('disabled', '').prop('required', 'required').parent().parent().show();
                break;
        }
    });

    $storageDriver.change(function () {
        $allStorageOptions.hide();
        $allStorageInputs.prop('required', '');
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
                $("#storage_endpoint").parent().parent().show();
                $('#storage_bucket').prop('required', 'required').parent().parent().show();
                break;
            case 'google-cloud':
                $('#storage_project_id').prop('required', 'required').parent().parent().show();
                $('#storage_key_path').prop('required', 'required').parent().parent().show();
                $('#storage_bucket').prop('required', 'required').parent().parent().show();
                break;
            case 'azure':
                $('#storage_account_name').prop('required', 'required').parent().parent().show();
                $('#storage_account_key').prop('required', 'required').parent().parent().show();
                $('#storage_container_name').prop('required', 'required').parent().parent().show();
                break;
            case 'dropbox':
                $('#storage_token').prop('required', 'required').parent().parent().show();
                break;
        }
    });

    $allDatabaseOptions.hide();
    $allStorageOptions.hide();
    $storageDriver.trigger('change');
    $connection.trigger('change');
});