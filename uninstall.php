<?php

//if uninstall not called from WordPress exit
if (!defined( 'WP_UNINSTALL_PLUGIN' )) {
    exit();
}

delete_post_meta_by_key('gil_js_file_selector_file');
delete_post_meta_by_key('gil_js_file_selector_functions');
?>