<?php

namespace App\Web;

class UA
{

    /**
     * bot user agent => perform link embed
     * @var string[]
     */
    private static $bots = [
        'TelegramBot' => false,
        'facebookexternalhit/' => false,
        'Facebot' => false,
        'curl/' => false,
        'wget/' => false,
        'WhatsApp/' => false,
        'Slack' => false,
        'Twitterbot/' => false,
        'discord' => true,
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 11.6; rv:92.0) Gecko/20100101 Firefox/92.0' => true
        // discord image bot
    ];

    /**
     * @param  string  $userAgent
     * @return bool
     */
    public static function isBot(string $userAgent): bool
    {
        foreach (self::$bots as $bot => $embedsLink) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $userAgent
     * @return false|string
     */
    public static function embedsLinks(string $userAgent)
    {
        foreach (self::$bots as $bot => $embedsLink) {
            if (stripos($userAgent, $bot) !== false) {
                return $embedsLink;
            }
        }

        return false;
    }
}
