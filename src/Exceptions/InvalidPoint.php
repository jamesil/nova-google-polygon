<?php

namespace Jamesil\NovaGooglePolygon\Exceptions;

use Exception;
use Jamesil\NovaGooglePolygon\Support\Point;

final class InvalidPoint extends Exception
{
    public function __construct(public readonly array|Point $point)
    {
        parent::__construct(sprintf("Invalid point : %s", json_encode($this->point)));
    }
}
