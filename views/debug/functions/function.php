<?php
if (!defined('ABSPATH')) {
     exit();
}
/**
 * read file content
 * 
 * @param type $fileName
 * @return boolean
 */
function debug_file_read($fileName) {
    $filePath = get_home_path() . $fileName;
    if (file_exists($filePath)) {
        $file = fopen($filePath, "r");
        $responce = '';
        fseek($file, -1048576, SEEK_END);
        while (!feof($file)) {
            $responce .= fgets($file);
        }

        fclose($file);
        return $responce;
    }
    return false;
}

/**
 * write file content
 * 
 * @param type $content
 * @param type $fileName
 * @return type
 */
function debug_file_write($content, $fileName) {
    $output = error_log('/*test*/', '3', get_home_path() . $fileName);
    if ($output) {
        unlink(get_home_path() . $fileName);
        error_log($content, '3', get_home_path() . $fileName);
        chmod(get_home_path() . $fileName, 0600);
    }
    return $output;
}

/**
 * unlink debug.log file
 * 
 * @return type
 */
function debug_clearlog() {
    $filePath = get_home_path() . 'wp-content/debug.log';
    $result['class'] = 'error';
    $result['message'] = esc_html_e('File debug.log not Removed.', 'debug');
    if (file_exists($filePath)) {
        $status = unlink($filePath);
        if ($status) {
            $result['class'] = 'updated';
            $result['message'] = esc_html_e('debug.log file Remove successfully.', 'debug');
        }
    }
    return $result;
}

/**
 * save debug setting from UI
 */
function debug_save_setting() {
        $nonce = isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])?trim($_REQUEST['_wpnonce']):"";
        if (wp_verify_nonce( $nonce, '_wpnonce' ) ) {
            if (isset($_POST['debugsetting']) && !empty($_POST['debugsetting'])) {
                $is_update = 1;
                $enable_notification = isset($_POST['enable_notification']) ? trim($_POST['enable_notification']) : '0';
                $email_notification = isset($_POST['email_notification']) ? trim($_POST['email_notification']) : '';
                $error_reporting = isset($_POST['error_reporting']) ? trim($_POST['error_reporting']) : '0';
                $error_log = isset($_POST['error_log']) ? trim($_POST['error_log']) : '0';
                $display_error = isset($_POST['display_error']) ? trim($_POST['display_error']) : '0';
                $error_script = isset($_POST['error_script']) ? trim($_POST['error_script']) : '0';
                $error_savequery = isset($_POST['error_savequery']) ? trim($_POST['error_savequery']) : '0';
                if ($enable_notification == '1') {
                    $error_reporting = $error_log = $error_script = $error_savequery = '1';
                    if (!is_email($email_notification)) {
                        $is_update = 0;
                    }
                }
                $fileName = 'wp-config.php';
                $fileContent = debug_file_read($fileName);
                $fileContent = debug_add_option($error_reporting, 'WP_DEBUG', $fileContent);
                $fileContent = debug_add_option($error_log, 'WP_DEBUG_LOG', $fileContent);
                $fileContent = debug_add_option($display_error, 'WP_DEBUG_DISPLAY', $fileContent);
                $fileContent = debug_add_option($error_script, 'SCRIPT_DEBUG', $fileContent);
                $fileContent = debug_add_option($error_savequery, 'SAVEQUERIES', $fileContent);

                if (debug_file_write($fileContent, $fileName)) {
                    if ($is_update == 0) {?>
                        <div class="error settings-error"> 
                            <p><strong><?php esc_html_e("Please enter Email Address.", 'debug'); ?></strong></p>
                        </div>
                        <?php
                    }else{
                        update_option('debug_notification', array('enable' => $enable_notification, 'email' => $email_notification));
                        ?>
                        <div class="updated settings-error"> 
                            <p><strong><?php esc_html_e("Setting saved successfully.", 'debug'); ?></strong></p>
                        </div>
                        <div class="notice notice-info is-dismissible"><p><?php esc_html_e("The plugin will automatically reload in 5 seconds.", 'debug'); ?></p></div>
                        <script>setTimeout(()=>{location.reload();},5000);</script>
                        <?php
                        exit();
                    }
                } else {?>
                    <div class="error settings-error">
                    <p><strong><?php esc_html_e('Your wp-config file not updated. Copy and paste following code in your wp-config.php file.', 'debug');?></strong></p>
                    </div>
                    <textarea style="width:100%; height:400px"><?php esc_html_e($fileContent);?></textarea>
        <?php }
        }                
    }
}

/**
 * modify content of wp-config.php file and add debug variable
 * 
 * @param type $option
 * @param type $define
 * @param type $fileContent
 * @return type
 */
function debug_add_option($option, $define, $fileContent) {
    $pattern = "/define\s*\(\s*[\'\"]" . preg_quote($define, '/') . "[\'\"]\s*,\s*(true|false)\s*\)\s*;/i";
    $replacement = ($option == 1) ? "define('" . $define . "', true);" : "define('" . $define . "', false);";
    $fileContent = preg_replace($pattern, $replacement, $fileContent, -1, $count);
    if ($count == 0) {
        $fileContent = str_replace('$table_prefix', $replacement. "\r\n" .'$table_prefix', $fileContent);
    }
    return $fileContent;
}

/**
 * Add thank you link on admin pages
 */
function debug_footer_link() {?>
    <script>
        window.onload = function() {
            debug_options_setting();
            var divElement = document.createElement('div');
            divElement.setAttribute('class', 'alignright');
            var textNode = document.createTextNode("Thank you for Debugging your wordpress with ");
            var linkElement = document.createElement('a');
            linkElement.setAttribute('href', 'https://www.soninow.com');
            linkElement.setAttribute('target', '_blank');
            linkElement.textContent = 'SoniNow';
            divElement.appendChild(textNode);
            divElement.appendChild(linkElement);
            document.getElementById('footer-left').appendChild(divElement);
            document.getElementById('footer-upgrade').textContent = 'Current Version <?php esc_html_e(DEBUG_PLUGIN_VERSION); ?>';
            var debugLogElement = document.getElementById('debug-log');
            if (debugLogElement !== null) { 
                debugLogElement.scrollTop = debugLogElement.scrollHeight;
            }
            var enableNotificationCheckbox = document.getElementById('enable_notification');
            if (enableNotificationCheckbox !== null) {
                enableNotificationCheckbox.addEventListener('click', debug_options_setting);
            }
        };
        function debug_options_setting() {
            var enableNotificationCheckbox = document.getElementById('enable_notification');
            var emailNotificationElements = document.querySelectorAll('.emailnotification');
            var noEmailNotificationElements = document.querySelectorAll('.noemailnotification');

            if (enableNotificationCheckbox.checked) {
                emailNotificationElements.forEach(function(element) {
                    element.style.display = 'block';
                });
                noEmailNotificationElements.forEach(function(element) {
                    element.style.display = 'none';
                });
            } else {
                emailNotificationElements.forEach(function(element) {
                    element.style.display = 'none';
                });
                noEmailNotificationElements.forEach(function(element) {
                    element.style.display = 'block';
                });
            }
        }
    </script>
    <?php
}

/**
 * 
 * @param type $path
 * 
 * Allow a file to download
 */
function debug_file_download($path) {
    $content = debug_file_read($path);
    header('Content-type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename="' . basename($path) . '"', true);
    header("Pragma: no-cache");
    header("Expires: 0");
    esc_html_e($content);
    exit();
}

/**
 * Allow to download debug.log file for make report.
 */
function debug_handle_file_download(){
    $nonce = isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])?trim($_REQUEST['_wpnonce']):"";
    if (wp_verify_nonce( $nonce, '_wpnonce' ) ) {
        if(isset($_POST['downloadlog'])){
            debug_file_download('wp-content/debug.log');
        }else if(isset($_POST['downloadconfig'])){
            debug_file_download('wp-config.php');
        }
    }
}

add_action('admin_init', 'debug_handle_file_download');

/**
 * 
 * @return type
 */
function debug_get_options() {
    return get_option('debug_notification');
}

/**
 * 
 * @param type $array
 * @return string
 */
function debug_create_table_format($array) {
    if (is_array($array) && count($array) > 0) {
        $errorContent = "<table border = 1><tr><td>";
        foreach ($array as $key => $val) {
            $errorContent .= $key . "</td><td>";
            if (is_array($val) && count($val) > 0) {
                $errorContent .= debug_create_table_format(json_decode(json_encode($val), true));
            } else {
                $errorContent .= print_r($val, true);
            }
        }
        $errorContent .= "</td></tr></table>";
        return $errorContent;
    }
    return '';
}

/**
 * 
 * @param type $errorNumber
 * @param type $errorString
 * @param type $errorFile
 * @param type $errorLine
 * @param type $errorContext
 */
function debug_error_handler($errorNumber, $errorString, $errorFile, $errorLine, $errorContext) {
    $debug_setting = debug_get_options();

    $emailMessage = '<h2>' . esc_html_e('Error Reporting on', 'debug') . ' :- </h2>[' . date("Y-m-d h:i:s", time()) . ']<br>';
    $emailMessage .= '<h2>' . esc_html_e('Error Number', 'debug') . ' :- </h2>' . print_r($errorNumber, true) . '<br>';
    $emailMessage .= '<h2>' . esc_html_e('Error String', 'debug') . ' :- </h2>' . print_r($errorString, true) . '<br>';
    $emailMessage .= '<h2>' . esc_html_e('Error File', 'debug') . ' :- </h2>' . print_r($errorFile, true) . '<br>';
    $emailMessage .= '<h2>' . esc_html_e('Error Line', 'debug') . ' :- </h2>' . print_r($errorLine, true) . '<br>';
    $emailMessage .= '<h2>' . esc_html_e('Error Context', 'debug') . ' :- </h2>' . debug_create_table_format($errorContext);

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    wp_mail($debug_setting['email'], 'Error Reporting from <b>' . get_bloginfo('name') . '</b> with the help of <a href="http://www.soninow.com" target=_blank">www.soninow.com</a>', $emailMessage, $headers);
}
