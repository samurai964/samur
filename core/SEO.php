<?php

class SEO
{
    public function render($title, $description)
    {
        echo "
        <title>$title</title>
        <meta name='description' content='$description'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        ";
    }
}
?>
