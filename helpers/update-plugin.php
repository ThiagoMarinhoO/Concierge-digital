<?php

function check_github_plugin_update($transient) {
    $github_repo = 'ThiagoMarinhoO/Concierge-digital';
    
    $url = "https://api.github.com/repos/$github_repo/releases/latest";
    
    $response = wp_remote_get($url);
    
    if (!is_wp_error($response) && isset($response['body'])) {
        $release_data = json_decode($response['body']);

        if (isset($release_data->tag_name)) {
            $plugin_version = '2.3';

            if (version_compare($plugin_version, $release_data->tag_name, '<')) {
                $transient->checked[plugin_basename(__FILE__)] = $release_data->tag_name;
            }
        }
    }
    
    return $transient;
}
add_filter('site_transient_update_plugins', 'check_github_plugin_update');

function github_plugin_update($updater) {
    $repo = 'ThiagoMarinhoO/Concierge-digital';

    $url = "https://github.com/$repo/archive/refs/heads/main.zip";
    
    $updater->add_zip_file($url);
    
    return $updater;
}
add_filter('plugin_api', 'github_plugin_update');
