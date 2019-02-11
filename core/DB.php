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

    public function execute($query, array $params = null)
    {

        if (is_null($params)) {
            $stmt = $this->pdo->query($query);

            return $stmt->fetchAll();
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}