<?php

namespace Volkszaehler\Util;

use Volkszaehler\Router;
use PHPPM\Bootstraps\BootstrapInterface;
use Stack\Builder;

/**
 * Bootstrap bridge for Router
 */
class MiddlewareBootstrapInterface implements BootstrapInterface
{
    /**
     * @var string|null The application environment
     */
    protected $appenv;

    /**
     * Instantiate the bootstrap, storing the $appenv
     */
    public function __construct($appenv)
    {
        $this->appenv = $appenv;
    }

    /**
     * Create middleware router
     */
    public function getApplication()
    {
        $app = new Router();
        return $app;
    }

    /**
     * Return the StackPHP stack.
     */
    public function getStack(Builder $stack)
    {
        return $stack;
    }
}
