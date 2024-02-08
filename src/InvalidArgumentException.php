<?php

namespace Battis\LazySecrets;

use Exception;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;

/**
 * @psalm-suppress UnusedClass
 */
class InvalidArgumentException extends Exception implements
    SimpleCacheInvalidArgumentException {}
