<?php

namespace core;

class DB
{

    public $pdo;

    public function __construct()
    {
        $settings = $this->getPDOSettings();
        $this->pdo = new \PDO($settings['dsn'], $settings['user'], $settings['password'], null);
    }

    protected function getPDOSettings()
    {
        $config = include ROOT_PATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        $result['dsn'] = "{$config['type']}:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $result['user'] = $config['user'];
        $result['password'] = $config['password'];

        return $result;
    }

    public function select($query, array $params = null)
    {

        if (is_null($params)) {
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        $stmt = $this->pdo->prepare($query);

        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert($query)
    {
        $this->pdo->query($query);

        return $this->pdo->lastInsertId();
    }

    public function update($query)
    {
        return $this->pdo->query($query);
    }
}