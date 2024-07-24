<?php

namespace VPlugins\SMPostConnector\Helper;

class Globals {
    const PLUGIN_VERSION = '1.0.0'; 

    public static function get_version() {
        return self::PLUGIN_VERSION;
    }
    public static function get_categories() {
        return get_categories([
            'hide_empty' => false
        ]);
    }
}
