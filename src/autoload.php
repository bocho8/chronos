<?php
/**
 * Copyright (C) 2025 Agustín Roizen
 * Use of this software is governed by the Business Source License included in the LICENSE.TXT file and at www.mariadb.com/bsl11.
 * 
 * Change Date: Three years from the date of the first publicly available distribution of each version
 * 
 * On the date above, in accordance with the Business Source License, use of this software will be governed by the open source license specified in the LICENSE.TXT file.
 */

/**
 * Simple autoloader for the Chronos application
 */

spl_autoload_register(function ($class) {

    $class = str_replace('App\\', '', $class);
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

spl_autoload_register(function ($class) {

    $file = __DIR__ . '/helpers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }

    $file = __DIR__ . '/models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }

    $file = __DIR__ . '/app/Models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
