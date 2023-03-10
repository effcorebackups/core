<?php

##################################################################
### Copyright © 2017—2022 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

abstract class cache extends dynamic {

    const DIRECTORY = DIR_DYNAMIC.'cache/';
    static public $info = []; # own cache info space
    static public $data = []; # own cache data space

    static function cleaning() {
        static::$info = [];
        static::$data = [];
        foreach (file::select_recursive(static::DIRECTORY, '', true) as $c_path => $c_object) {
            if ($c_path !== static::DIRECTORY.'readme.md') {
                if ($c_object instanceof file) {
                    if (!@unlink($c_path)) {
                        $c_file = new file($c_path);
                        static::message_on_error_delete($c_file);
                    }
                } else {
                    @rmdir($c_path);
                }
            }
        }
    }

    static function update($name, $data, $sub_dirs = '', $info = null) {
        if (parent::update($name, $data, $sub_dirs, $info)) {
            console::log_insert('storage', 'cache', 'cache for '.$name.' was rebuild', 'ok');
            return true;
        }
    }

    static function update_global($modules_to_include = []) {
        static::cleaning();                                    # delete dynamic/cache/*.php
        core::structures_select($modules_to_include);          # create dynamic/cache/structures.php
        storage_nosql_data::cache_update($modules_to_include); # create dynamic/cache/data--*.php
        core::structures_cache_cleaning_after_on_install();    # method *::cache_cleaning() call for each class which implements "should_clear_cache_after_on_install"
    }

}
