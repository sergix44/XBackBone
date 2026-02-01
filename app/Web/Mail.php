<?php


namespace App\Web;

use InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mail
{
    /**
     * @var bool
     */
    private static $testing = false;

    protected $fromMail = 'no-reply@example.com';
    protected $fromName;

    protected $to;

    protected $subject;
    protected $message;

    protected $additionalHeaders = '';
    protected $headers = '';

    /**
     * @return Mail
     */
    public static function make()
    {
        return new self();
    }

    /**
     * This will skip the email send
     */
    public static function fake()
    {
        self::$testing = true;
    }

    /**
     * @param $mail
     * @param $name
     * @return $this
     */
    public function from(string $mail, string $name)
    {
        $this->fromMail = $mail;
        $this->fromName = $name;
        return $this;
    }

    /**
     * @param $mail
     * @return $this
     */
    public function to(string $mail)
    {
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Mail not valid.');
        }
        $this->to = $mail;
        return $this;
    }


    /**
     * @param $text
     * @return $this
     */
    public function subject(string $text)
    {
        $this->subject = $text;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     */
    public function message(string $text)
    {
        $this->message = $text;
        return $this;
    }

    /**
     * @param $header
     * @return $this
     */
    public function addHeader(string $header)
    {
        $this->additionalHeaders .= "$header\r\n";
        return $this;
    }

    /**
     * @param $header
     * @return $this
     */
    protected function addRequiredHeader(string $header)
    {
        $this->headers .= "$header\r\n";
        return $this;
    }

    /**
     * Set headers before send
     */
    protected function setHeaders()
    {
        if ($this->fromName === null) {
            $this->addRequiredHeader("From: $this->fromMail");
        } else {
            $this->addRequiredHeader("From: $this->fromName <$this->fromMail>");
        }

        $this->addRequiredHeader('X-Mailer: PHP/'.phpversion())
            ->addRequiredHeader('MIME-Version: 1.0')
            ->addRequiredHeader('Content-Type: text/html; charset=utf-8');
    }

    /**
     * @return int
     */
    public function send()
    {
        if ($this->to === null) {
            throw new InvalidArgumentException('Target email cannot be null.');
        }

        if ($this->subject === null) {
            throw new InvalidArgumentException('Subject cannot be null.');
        }

        if ($this->message === null) {
            throw new InvalidArgumentException('Message cannot be null.');
        }

        if (self::$testing) {
            return 1;
        }

        $config = resolve('config');
        $mailConfig = $config['mail'] ?? [];
        $driver = $mailConfig['driver'] ?? 'mail';

        if ($driver === 'smtp') {
            return $this->sendViaSMTP($mailConfig);
        }

        return $this->sendViaMail();
    }

    /**
     * Send email using SMTP via PHPMailer
     *
     * @param array $config
     * @return int
     */
    protected function sendViaSMTP(array $config)
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'] ?? '';
            $mail->Port = $config['port'] ?? 587;

            // Authentication
            if (!empty($config['username'])) {
                $mail->SMTPAuth = true;
                $mail->Username = $config['username'];
                $mail->Password = $config['password'] ?? '';
            }

            // Encryption
            $encryption = $config['encryption'] ?? 'tls';
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            // Sender
            $fromMail = !empty($config['from']) ? $config['from'] : $this->fromMail;
            $fromName = !empty($config['from_name']) ? $config['from_name'] : ($this->fromName ?? '');
            $mail->setFrom($fromMail, $fromName);

            // Recipient
            $mail->addAddress($this->to);

            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $this->subject;
            $mail->Body = '<html><body>'.html_entity_decode($this->message).'</body></html>';
            $mail->AltBody = strip_tags(html_entity_decode($this->message));

            $mail->send();
            return 1;
        } catch (PHPMailerException $e) {
            error_log('Mail sending failed: '.$mail->ErrorInfo);
            return 0;
        }
    }

    /**
     * Send email using PHP's mail() function (legacy method)
     *
     * @return int
     */
    protected function sendViaMail()
    {
        $this->setHeaders();
        $this->headers .= $this->additionalHeaders;
        $message = html_entity_decode($this->message);

        return (int) mail("<{$this->to}>", $this->subject, "<html><body>$message</body></html>", $this->headers);
    }
}
