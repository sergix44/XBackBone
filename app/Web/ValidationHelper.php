<?php


namespace App\Web;

class ValidationHelper
{
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var bool
     */
    protected $failed;

    /**
     * Validator constructor.
     * @param  Session  $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;

        $this->failed = false;
    }

    public function alertIf(bool $condition, string $alert, string $type = 'danger')
    {
        if (!$this->failed && $condition) {
            $this->failed = true;
            $this->session->alert(lang($alert), $type);
        }

        return $this;
    }

    public function failIf(bool $condition)
    {
        if (!$this->failed && $condition) {
            $this->failed = true;
        }

        return $this;
    }

    public function callIf(bool $condition, callable $closure)
    {
        if (!$this->failed && $condition) {
            do {
                $result = $closure($this->session);
                if (is_callable($result)) {
                    $closure = $result;
                }
            } while (!is_bool($result));
            $this->failed = !$result;
        }

        return $this;
    }

    public function fails()
    {
        return $this->failed;
    }
}
