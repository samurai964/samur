<?php

Hook::add('page_footer', function ($data) {
    echo "<div style='text-align:center;'>Test Module Loaded</div>";
});