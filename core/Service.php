<?php
class Service {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT id, title, description, price 
            FROM services 
            ORDER BY id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($userId, $title, $desc, $price) {

        if (!$title || !$price) return;

        $stmt = $this->db->prepare("
            INSERT INTO services (user_id, title, description, price)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$userId, $title, $desc, $price]);
    }
}
?>
