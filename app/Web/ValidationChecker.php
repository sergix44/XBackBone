<?php


namespace App\Web;

class ValidationChecker
{
    protected $rules = [];
    protected $failClosure;

    /**
     * @return ValidationChecker
     */
    public static function make()
    {
        return new self();
    }

    /**
     * @param  array  $rules
     * @return $this
     */
    public function rules(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * @param  callable  $closure
     * @return $this
     */
    public function onFail(callable $closure)
    {
        $this->failClosure = $closure;
        return $this;
    }

    /**
     * @return bool
     */
    public function fails()
    {
        foreach ($this->rules as $rule => $condition) {
            if (!$condition) {
                if (is_callable($this->failClosure)) {
                    ($this->failClosure)($rule);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function removeRule(string $key)
    {
        $condition = $this->rules[$key];

        unset($this->rules[$key]);

        return $condition;
    }

    /**
     * @param  string  $key
     * @param $condition
     * @return ValidationChecker
     */
    public function addRule(string $key, $condition)
    {
        $this->rules[$key] = $condition;
        return $this;
    }
}
