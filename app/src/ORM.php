<?php

namespace App;

use App\ArrayUtil as A;
use PDO;
use PDOStAtement;
use PDOException;
use Throwable;

class ORM
{
    protected $query = null;

    /**
     * @var PDO
     */
    public $pdo = null;

    /**
     * @var PDOStatement
     */
    public $stmt = null;
    private $error = null;
    private $executed = false;

    static $history = [];
    static $columns = [];

    public function __construct(protected string $sql, protected array $params = [])
    {
        $this->query = $sql;

        if (!empty($params)) {
            $tmp_params = [];
            if (static::is_array($params)) {
                $idxl = count($params);
                foreach ($params as $index => $param) {
                    $idxkey = str_pad($index, strlen($idxl), '0', STR_PAD_LEFT);
                    $param_key = ':p__' . $idxkey;
                    $this->query = preg_replace('/\?/', $param_key, $this->query, 1);
                    $tmp_params[$param_key] = $param;
                }
                $params = &$tmp_params;
            }

            foreach ($params as $key => $param) {
                $key = preg_replace('/^\:/', '', $key);
                if ($param instanceof static) {
                    $value = $param->__toString();
                } elseif (is_int($param) || is_float($param)) {
                    $value = $param;
                } elseif (is_null($param)) {
                    $value = 'NULL';
                } elseif (is_bool($param)) {
                    $value = $param ? 1 : 0;
                } else if (is_array($param)) {
                    $value = static::in($param);
                } else {
                    $value = "'" . addslashes($param) . "'";
                }

                $this->query = str_replace(":$key", $value, $this->query);
            }
        }
    }

    static function is_array($value)
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    public function __toString(): string
    {
        return $this->query ?? $this->sql;
    }
    public function __invoke(): string
    {
        return $this->__toString();
    }
    public static function build(...$args): static
    {
        return new static(...$args);
    }

    public static function raw(string $sql, array $params = []): static
    {
        return new static($sql, $params);
    }

    public static function history(): array
    {
        return static::$history;
    }

    public static function columns(string|array $names, $wrapper = '`'): static
    {
        if (empty($names))
            return new static('');
        if (!is_array($names)) {
            $names = [$names];
        }

        if (static::is_array($names)) {
            $sql = $wrapper . implode("{$wrapper}, {$wrapper}", array_values($names)) . $wrapper;
            return new static($sql);
        } else {
            $selects = [];
            foreach ($names as $alias => $name) {
                $name = preg_replace('/\./', "{$wrapper}.{$wrapper}", $name);
                if (is_numeric($alias)) {
                    $selects[] = "{$wrapper}{$name}{$wrapper}";
                } else {
                    $name = preg_match('/\(/', $name) ? new static($name) : "{$wrapper}{$name}{$wrapper}";
                    $selects[] = "{$name} AS {$wrapper}{$alias}{$wrapper}";
                }
            }

            $sql = implode(', ', $selects);
            return new static($sql);
        }
    }

    public static function in(array $values): static
    {
        $sql = implode(', ', array_map(function ($value) {
            if ($value instanceof static) {
                return $value->__toString();
            } elseif (is_numeric($value)) {
                return $value;
            } elseif (is_null($value)) {
                return 'NULL';
            } elseif (is_bool($value)) {
                return $value ? 1 : 0;
            } else {
                return "'" . addslashes(strval($value)) . "'";
            }
        }, $values));

        return new static("({$sql})");
    }

    public static function table(string $name, string $alias = null): static
    {
        $sql = "`{$name}`";
        if ($alias) {
            $sql .= " AS `{$alias}`";
        }
        return new static($sql);
    }

    public static function select(string|array $table, array $query)
    {
        $table = is_array($table) ? static::table(...$table) : static::table($table);
        $cols = A::get($query, ['columns', 'column', 'col', 'cols', 'select'], '*');
        if (is_array($cols)) {
            $cols = static::columns($cols);
        }

        $where = A::get($query, ['where'], null);
        $where = $where ? static::where($where) : $where;
        $where = $where ? " WHERE $where" : '';

        $order = A::get($query, ['order', 'orderby', 'sort', 'sortby'], null);
        $order = $order ? static::order($order) : $order;
        $order = $order ? " ORDER BY $order" : '';

        $offset = A::get($query, ['offset', 'skip'], null);
        $offset = $offset ? static::raw(' OFFSET ?', [$offset]) : '';

        $limit = A::get($query, ['limit', 'take'], null);
        $limit = $limit ? static::raw(' LIMIT ?', [$limit]) : '';

        $join = A::get($query, ['join', 'joins'], null);
        $join = $join ? ' ' . static::join($join) : $join;

        $group = A::get($query, ['group', 'groupby'], null);
        $group = $group ? (is_array($group) ? static::columns($group) : $group) : $group;
        $group = $group ? " GROUP BY $group" : '';

        $having = A::get($query, ['having'], null);
        $having = $having ? static::where($having) : $having;
        $having = $having ? " HAVING $having" : '';

        return static::raw("SELECT $cols FROM " . $table . $join . $where . $group . $having . $order . $limit . $offset);
    }

    public static function where(array|string|self $data)
    {
        if (is_string($data) || $data instanceof self) {
            return $data;
        }

        $build = [];
        foreach ($data as $key => $value) {
            $cond = false;
            $adjective = 'AND';

            if (gettype($key) === 'integer') {
                if (is_string($value) || $value instanceof self) {
                    $cond = $data;
                } else if (is_array($value)) {
                    $cond = static::raw('(?)', [static::where($value)]);
                } else {
                    continue;
                }
            } else {
                if (preg_match('/^(and|or|not)\s+/i', $key, $matches)) {
                    $adjective = strtoupper($matches[1]);
                    $key = preg_replace('/^(and|or|not)\s+/i', '', $key);
                }

                if (is_array($value)) {
                    $sub = [];
                    foreach ($value as $operator => $v) {
                        $adj = 'AND';
                        $cnd = false;
                        if (gettype($operator) === 'integer') {
                            continue;
                        }
                        if (count($sub) > 0 && preg_match('/^(and|or|not)\s+/i', $operator, $matches)) {
                            $adj = strtoupper($matches[1]);
                            $operator = preg_replace('/^(and|or|not)\s+/i', '', $operator);
                        }

                        $operator = strtoupper($operator);
                        $operator = match ($operator) {
                            'GT' => '>',
                            'GTE' => '>=',
                            'LT' => '<',
                            'LTE' => '<=',
                            'E' => '=',
                            'EQ' => '=',
                            'EQUAL' => '=',
                            'EQUALS' => '=',
                            'NEQ' => '!=',
                            'NIN' => 'NOT IN',
                            default => $operator
                        };

                        if (in_array($operator, ['BETWEEN', 'NOT BETWEEN'])) {
                            $cnd = static::raw("`{$key}` {$operator} ? AND ?", $v);
                        } else if (in_array($operator, ['IN', 'NOT IN'])) {
                            $cnd = static::raw("`{$key}` {$operator} ?", [(is_array($v) ? $v : [$v])]);
                        } else {
                            $cnd = static::raw("`{$key}` {$operator} ?", [$v]);
                        }

                        if (count($sub) > 0) {
                            $sub[] = $adj;
                        }

                        $sub[] = $cnd;
                    }

                    if (count($sub) === 1) {
                        $cond = $sub[0];
                    } else if (count($sub) > 1) {
                        $cond = '(' . implode(' ', $sub) . ')';
                    }
                } else {
                    $cond = static::raw('`' . $key . '` = ?', [$value]);
                }
            }

            if ($cond) {
                if (count($build) > 0) {
                    $build[] = $adjective;
                }
                $build[] = $cond;
            }
        }

        if (count($build) > 0) {
            return implode(' ', $build);
        }

        return false;
    }

    public static function order(array|string|self $data)
    {
        if (is_string($data) || $data instanceof self) {
            return $data;
        }

        $build = [];
        foreach ($data as $key => $value) {
            $adjective = 'ASC';
            if (gettype($key) === 'integer') {
                if (is_string($value) || $value instanceof self) {
                    $build[] = $value;
                } else if (is_array($value)) {
                    $build[] = static::order($value);
                } else {
                    continue;
                }
            } else {
                if (count($build) > 0 && preg_match('/^(asc|desc)\s+/i', $key, $matches)) {
                    $adjective = strtoupper($matches[1]);
                    $key = preg_replace('/^(asc|desc)\s+/i', '', $key);
                }

                $build[] = static::raw('`' . $key . '` ' . $adjective);
            }
        }

        if (count($build) > 0) {
            return implode(', ', $build);
        }

        return false;
    }

    public static function join(array|string|self $data)
    {
        if (is_string($data) || $data instanceof self) {
            return $data;
        }

        $build = [];
        foreach ($data as $key => $value) {
            $where = static::where($value);
            if (!$where) {
                continue;
            }
            $build[] = static::raw("$key ON {$where}");
        }

        if (count($build) > 0) {
            return ' ' . implode(' ', $build);
        }

        return false;
    }

    public static function connect(?array $options = null)
    {
        $driver = getenv('DB_DRIVER') ?: 'mysql';
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: 3306;
        $dbname = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');

        $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};";
        return new PDO($dsn, $username, $password, $options);

    }

    public function pdo()
    {
        try {
            if (!$this->pdo) {
                $this->pdo = static::connect([
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function lastError()
    {
        return $this->error;
    }

    public function exec(): false|PDOStatement
    {
        if ($this->executed) {
            return $this->stmt;
        }

        static::$history[] = $this->query;

        $this->pdo();
        if ($this->error) {
            return false;
        }

        $this->stmt = $this->pdo->prepare($this->query);
        $this->stmt->execute();
        $this->executed = true;
        return $this->stmt;
    }

    public static function insert(string $table, array $data)
    {
        $cols = static::getTableColumns($table);
        $data = array_intersect_key($data, array_flip($cols));

        $keys = array_keys($data);
        $values = array_values($data);
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        for ($i = 0; $i < count($values); $i++) {
            if (is_array($values[$i])) {
                $values[$i] = count($values[$i]) ? json_encode($values[$i]) : null;
            }
        }
        return static::raw($sql, $values);
    }

    public static function insert_many(string $table, array $data)
    {
        $cols = static::getTableColumns($table);
        $keys = array_keys($data[0]);
        $values = [];
        foreach ($data as $d) {
            $d = array_intersect_key($d, array_flip($cols));
            $rowdata = array_values($d);
            for ($i = 0; $i < count($rowdata); $i++) {
                if (is_array($rowdata[$i])) {
                    $rowdata[$i] = count($rowdata[$i]) ? json_encode($rowdata[$i]) : null;
                }
            }
            $values = array_merge($values, $rowdata);
        }
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES " . implode(', ', array_fill(0, count($data), '(' . implode(', ', array_fill(0, count($keys), '?')) . ')'));
        return static::raw($sql, $values);
    }

    public static function update(string $table, array $data, self|string|array $where = '1')
    {
        $cols = static::getTableColumns($table);
        $data = array_intersect_key($data, array_flip($cols));
        $keys = array_keys($data);
        $values = array_values($data);
        $where = static::where($where);
        $sql = "UPDATE `$table` SET `" . implode('` = ?, `', $keys) . "` = ?" . ($where ? " WHERE $where" : '');

        for ($i = 0; $i < count($values); $i++) {
            if (is_array($values[$i])) {
                $values[$i] = count($values[$i]) ? json_encode($values[$i]) : null;
            }
        }

        return static::raw($sql, $values);
    }

    public static function delete(string $table, self|string|array $where = '1')
    {
        $where = static::where($where);
        $sql = "DELETE FROM `$table`" . ($where ? " WHERE $where" : '');
        return static::raw($sql);
    }

    public function fetch(...$args)
    {
        $this->exec();
        return $this->stmt->fetch(...$args);
    }

    public function fetchAll(...$args)
    {
        $this->exec();
        return $this->stmt->fetchAll(...$args);
    }

    public function fetchColumn(...$args)
    {
        $this->exec();
        return $this->stmt->fetchColumn(...$args);
    }

    public function fetchObject(...$args)
    {
        $this->exec();
        return $this->stmt->fetchObject(...$args);
    }

    public function fetchAllObject(...$args)
    {
        $this->exec();
        return $this->stmt->fetchAll(PDO::FETCH_CLASS, ...$args);
    }

    public function affected()
    {
        $this->exec();
        return $this->stmt->rowCount();
    }

    public function id(...$args)
    {
        $this->pdo();
        return $this->pdo->lastInsertId(...$args);
    }

    public function beginTransaction()
    {
        $this->pdo();
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo();
        return $this->pdo->commit();
    }

    public function rollback()
    {
        $this->pdo();
        return $this->pdo->rollBack();
    }

    public static function tableColumns(string $table)
    {
        $sql = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        return static::raw($sql, [getenv('DB_NAME'), $table]);
    }

    public static function getTableColumns(string $table)
    {
        if (isset(static::$columns[$table])) {
            return static::$columns[$table];
        }
        $sql = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        $stmt = static::raw($sql, [getenv('DB_NAME'), $table])->exec();
        static::$columns[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return static::$columns[$table];
    }
}