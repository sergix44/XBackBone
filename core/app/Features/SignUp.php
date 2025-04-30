<?php

namespace App\Features;

class SignUp
{
    public string $name = 'signup';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
