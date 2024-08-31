<?php

namespace App\Models\Properties;

enum UserStatus: int
{
    case ENABLED = 1;
    case DISABLED = 0;
    case API_ONLY = 2;
    case SSO_ONLY = 3;
}
