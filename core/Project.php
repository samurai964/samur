<?php
class Project {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM projects")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($userId, $title, $desc, $budget) {
        $stmt = $this->db->prepare("INSERT INTO projects (user_id, title, description, budget) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $desc, $budget]);
    }
}
?>
