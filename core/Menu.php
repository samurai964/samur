<?php

class Menu
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getMenus($position)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM menus
            WHERE position = ? AND is_active = 1
            ORDER BY sort_order ASC
        ");

        $stmt->execute([$position]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
