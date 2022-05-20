<?php

namespace Xtwoend\HyperfClickhouse;

use ClickHouseDB\Client;
use Psr\Container\ContainerInterface;
use Hyperf\Contract\ConnectionInterface;
use Xtwoend\HyperfClickhouse\Pool\DbPool;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Traits\DbConnection;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Database\ConnectionInterface as DbConnectionInterface;

class Connection extends BaseConnection implements ConnectionInterface, DbConnectionInterface
{
    use DbConnection;

    /**
     * @var DbPool
     */
    protected $pool;

    /**
     * @var DbConnectionInterface
     */
    protected $connection;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    protected $transaction = false;

    /**
     * @var ClickHouseDB\Client
     */
    protected $client;

    public function __construct(ContainerInterface $container, DbPool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = $config;
        $this->logger = $container->get(StdoutLoggerInterface::class);

        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function getActiveConnection(): DbConnectionInterface
    {
        if ($this->check()) {
            return $this;
        }

        if (! $this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }

        return $this;
    }

    public function reconnect(): bool
    {
        $this->close();

        $this->connection = $this->makeConnection($this->config);
        $this->lastUseTime = microtime(true);

        return true;
    }

    public function close(): bool
    {
        unset($this->connection);

        return true;
    }

    public function release(): void
    {
        parent::release();
    }

    public function setTransaction(bool $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function isTransaction(): bool
    {
        return $this->transaction;
    }

    /**
     * Refresh pdo and readPdo for current connection.
     */
    protected function refresh(\Hyperf\Database\Connection $connection)
    {
        $this->logger->warning('Database connection refreshed.');
    }

    /**
     * get client clickhouse
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * make connection clickhouse
     */
    public function makeConnection($config)
    {
        $this->client = new Client($config);
        $this->client->database($config['database']);
        $this->client->setTimeout($config['timeout_query']); 
        $this->client->setConnectTimeOut($config['timeout_connect']);
        $this->client->ping(true);
     
        if ($configSettings =& $config['settings']) {
            $settings = $this->client->settings();
            foreach ($configSettings as $sName => $sValue) {
                $settings->set($sName, $sValue);
            }
        }

        if ($retries = (int)($config['retries'] ?? null)) {
            $curler = new CurlerRollingWithRetries();
            $curler->setRetries($retries);
            $this->client->transport()->setDirtyCurler($curler);
        }

        return $this->client;
    }
}
