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
        $values_str = "'".implode("', '", $values)."'";

        $query = "INSERT INTO {$this->table} ($columns_str) VALUES ($values_str)";

        return $this->db->insert($query);
    }

    public function all()
    {
        return $this->db->select("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
    }

    public function find($id)
    {
        return $this->db->select("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }
}