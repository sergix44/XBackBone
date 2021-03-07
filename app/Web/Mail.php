<?php


namespace App\Web;

use InvalidArgumentException;

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
        $this->to = "<$mail>";
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

        $this->setHeaders();

        $this->headers .= $this->additionalHeaders;
        $message = html_entity_decode($this->message);

        if (self::$testing) {
            return 1;
        }

        return (int) mail($this->to, $this->subject, "<html><body>$message</body></html>", $this->headers);
    }
}
