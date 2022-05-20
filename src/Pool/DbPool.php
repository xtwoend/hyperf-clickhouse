<?php

namespace Xtwoend\HyperfClickhouse\Pool;

use Hyperf\Pool\Pool;
use Hyperf\Utils\Arr;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Contract\ConnectionInterface;
use Xtwoend\HyperfClickhouse\Connection;

class DbPool extends Pool
{
    protected $name;

    protected $config;

    public function __construct(ContainerInterface $container, string $name)
    {
        $this->name = $name;
        $config = $container->get(ConfigInterface::class);
        $key = sprintf('databases.%s', $this->name);
        if (! $config->has($key)) {
            throw new \InvalidArgumentException(sprintf('config[%s] is not exist!', $key));
        }
        // Rewrite the `name` of the configuration item to ensure that the model query builder gets the right connection.
        $config->set("{$key}.name", $name);
        
        $this->config = $config->get($key);
        $options = Arr::get($this->config, 'pool', []);

        parent::__construct($container, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function createConnection(): ConnectionInterface
    {
        return new Connection($this->container, $this, $this->config);
    }
}
