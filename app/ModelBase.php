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

    public function all()
    {
        return $this->db->execute("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
    }

    public function find($id)
    {
        return $this->db->execute("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }
}