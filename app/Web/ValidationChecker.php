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
}
