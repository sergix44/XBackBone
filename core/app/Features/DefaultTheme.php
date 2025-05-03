<?php

namespace App\Features;

use Illuminate\Support\Lottery;

class DefaultTheme
{
    public string $name = 'default-theme';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return '';
    }
}
