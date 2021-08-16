<?php


namespace App\Controllers;

use App\Database\Repositories\UserRepository;
use App\Web\Theme;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

class SettingController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     * @throws HttpInternalServerErrorException
     */
    public function saveSettings(Request $request, Response $response): Response
    {
        if (!preg_match('/[0-9]+[K|M|G|T]/i', param($request, 'default_user_quota', '1G'))) {
            $this->session->alert(lang('invalid_quota', 'danger'));
            return redirect($response, route('system'));
        }

        if (param($request, 'recaptcha_enabled', 'off') === 'on' && (empty(param($request, 'recaptcha_site_key')) || empty(param($request, 'recaptcha_secret_key')))) {
            $this->session->alert(lang('recaptcha_keys_required', 'danger'));
            return redirect($response, route('system'));
        }

        // registrations
        $this->updateSetting('register_enabled', param($request, 'register_enabled', 'off'));
        $this->updateSetting('auto_tagging', param($request, 'auto_tagging', 'off'));

        // quota
        $this->updateSetting('quota_enabled', param($request, 'quota_enabled', 'off'));
        $this->updateSetting('default_user_quota', stringToBytes(param($request, 'default_user_quota', '1G')));
        $user = make(UserRepository::class)->get($request, $this->session->get('user_id'));
        $this->setSessionQuotaInfo($user->current_disk_quota, $user->max_disk_quota);

        $this->updateSetting('custom_head', param($request, 'custom_head'));
        $this->updateSetting('recaptcha_enabled', param($request, 'recaptcha_enabled', 'off'));
        $this->updateSetting('recaptcha_site_key', param($request, 'recaptcha_site_key'));
        $this->updateSetting('recaptcha_secret_key', param($request, 'recaptcha_secret_key'));
        $this->updateSetting('image_embeds', param($request, 'image_embeds'));

        $this->applyTheme($request);
        $this->applyLang($request);

        $this->logger->info("User $user->username updated the system settings.");
        $this->session->alert(lang('settings_saved'));

        return redirect($response, route('system'));
    }

    /**
     * @param  Request  $request
     */
    public function applyLang(Request $request)
    {
        if (param($request, 'lang') !== 'auto') {
            $this->updateSetting('lang', param($request, 'lang'));
        } else {
            $this->database->query('DELETE FROM `settings` WHERE `key` = \'lang\'');
        }
    }

    /**
     * @param  Request  $request
     * @throws HttpInternalServerErrorException
     */
    public function applyTheme(Request $request)
    {
        $css = param($request, 'css');
        if ($css === null) {
            return;
        }

        if (!is_writable(BASE_DIR.'static/bootstrap/css/bootstrap.min.css')) {
            $this->session->alert(lang('cannot_write_file'), 'danger');
            throw new HttpInternalServerErrorException($request);
        }

        make(Theme::class)->applyTheme($css);

        // if is default, remove setting
        if ($css !== Theme::default()) {
            $this->updateSetting('css', $css);
        } else {
            $this->database->query('DELETE FROM `settings` WHERE `key` = \'css\'');
        }
    }

    /**
     * @param $key
     * @param  null  $value
     */
    private function updateSetting($key, $value = null)
    {
        if (!$this->database->query('SELECT `value` FROM `settings` WHERE `key` = '.$this->database->getPdo()->quote($key))->fetch()) {
            $this->database->query(
                'INSERT INTO `settings`(`key`, `value`) VALUES ('.$this->database->getPdo()->quote($key).', ?)',
                $value
            );
        } else {
            $this->database->query(
                'UPDATE `settings` SET `value`=? WHERE `key` = '.$this->database->getPdo()->quote($key),
                $value
            );
        }
    }
}
