<?php

class ContentEngine
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function renderPost($content)
    {
        $parts = explode("\n", $content);
        $middle = floor(count($parts)/2);

        $output = "";

        // TOP ADS
        foreach ($this->getAds('content_top') as $ad) {
            $output .= "<div class='ad-box'>{$ad['content']}</div>";
        }

        foreach ($parts as $i => $p) {

            $output .= "<p>" . htmlspecialchars($p) . "</p>";

            if ($i == $middle) {
                foreach ($this->getAds('content_middle') as $ad) {
                    $output .= "<div class='ad-box'>{$ad['content']}</div>";
                }
            }
        }

        // BOTTOM ADS
        foreach ($this->getAds('content_bottom') as $ad) {
            $output .= "<div class='ad-box'>{$ad['content']}</div>";
        }

        return $output;
    }

    private function getAds($placement)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ads WHERE placement = ? AND is_active = 1
        ");
        $stmt->execute([$placement]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
