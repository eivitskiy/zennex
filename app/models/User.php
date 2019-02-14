<?php

namespace app\models;

use app\ModelBase;

class User extends ModelBase
{
    protected $table = 'users';

    public function existsUser($username, $token)
    {
        $query = "SELECT * FROM {$this->table} WHERE username = ?";
        $result = $this->db->select($query, [$username]);

        var_dump($result);

        /**
         * если пользователя с username нет, то возвращаем null
         * если пользователь есть, но токен не соответствует - false
         * если пользователь есть и токен соответствует - true
         */
        if(count($result) == 0) {
            return null;
        } elseif(array_shift($result)['token'] !== $token) {
            return false;
        } else {
            return true;
        }
    }

    public function getByUsername($username)
    {
        $result = $this->db->select("SELECT * FROM {$this->table} WHERE username = ?", [$username]);
        return array_shift($result);
    }
}