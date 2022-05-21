<?php


namespace Xtwoend\HyperfClickhouse;


use Hyperf\Utils\Str;
use Hyperf\Database\Model\Concerns\HasAttributes;
use Tinderbox\ClickhouseBuilder\Query\Enums\Operator;
use Tinderbox\ClickhouseBuilder\Query\TwoElementsLogicExpression;

class Model
{

    use HasAttributes;

    /**
     * The name of the "created at" column.
     *
     * @var null|string
     */
    public const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var null|string
     */
    public const UPDATED_AT = 'updated_at';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Use this only when you have Buffer table engine for inserts
     * @link https://clickhouse.tech/docs/ru/engines/table-engines/special/buffer/
     *
     * @var string
     */
    protected $tableForInserts;

    /**
     * Use this field for OPTIMIZE TABLE OR ALTER TABLE (also DELETE) queries
     *
     * @var string
     */
    protected $tableSources;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Get the table name for insert queries
     *
     * @return string
     */
    public function getTableForInserts()
    {
        return $this->tableForInserts ?? $this->getTable();
    }

    /**
     * Use this field for OPTIMIZE TABLE OR ALTER TABLE (also DELETE) queries
     * @return string
     */
    public function getTableSources()
    {
        return $this->tableSources ?? $this->getTable();
    }

    /**
     * @return \Tinderbox\Clickhouse\Client
     */
    public static function getClient()
    {
        return Clickhouse::connection('clickhouse')->getClient();
    }

    /**
     * Begin querying the model.
     *
     * @return \Xtwoend\HyperfClickhouse\Builder
     */
    public static function query()
    {
        return (new static())->newQuery();
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Xtwoend\HyperfClickhouse\Builder
     */
    public function newQuery()
    {   
        return (new Builder)->from($this->getTableForInserts()); 
    }

    /**
     * Create and return an un-saved model instance.
     * @param array $attributes
     * @return static
     */
    public static function make($attributes = [])
    {
        $model = new static;
        $model->fill($attributes);
        return $model;
    }

    /**
     * Save a new model and return the instance.
     * @param array $attributes
     * @return static
     * @throws \Exception
     */
    public static function create($attributes = [])
    {
        $model = static::make($attributes);
        $model->save();
        return $model;
    }

    /**
     * Fill the model with an array of attributes.
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Save the model to the database.
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \Exception("Clickhouse does not allow update rows");
        }
        $this->exists = static::insert([$this->getAttributes()]);
        return $this->exists;
    }

    /**
     * Prepare row to insert into DB, non associative array
     * Need to overwrite in nested models
     * @param array $row
     * @param array $columns
     * @return array
     */
    public static function prepareFromRequest($row, $columns = [])
    {
        return $row;
    }

    /**
     * Prepare row to insert into DB, associative array
     * Need to overwrite in nested models
     * @param array $row
     * @return array
     */
    public static function prepareAssocFromRequest($row)
    {
        return $row;
    }

    /**
     * Necessary stub for HasAttributes trait
     * @return array
     */
    public function getCasts()
    {
        return $this->casts;
    }

    /**
     * Necessary stub for HasAttributes trait
     * @return bool
     */
    public function usesTimestamps()
    {
        return false;
    }

    /**
     * Necessary stub for HasAttributes trait
     * @param string $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        return null;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return $this->{$method}(...$parameters);
        }

        return call([$this->newQuery(), $method], $parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static())->{$method}(...$parameters);
    }

    /**
     * Optimize table. Using for ReplacingMergeTree, etc.
     * @source https://clickhouse.tech/docs/ru/sql-reference/statements/optimize/
     * @param bool $final
     * @param string|null $partition
     * @return \ClickHouseDB\Statement
     */
    public static function optimize($final = false, $partition = null)
    {
        $sql = "OPTIMIZE TABLE " . (new static)->getTableSources();
        if ($partition) {
            $sql .= " PARTITION $partition";
        }
        if ($final) {
            $sql .= " FINAL";
        }
        return static::getClient()->write($sql);
    }

}