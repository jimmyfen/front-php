<?php
namespace core;
class Model extends Db
{
    public function __construct()
    {
        parent::__construct();
        $db_config = config('Db');
        $this->tablename = isset($this->tablename) ? $this->tablename : strtolower(substr(get_class($this), strpos(get_class($this), '\\')+1));
        $this->table_prefix = isset($this->table_prefix) ? $this->table_prefix : $db_config['table_prefix'];
        $this->connect($db_config['host'], $db_config['dbname'], $db_config['user'], $db_config['pwd']);
        return $this;
    }
}