<?php


namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Web\Session;
use App\Web\ValidationHelper;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class AuthController extends Controller
{
    protected function checkRecaptcha(ValidationHelper $validator, Request $request)
    {
        $validator->callIf($this->getSetting('recaptcha_enabled') === 'on', function (Session $session) use (&$request) {
            $recaptcha = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$this->getSetting('recaptcha_secret_key').'&response='.param($request, 'recaptcha_token')));

            if ($recaptcha->success && $recaptcha->score < 0.5) {
                $session->alert(lang('recaptcha_failed'), 'danger');
                return false;
            }
            return true;
        });
        return $validator;
    }


    /**
     * @return bool|false|resource
     */
    public function ldapConnect()
    {
        if (!extension_loaded('ldap')) {
            $this->logger->error('The LDAP extension is not loaded.');
            return false;
        }
        // Building LDAP URI
        $ldapSchema=array_key_exists('schema', $this->config['ldap']) ?
        strtolower($this->config['ldap']['schema']) : 'ldap';
        $ldapURI="$ldapSchema://".$this->config['ldap']['host'].':'.$this->config['ldap']['port'];
        
        // Connecting to LDAP server
        $server = ldap_connect($ldapURI);
        if ($server) {
            ldap_set_option($server, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($server, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($server, LDAP_OPT_NETWORK_TIMEOUT, 10);
        }
        
        // Authenticating LDAP service account
        $serviceAccountFQDN= (array_key_exists('service_account_dn', $this->config['ldap'])) ? 
            $this->config['ldap']['service_account_dn'] : null;
        if (is_string($serviceAccountFQDN)) {
            
            if (ldap_bind($server,$serviceAccountFQDN,$this->config['ldap']['service_account_password']) === false) {
                $this->logger->error("Bind with service account ($serviceAccountFQDN) failed.");
                return false;
            }
        }

        return $server;
    }

    /**
     * @param  string  $username
     * @return string
     */
    protected function getLdapRdn(string $username)
    {
        $bindString = ($this->config['ldap']['rdn_attribute'] ?? 'uid=').addslashes($username);
        if ($this->config['ldap']['user_domain'] !== null) {
            $bindString .= ','.$this->config['ldap']['user_domain'];
        }

        if ($this->config['ldap']['base_domain'] !== null) {
            $bindString .= ','.$this->config['ldap']['base_domain'];
        }

        return $bindString;
    }
}
