<?php
if (!defined('ABSPATH')) {
     exit();
}?>
<style>
    #debug-log{
        max-width: 100%;
        padding: 10px;
        word-wrap: break-word;
        background: black;
        color: #fff;
        border-radius: 5px;
        height: 400px;
        overflow-y: auto;
    }
</style>
<div class="wrap">
    <h2><?php esc_html_e('Debug Log','debug');?></h2>
    <?php
    if (isset($_POST['clearlog'])) {
        $nonce = isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])?trim($_REQUEST['_wpnonce']):"";
        if ( ! wp_verify_nonce( $nonce, '_wpnonce' ) ) {
            die( esc_html_e( 'Sorry, you are not allowed to access this page.', 'debug' ) ); 
        } else {
            $responce = debug_clearlog()
            ?>
            <div class="<?php esc_html_e($responce['class']);?> settings-error"> 
                <p><strong><?php esc_html_e($responce['message']);?></strong></p>
            </div>
            <?php
        }
    }
    $content = debug_file_read('wp-content/debug.log');
    if($content !== false){?>
        <form method="post" action="">
        <p style="float: right;margin-top: -32px;"><input type="submit" name="clearlog" id="clearlog" class="button button-primary" value="<?php esc_html_e('Clear Log','debug');?>">
        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php esc_html_e(wp_create_nonce( '_wpnonce' ));?>" />
        <input type="submit" name="downloadlog" id="downloadlog" class="button button-primary" value="<?php esc_html_e('Download Log','debug');?>">
        </p></form>
        <pre id="debug-log"><?php esc_html_e($content);?></pre>
    <?php }else{?>
        <div class="notice settings-error">
        <p><strong><?php esc_html_e('No Log File Found','debug');?></strong></p>
        </div>
    <?php }
    debug_footer_link();
    ?>
</div>
