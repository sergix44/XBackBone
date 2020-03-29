<?php


namespace App\Web;

class ValidationChecker
{
    protected $rules = [];
    protected $failClosure;
    protected $lastRule;

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
                $this->lastRule = $rule;
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
     * @return ValidationChecker
     */
    public function removeRule(string $key)
    {
        $this->rules[$key];

        unset($this->rules[$key]);

        return $this;
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

    /**
     * @return mixed
     */
    public function getLastRule()
    {
        return $this->lastRule;
    }
}
