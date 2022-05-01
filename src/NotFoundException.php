<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer;

class NotFoundException extends \Exception implements \Psr\Container\NotFoundExceptionInterface
{
}
