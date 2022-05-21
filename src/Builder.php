<?php


namespace Xtwoend\HyperfClickhouse;


use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use ClickHouseDB\Quote\FormatLine;
use Xtwoend\HyperfClickhouse\Clickhouse;
use ClickHouseDB\Exception\QueryException;
use Tinderbox\ClickhouseBuilder\Query\Grammar;
use Tinderbox\ClickhouseBuilder\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{

    /**
     * @var \Tinderbox\Clickhouse\Client
     */
    protected $client;

    /** @var string */
    protected $tableSources;

    public function __construct()
    {
        $this->client = Clickhouse::connection('clickhouse')->getClient();
        $this->grammar = new Grammar();
    }
    
    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     */
    public function chunk(int $count, callable $callback)
    {
        $offset = 0;
        do {
            $rows = $this->limit($count, $offset)->getRows();
            $callback($rows);
            $offset += $count;
        } while ($rows);
    }
}