<?php
namespace core;
class Db
{
    protected $pdo;
    protected $tablename;
    protected $table_prefix;
    protected $pk;
    protected $error        = 'success';
    protected $alias        = '';
    protected $columes      = [];
    protected $where        = '';
    protected $join         = '';
    protected $querySql     = '';
    protected $queryColumes = '';
    protected $limit        = '';
    protected $order        = '';
    protected $group        = '';
    protected $fetchSql     = false;

    public function __construct(){}
    
    public function connect($host, $db, $user, $pwd)
    {
        try{
            $this->pdo = new \PDO("mysql:host={$host};dbname={$db}", $user, $pwd);
            $this->pdo->query('set names utf8');
            if ($this->tablename) {
                $columes = $this->pdo->query('SHOW COLUMNS FROM '. $this->table_prefix . $this->tablename)->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($columes as $value) {
                    $this->columes[$value['Field']] = $value['Type'];
                    if ($value['Key'] === 'PRI') {
                        $this->pk = $value['Field'];
                    }
                }
            }
        } catch(PDOException $e) {
            exit('Error<br><br>具体信息: '.$e->getMessage());
        }
    }

    public static function __callStatic($name, $arguments)
    {
        call_user_func_array([$this, $name], $arguments);
    }

    public function __destruct()
    {
        $this->pdo = null;
    }
    
    public function add($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        $columes = '(';
        $values = 'VALUES(';
        foreach ($data as $key => $value) {
            if (!isset($this->columes[$key])) {
                $this->error = 'Error:<br><br>执行方法：add<br><br>错误：'.$key.'字段不存在！';
                return false;
            }
            $columes .= '`' . $key . '`,';
            $values .= htmlspecialchars("'{$value}',");
        }
        $columes = substr($columes, 0, -1) . ')';
        $values = substr($values, 0, -1) . ')';
        $this->querySql = "INSERT INTO `{$this->table_prefix}{$this->tablename}`{$columes}{$values}";
        if ($this->fetchSql) {
            $this->fetchSql = false;
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->exec($this->querySql))) {
            return $this->sqlError('add');
        }
        return $this->pdo->lastInsertId();
    }
    
    public function addAll($data)
    {
        if (empty($data) || !is_array($data)) {
            $this->error = 'Error：<br><br>执行方法：addAll<br><br>错误：传输数据必须为二维数组';
            return false;
        }
        $values = 'VALUES';
        $columes = '(';
        foreach ($data as $v) {
            if (!is_array($v)) {
                $this->error = 'Error：<br><br>执行方法：addAll<br><br>错误：传输数据必须为二维数组';
                return false;
            }
            $value = '(';
            foreach ($v as $key => $val) {
                if (!isset($this->columes[$key])) {
                    $this->error = 'Error:<br><br>执行方法：addAll<br><br>错误：'.$key.'字段不存在！';
                    return false;
                }
                if (!preg_match('/^\(.*\)$/', $columes)) {
                    $columes .= '`' . $key . '`,';
                }
                $value .= htmlspecialchars("'{$val}',");
            }
            $columes = substr($columes, 0, -1) . ')';
            $value = substr($value, 0, -1) . ')';
            $values .= $value . ',';
        }
        $values = substr($values, 0, -1);
        $this->querySql = "INSERT INTO `{$this->table_prefix}{$this->tablename}`{$columes}{$values}";
        if ($this->fetchSql) {
            $this->fetchSql = false;
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->exec($this->querySql))) {
            return $this->sqlError('addAll');
        }
        return $r;
    }

    public function alias($name)
    {
        $this->alias = ' ' . $name;
        return $this;
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function count()
    {
        $this->querySql = "SELECT COUNT(`{$this->pk}`) count FROM {$this->table_prefix}{$this->tablename}{$this->alias} {$this->join} {$this->where} {$this->order} {$this->group} LIMIT 1";
        if ($this->fetchSql) {
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->query($this->querySql))) {
            return $this->sqlError('count');
        }
        $c = $r->fetch(\PDO::FETCH_ASSOC);
        return $c['count'];
    }
    
    public function delete()
    {
        $this->querySql = "DELETE FROM `{$this->table_prefix}{$this->tablename}` {$this->where}";
        if ($this->fetchSql) {
            $this->fetchSql = false;
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->exec($this->querySql))) {
            return $this->sqlError('add');
        }
        $this->where = '';
        return $r;
    }
    
    public function fetchSql($get = true)
    {
        $this->fetchSql = $get;
        return $this;
    }
    
    public function field($data)
    {
        if (empty($data)) {
            return $this;
        }
        if (is_string($data)) {
            if (!empty($this->queryColumes)) {
                $columesArr = explode(',', $data);
                foreach ($columesArr as $key => $value) {
                    if (strpos($this->queryColumes, $value) === -1) {
                        $this->queryColumes .= ',' . $value;
                    }
                }
            } else {
                $this->queryColumes = ' ' . $data;
            }
        }
        if (is_array($data)) {
            if (!empty($this->queryColumes)) {
                foreach ($data as $key => $value) {
                    if (strpos($this->queryColumes, $value) === -1) {
                        $this->queryColumes .= ',' . $value;
                    }
                }
            } else {
                $this->queryColumes = ' ' . implode(',', $data);
            }
        }
        return $this;
    }
    
    public function find()
    {
        $this->queryColumes = empty($this->queryColumes) ? ' *' : $this->queryColumes;
        $this->querySql = 'SELECT' . $this->queryColumes . ' FROM ' . $this->table_prefix . $this->tablename . $this->alias . $this->join . $this->where . $this->order . $this->group . ' LIMIT 1';
        if ($this->fetchSql) {
            $this->fetchSql = false;
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->query($this->querySql))) {
            return $this->sqlError('find');
        }
        $this->queryColumes = '';
        $this->where = '';
        $this->order = '';
        $this->group = '';
        return $r->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function getError()
    {
        return $this->error;
    }
    
    public function getField($field)
    {
        if (empty($field) || !is_string($field)) {
            $this->error = "Error：<br><br>执行方法：getField<br><br>错误：查询字段错误";
            return false;
        }
        $this->querySql = 'SELECT ' . $field . ' FROM ' . $this->table_prefix . $this->tablename . $this->alias . $this->join . $this->where . $this->order . $this->group . ' LIMIT 1';
        $data = $this->pdo->query($this->querySql)->fetch(\PDO::FETCH_ASSOC);
        if ($data === false) {
            return $this->sqlError('getField');
        }
        $this->where = '';
        $this->order = '';
        $this->group = '';
        return $data[$field];
    }
    
    public function group($data)
    {
        $this->group = ' GROUP BY ' . $data;
        return $this;
    }

    public function join($data, $option)
    {
        $this->join .= " INNER JOIN {$data} ON {$option}";
        return $this;
    }
    
    public function limit($data)
    {
        if (is_string($data)) {
            $this->limit = ' LIMIT ' . $data;
            return $this;
        }
        if (is_array($data)) {
            $this->limit = ' LIMIT ' . implode(',', $data);
        }
        return $this;
    }

    public function name($tablename)
    {
        $this->tablename = $tablename;
        $columes = $this->pdo->query('SHOW COLUMNS FROM '. $this->table_prefix . $this->tablename)->fetchAll(\PDO::FETCH_ASSOC);
        $this->columes = [];         
        foreach ($columes as $value) {
            $this->columes[$value['Field']] = $value['Type'];
            if ($value['Key'] === 'PRI') {
                $this->pk = $value['Field'];
            }
        }
        return $this;
    }
    
    public function order($data)
    {
        $this->order = ' ORDER BY ' . $data;
        return $this;
    }

    public function rollback()
    {
        $this->pdo->rollBack();
    }
    
    public function select()
    {
        $this->queryColumes = empty($this->queryColumes) ? ' *' : $this->queryColumes;
        $this->querySql = 'SELECT' . $this->queryColumes . ' FROM `' . $this->table_prefix . $this->tablename . '`' . $this->alias . $this->join . $this->where . $this->order . $this->group . $this->limit;
        if ($this->fetchSql) {
            $this->fetchSql = false;
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->query($this->querySql)->fetchAll(\PDO::FETCH_ASSOC))) {
            return $this->sqlError('select');
        }
        $this->queryColumes = '';
        $this->where = '';
        $this->order = '';
        $this->group = '';
        return $r;
    }
    
    public function sqlError($method)
    {
        $error = $this->pdo->errorInfo();
        $this->error = "Error:<br><br>执行方法：{$method}<br><br>错误：SQL error:{$error[2]}<br><br>执行sql：{$this->querySql}";
        return false;
    }

    public function startTrans()
    {
        $this->pdo->beginTransaction();
    }
    
    public function where($data)
    {
        if (empty($data)) {
            return $this;
        }
        if (is_string($data)) {
            $this->where = !empty($this->where) ? $this->where . ' AND ( ' . $data . ')' : ' WHERE ' . $data;
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    $where = !empty($where) ? $where . " AND {$key}='{$value}'" : "( {$key}='{$value}'";
                    continue;
                }
                if ($value[0] === 'exp') {
                    $where = !empty($where) ? $where . " AND {$key}{$value[1]}{$value[2]}" : "( {$key}{$value[1]}{$value[2]}";
                        continue;
                }
                if ($value[0] === 'between') {
                    $where = !empty($where) ? $where . " AND ( {$key} BETWEEN {$value[1]} AND {$value[2]} )" : "( {$key} BETWEEN {$value[1]} AND {$value[2]} )";
                    continue;
                }
                $where = !empty($where) ? $where . " AND {$key}{$value[0]}{$value[1]}" : "( {$key}{$value[0]}{$value[1]}";
            }
            $where .= ' )';
            $this->where = !empty($this->where) ? $this->where . $where : ' WHERE ' . $where;
        }
        return $this;
    }
    
    public function update($data)
    {
        if (!is_array($data)) {
            return false;
        }
        $update = 'SET ';
        foreach ($data as $key => $value) {
            $update .= '`' . $key . '`="' . $value . '",';
        }
        $update = substr($update, 0, -1);
        $this->querySql = "UPDATE `{$this->table_prefix}{$this->tablename}` {$update} {$this->where}";
        if ($this->fetchSql) {
            $this->fetchSql = false;
            return $this->querySql;
        }
        if (false === ($r = $this->pdo->exec($this->querySql))) {
            return $this->sqlError('update');
        }
        $this->where = '';
        return $r;
    }
}