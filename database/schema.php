<?php
function create_vector_tables()
{
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

function create_organizations_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_organizations = $wpdb->prefix . 'organizations';

    $sql = "
        CREATE TABLE $table_organizations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            owner_user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function alter_users_table()
{
    global $wpdb;
    $table_users = $wpdb->prefix . 'users';

    // Verifique se a coluna já existe para evitar erros na primeira vez
    $column_exists = $wpdb->query("SHOW COLUMNS FROM `$table_users` LIKE 'charlie_organization_id'");

    if ($column_exists == 0) {
        $sql = "
            ALTER TABLE $table_users
            ADD organization_id BIGINT UNSIGNED NULL;
        ";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // Para ALTER TABLE simples no core do WP, $wpdb->query pode ser mais confiável
        $wpdb->query($sql);
    }
}

function alter_assistants_table()
{
    global $wpdb;
    $table_users = $wpdb->prefix . 'chatbot';

    // Verifique se a coluna já existe para evitar erros na primeira vez
    $column_exists = $wpdb->query("SHOW COLUMNS FROM `$table_users` LIKE 'organization_id'");

    if ($column_exists == 0) {
        $sql = "
            ALTER TABLE $table_users
            ADD organization_id BIGINT UNSIGNED NULL;
        ";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // Para ALTER TABLE simples no core do WP, $wpdb->query pode ser mais confiável
        $wpdb->query($sql);
    }
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