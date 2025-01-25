<?php

function log_to_file($message) {
    $log_file = plugin_dir_path(__FILE__) . 'log/log.txt';

    $log_message = date('Y-m-d H:i:s') . ' - ' . $message . "\n";

    error_log($log_message, 3, $log_file);
}

?>