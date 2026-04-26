<?php

class UserModel {

    protected $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    public function getUsers() {

        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->query("SELECT id, username FROM users LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsers() {

    if (!$this->db) {
    return [];

    }

    try {
    $stmt = $this->db->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 20");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    return [];
}
}
}
