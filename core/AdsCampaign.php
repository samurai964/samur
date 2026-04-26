<?php

class AdsCampaign
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($userId, $content, $budget)
    {
        $stmt = $this->db->prepare("
            INSERT INTO ad_campaigns (user_id, content, budget)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $content, $budget]);
    }
}
?>
