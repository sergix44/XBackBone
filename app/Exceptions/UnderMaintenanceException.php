<?php

namespace App\Exceptions;

use Slim\Exception\HttpSpecializedException;

class UnderMaintenanceException extends HttpSpecializedException
{
    protected $code = 503;
    protected $message = 'Platform Under Maintenance.';
    protected $title = '503 Service Unavailable';
    protected $description = 'We\'ll be back very soon! :)';
}
