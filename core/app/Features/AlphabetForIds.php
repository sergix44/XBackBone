<?php

namespace App\Features;

use Illuminate\Support\Lottery;
use Illuminate\Support\Str;
use Sqids\Sqids;

class AlphabetForIds
{
    public string $name = 'id-alphabet';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return str_shuffle(Sqids::DEFAULT_ALPHABET);
    }
}
