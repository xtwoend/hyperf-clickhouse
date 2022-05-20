<?php

namespace Xtwoend\HyperfClickhouse;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

/**
 * Clickhouse 
 */
class Clickhouse
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __call($name, $arguments)
    {
        if ($name === 'connection') {
            return $this->__connection(...$arguments);
        }
        return $this->__connection()->{$name}(...$arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        $db = ApplicationContext::getContainer()->get(Clickhouse::class);
        if ($name === 'connection') {
            return $db->__connection(...$arguments);
        }
        return $db->__connection()->{$name}(...$arguments);
    }

    private function __connection($pool = 'default'): ConnectionInterface
    {
        $resolver = $this->container->get(ConnectionResolver::class);
        return $resolver->connection($pool);
    }
}
