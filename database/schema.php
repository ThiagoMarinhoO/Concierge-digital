<?php
function create_vector_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_vector_stores = $wpdb->prefix . 'vector_stores';
    $table_vector_files  = $wpdb->prefix . 'vector_files';

    $sql = "
        CREATE TABLE $table_vector_stores (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            assistant_id VARCHAR(255),
            vector_store_id VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;

        CREATE TABLE $table_vector_files (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            file_id VARCHAR(255) NOT NULL,
            vector_store_id VARCHAR(255),
            file_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function create_active_campaign_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_active_campaign_variables = $wpdb->prefix . 'active_campaign_variables';

    $sql = "
        CREATE TABLE $table_active_campaign_variables (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            api_url VARCHAR(255) NOT NULL,
            api_key VARCHAR(255) NOT NULL,
            assistant_id VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
