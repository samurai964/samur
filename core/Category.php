<?php

class Category
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        return $this->db->query("SELECT * FROM categories ORDER BY id DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByPost($postId)
    {
        $stmt = $this->db->prepare("
            SELECT c.* FROM categories c
            JOIN post_categories pc ON pc.category_id = c.id
            WHERE pc.post_id = ?
        ");

        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
