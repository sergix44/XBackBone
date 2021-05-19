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
        $ldapSchema=(@is_string(['ldap']['schema'])) ?
            strtolower($this->config['ldap']['schema']) : 'ldap';
        $ldapURI="$ldapSchema://".$this->config['ldap']['host'].':'.$this->config['ldap']['port'];
        
        // Connecting to LDAP server
        $server = ldap_connect($ldapURI);
        if ($server) {
            ldap_set_option($server, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($server, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($server, LDAP_OPT_NETWORK_TIMEOUT, 10);
        }
        
        // Upgrade to StartTLS
        if ($this->config['ldap']['useStartTLS'] === true) {
            if (ldap_start_tls($server) === false) { 
                $this->logger-error("Failed to establish secure LDAP swith StartTLS");
                return false;
            }
        }
        
        // Authenticating LDAP service account (if configured)
        $serviceAccountFQDN= (@is_string($this->config['ldap']['service_account_dn'])) ? 
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
     * Returns User's LDAP DN
     * @param  string  $username
     * @package resource $server LDAP Server Resource 
     * @return string|null
     */
    protected function getLdapRdn(string $username, $server)
    {
        //Dynamic LDAP User Binding
        if (@is_string($this->config['ldap']['search_filter'])) {
            //Replace ???? with username
            $searchFilter = str_replace('????', ldap_escape($username,null,LDAP_ESCAPE_FILTER), $this->config['ldap']['search_filter']);
            $ldapAddributes = array ('dn');
            $ldapSearchResp = ldap_search(
                $server, 
                $this->config['ldap']['base_domain'], 
                $searchFilter,
                $ldapAddributes
            );
            if (ldap_count_entries($server, $ldapSearchResp) !== 1 ) {
                $this->logger->warn("LDAP search for $username not found or had multiple entries");
                return null;
            }
            $ldapEntry = ldap_first_entry($server, $$ldapSearchResp);
            //Returns full DN
            $bindString = ldap_get_dn($server, $ldapEntry);
            
            
        } else {
            // Static LDAP Binding
            $bindString = ($this->config['ldap']['rdn_attribute'] ?? 'uid=').addslashes($username);
            if ($this->config['ldap']['user_domain'] !== null) {
                $bindString .= ','.$this->config['ldap']['user_domain'];
            }
            
            if ($this->config['ldap']['base_domain'] !== null) {
                $bindString .= ','.$this->config['ldap']['base_domain'];
            }
            //returns partial DN
        }
        
        return $bindString;
    }
}
