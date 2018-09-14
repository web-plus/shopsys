<?php

declare(strict_types=1);

namespace Shopsys\CodingStandards\Sniffs;

final class ForbiddenPrivateInFactoryAndControllerSniff extends ForbiddenPrivateSniff
{
    public $forbiddenInClasses = [
        'Factory',
        'Controller',
    ];
}
