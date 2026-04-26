<?php

class DashboardWidgets {

    private static $widgets = [];

    public static function register($widget) {
        self::$widgets[] = $widget;
    }

    public static function get() {
        return self::$widgets;
    }

}
