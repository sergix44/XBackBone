<?php

namespace App\Models\Properties;

enum ResourceType: string
{
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case AUDIO = 'AUDIO';
    case PDF = 'PDF';
    case FILE = 'FILE';
    case LINK = 'LINK';
}
