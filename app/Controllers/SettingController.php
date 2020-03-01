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
     * @throws \Slim\Exception\HttpNotFoundException
     * @throws \Slim\Exception\HttpUnauthorizedException
     */
    public function saveSettings(Request $request, Response $response): Response
    {
        if (!preg_match('/[0-9]+[K|M|G|T]/i', param($request, 'default_user_quota', '1G'))) {
            $this->session->alert(lang('invalid_quota', 'danger'));
            return redirect($response, route('system'));
        }

        $this->updateSetting('register_enabled', param($request, 'register_enabled', 'off'));
        $this->updateSetting('hide_by_default', param($request, 'hide_by_default', 'off'));
        $this->updateSetting('quota_enabled', param($request, 'quota_enabled', 'off'));

        $user = $this->getUser($request, $this->session->get('user_id'));
        $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);

        $this->updateSetting('default_user_quota', stringToBytes(param($request, 'default_user_quota', '1G')));
        $this->updateSetting('copy_url_behavior', param($request, 'copy_url_behavior') === null ? 'default' : 'raw');

        $this->applyTheme($request);
        $this->applyLang($request);
        $this->updateSetting('custom_head', param($request, 'custom_head'));

        $this->session->alert(lang('settings_saved'));

        return redirect($response, route('system'));
    }

    /**
     * @param  Request  $request
     */
    public function applyLang(Request $request)
    {
        if (param($request, 'lang') !== 'auto') {
            $this->updateSetting('copy_url_behavior', param($request, 'lang'));
        } else {
            $this->database->query('DELETE FROM `settings` WHERE `key` = \'lang\'');
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

            // if is default, remove setting
            if (param($request, 'css') !== 'https://bootswatch.com/_vendor/bootstrap/dist/css/bootstrap.min.css') {
                $this->updateSetting('css', param($request, 'css'));
            } else {
                $this->database->query('DELETE FROM `settings` WHERE `key` = \'css\'');
            }
        }
    }

    /**
     * @param $key
     * @param  null  $value
     */
    private function updateSetting($key, $value = null)
    {
        if (!$this->database->query('SELECT `value` FROM `settings` WHERE `key` = '.$this->database->getPdo()->quote($key))->fetch()) {
            $this->database->query('INSERT INTO `settings`(`key`, `value`) VALUES ('.$this->database->getPdo()->quote($key).', ?)', $value);
        } else {
            $this->database->query('UPDATE `settings` SET `value`=? WHERE `key` = '.$this->database->getPdo()->quote($key), $value);
        }
    }
}
