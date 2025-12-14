<?php

namespace App\Services;

use Exception;

class DatabaseService
{
    private $connection;
    
    public function __construct()
    {
        $this->connect();
    }
    
    private function connect()
    {
        $host = env('DB_HOST');
        $port = env('DB_PORT');
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        
    $sslmode = env('DB_SSLMODE', 'prefer');
    $connString = "host=$host port=$port dbname=$database user=$username password=$password sslmode=$sslmode";
        
        $this->connection = pg_connect($connString);
        
        if (!$this->connection) {
            throw new Exception("Error al conectar a PostgreSQL");
        }
    }
    
    public function query($sql, $params = [])
    {
        // Verificar si la conexión está cerrada y reconectar si es necesario
        if (!$this->connection || pg_connection_status($this->connection) !== PGSQL_CONNECTION_OK) {
            $this->connect();
        }
        
        if (empty($params)) {
            $result = pg_query($this->connection, $sql);
        } else {
            $result = pg_query_params($this->connection, $sql, $params);
        }
        
        if (!$result) {
            throw new Exception("Error en la consulta SQL");
        }
        
        return $result;
    }
    
    public function fetchAll($result)
    {
        $rows = [];
        while ($row = pg_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function fetchOne($result)
    {
        return pg_fetch_assoc($result);
    }
    
    public function escape($string)
    {
        return pg_escape_string($this->connection, $string);
    }
    
    public function getLastInsertId($table, $column = 'id')
    {
        $result = $this->query("SELECT currval(pg_get_serial_sequence('$table', '$column')) as id");
        $row = $this->fetchOne($result);
        return $row['id'];
    }
    
    public function __destruct()
    {
        if ($this->connection) {
            pg_close($this->connection);
        }
    }
}