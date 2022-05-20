<?php

namespace Xtwoend\HyperfClickhouse;

use Xtwoend\HyperfClickhouse\Pool\PoolFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'databases' => [
                'clickhouse' => [
                    'driver' => 'clickhouse',
                    'host' => env('CLICKHOUSE_HOST', '127.0.0.1'),
                    'port' => env('CLICKHOUSE_PORT','8123'),
                    'database' => env('CLICKHOUSE_DATABASE','default'),
                    'username' => env('CLICKHOUSE_USERNAME','default'),
                    'password' => env('CLICKHOUSE_PASSWORD',''),
                    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT',2),
                    'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY',2),
                    'https' => (bool) env('CLICKHOUSE_HTTPS', null),
                    'retries' => env('CLICKHOUSE_RETRIES', 0),
                    'settings' => [ // optional
                        'max_partitions_per_insert_block' => 300,
                    ],
                ],
            ],
            'dependencies' => [
                PoolFactory::class => PoolFactory::class,
                ConnectionResolver::class => ConnectionResolver::class
            ]
        ];
    }
}