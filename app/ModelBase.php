<?php

namespace app;

use core\DB;

abstract class ModelBase
{
    protected $db;

    protected $table = null;

    public function __construct()
    {
        $this->db = new DB();
    }

    private function created_at()
    {
        $datetime = (new \DateTime())->format('Y-m-d H:i:s');
        return ['created_at' => $datetime];
    }

    public function create($data)
    {
        $data = array_merge($data, $this->created_at());

        $columns = array_keys($data);
        $values = array_values($data);

        $columns_str = implode(', ', $columns);
        $values_str = "'" . implode("', '", $values) . "'";

        $query = "INSERT INTO {$this->table} ($columns_str) VALUES ($values_str)";

        return $this->db->insert($query);
    }

    public function update($id, $data)
    {
        $updated_data = [];

        foreach ($data as $column => $value) {
            $updated_data[] = "{$column} = '{$value}'";
        }

        $updated_data_str = implode(', ', $updated_data);

        $query = "UPDATE {$this->table} SET {$updated_data_str} WHERE id = {$id}";

        if ($this->db->update($query)) {
            return $this->find($id);
        } else {
            new \Exception('Что-то пошло не так');
        }
    }

    public function all()
    {
        return $this->db->select("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
    }

    public function find($id)
    {
        $result = $this->db->select("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        return array_shift($result);
    }

    public function getLast($order = 'created_at', $limit = 10, $sort = 'ASC')
    {
        $query = "
          SELECT * FROM (
            SELECT * FROM messages WHERE deleted_at IS NULL ORDER BY {$order} DESC LIMIT {$limit}
          ) AS subquery ORDER BY {$order} {$sort}
        ";

        return $this->db->select($query);
    }
}