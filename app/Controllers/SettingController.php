<?php


namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SettingController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     */
    public function saveSettings(Request $request, Response $response): Response
    {
        $this->settingUpdate('register_enabled', param($request, 'register_enabled', 'off'));
        $this->settingUpdate('hide_by_default', param($request, 'hide_by_default', 'off'));
        $this->settingUpdate('copy_url_behavior', param($request, 'copy_url_behavior', 'off'));

        $this->applyTheme($request);
        $this->applyLang($request);
        $this->saveCustomHead($request);

        $this->session->alert(lang('settings_saved'));

        return redirect($response, route('system'));
    }

    /**
     * @param  Request  $request
     */
    public function applyLang(Request $request)
    {
        if (param($request, 'lang') !== 'auto') {
            if (!$this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'lang\'')->fetch()) {
                $this->database->query('INSERT INTO `settings`(`key`, `value`) VALUES (\'lang\', ?)', param($request, 'lang'));
            } else {
                $this->database->query('UPDATE `settings` SET `value`=? WHERE `key` = \'lang\'', param($request, 'lang'));
            }
        } else {
            $this->database->query('DELETE FROM `settings` WHERE `key` = \'lang\'');
        }
    }

    /**
     * @param  Request  $request
     */
    public function saveCustomHead(Request $request)
    {
        if ($request->getAttribute('custom_head_key_present')) {
            $this->database->query('UPDATE `settings` SET `value`=? WHERE `key` = \'custom_head\'', param($request, 'custom_head'));
        } else {
            $this->database->query('INSERT INTO `settings`(`key`, `value`) VALUES (\'custom_head\', ?)', param($request, 'custom_head'));
        }
    }


    /**
     * @param  Request  $request
     */
    public function applyTheme(Request $request)
    {
        if (param($request, 'css') !== null) {
            if (!is_writable(BASE_DIR.'static/bootstrap/css/bootstrap.min.css')) {
                $this->session->alert(lang('cannot_write_file'), 'danger');
            } else {
                file_put_contents(BASE_DIR.'static/bootstrap/css/bootstrap.min.css', file_get_contents(param($request, 'css')));
            }

            $this->settingUpdate('css', param($request, 'css'));
        }
    }

    /**
     * @param $key
     * @param  null  $value
     */
    private function settingUpdate($key, $value = null)
    {
        if (!$this->database->query('SELECT `value` FROM `settings` WHERE `key` = '.$this->database->getPdo()->quote($key))->fetch()) {
            $this->database->query('INSERT INTO `settings`(`key`, `value`) VALUES ('.$this->database->getPdo()->quote($key).', ?)', $value);
        } else {
            $this->database->query('UPDATE `settings` SET `value`=? WHERE `key` = '.$this->database->getPdo()->quote($key), $value);
        }
    }
}
