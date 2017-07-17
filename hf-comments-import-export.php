<?php

/*
  Plugin Name: WordPress Comments Import & Export
  Plugin URI: https://wordpress.org/plugins/comments-import-export-woocommerce/
  Description: Import and Export WordPress Comments From and To your WooCommerce Store.
  Author: XAdapter
  Author URI: http://www.xadapter.com/vendor/hikeforce/
  Version: 2.0.1
  Text Domain: hw_cmt_import_export
 */

  if (!defined('ABSPATH') || !is_admin()) {
    return;
}
if (!defined('HW_CMT_IMP_EXP_ID')) {

    define("HW_CMT_IMP_EXP_ID", "hw_cmt_imp_exp");
}

if (!defined('HW_CMT_CSV_IM_EX')) {

    define("HW_CMT_CSV_IM_EX", "hw_cmt_csv_im_ex");
}

//print_r(get_option( 'active_plugins' ));

require_once(ABSPATH."wp-admin/includes/plugin.php");
// Change the Pack IF BASIC  mention switch('BASIC') ELSE mention switch('PREMIUM')

    register_deactivation_hook(__FILE__, 'eh_deactivate_work');
    // Enter your plugin unique option name below update_option function
    function eh_deactivate_work()
    {
        update_option('cmt_ex_im_option', '');
    }
    
    if (!class_exists('HW_Product_Comments_Import_Export_CSV')) :

            /**
             * Main CSV Import class
             */
        class HW_Product_Comments_Import_Export_CSV {

            public $cron;
            public $cron_import;

                /**
                 * Constructor
                 */
                public function __construct() {
                    define('HW_CMT_ImpExpCsv_FILE', __FILE__);
                    if (is_admin()) {
                        add_action('admin_notices', array($this, 'hw_product_comments_ie_admin_notice'), 15);
                    }

                    add_filter('woocommerce_screen_ids', array($this, 'woocommerce_screen_ids'));
                    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'hw_plugin_action_links'));
                    add_action('init', array($this, 'load_plugin_textdomain'));
                    add_action('init', array($this, 'catch_export_request'), 20);
                    add_action('init', array($this, 'catch_save_settings'), 20);
                    add_action('admin_init', array($this, 'register_importers'));

                    include_once( 'includes/class-hf_cmt_impexpcsv-system-status-tools.php' );
                    include_once( 'includes/class-hf_cmt_impexpcsv-admin-screen.php' );
                    include_once( 'includes/importer/class-hf_cmt_impexpcsv-importer.php' );

                    require_once( 'includes/class-hf_cmt_impexpcsv-cron.php' );
                    $this->cron = new HW_Cmt_ImpExpCsv_Cron();
                    register_activation_hook(__FILE__, array($this->cron, 'hw_new_scheduled_cmt_export'));
                    register_deactivation_hook(__FILE__, array($this->cron, 'clear_hw_scheduled_cmt_export'));


                    if (defined('DOING_AJAX')) {
                        include_once( 'includes/class-hf_cmt_impexpcsv-ajax-handler.php' );
                    }

                    require_once( 'includes/class-hf_cmt_impexpcsv-import-cron.php' );
                    $this->cron_import = new HW_Cmt_ImpExpCsv_ImportCron();
                    register_activation_hook(__FILE__, array($this->cron_import, 'hw_new_scheduled_cmt_import'));
                    register_deactivation_hook(__FILE__, array($this->cron_import, 'clear_hw_scheduled_cmt_import'));
                }

                public function hw_plugin_action_links($links) {
                    $plugin_links = array(
                        '<a href="' . admin_url('admin.php?page=hw_cmt_csv_im_ex') . '">' . __('Import Export', 'hw_cmt_import_export') . '</a>',
                        '<a href="https://www.xadapter.com/category/product/wordpress-woocommerce-comments-import-export-plugin/" target="_blank">' . __('Documentation', 'hw_cmt_import_export') . '</a>',
                        '<a href="https://wordpress.org/support/plugin/comments-import-export-woocommerce" target="_blank">' . __('Support', 'hw_cmt_import_export') . '</a>','<a href="https://www.xadapter.com/" target="_blank">' . __('Premium Plugins', 'hf_bb_import_export') . '</a>'

                        );
                    return array_merge($plugin_links, $links);
                }

                function hw_product_comments_ie_admin_notice() {
                    global $pagenow;
                    global $post;

                    if (!isset($_GET["hw_product_Comment_ie_msg"]) && empty($_GET["hw_product_Comment_ie_msg"])) {
                        return;
                    }

                    $wf_product_Comment_ie_msg = $_GET["hw_product_Comment_ie_msg"];

                    switch ($wf_product_Comment_ie_msg) {
                        case "1":
                        echo '<div class="update"><p>' . __('Successfully uploaded via FTP.', 'hw_cmt_import_export') . '</p></div>';
                        break;
                        case "2":
                        echo '<div class="error"><p>' . __('Error while uploading via FTP.', 'hw_cmt_import_export') . '</p></div>';
                        break;
                        case "3":
                        echo '<div class="error"><p>' . __('Please choose the file in CSV format either using Method 1 or Method 2.', 'hw_cmt_import_export') . '</p></div>';
                        break;
                    }
                }

                /**
                 * Add screen ID
                 */
                public function woocommerce_screen_ids($ids) {
                    $ids[] = 'admin'; // For import screen
                    return $ids;
                }

                /**
                 * Handle localisation
                 */
                public function load_plugin_textdomain() {
                    load_plugin_textdomain('hw_cmt_import_export', false, dirname(plugin_basename(__FILE__)) . '/lang/');
                }

                /**
                 * Catches an export request and exports the data. This class is only loaded in admin.
                 */
                public function catch_export_request() {
                    if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'hw_cmt_csv_im_ex') {
                        switch ($_GET['action']) {
                            case "export" :
                            $user_ok = $this->hf_user_permission();
                            if ($user_ok) {
                                include_once( 'includes/exporter/class-hf_cmt_impexpcsv-exporter.php' );
                                HW_Cmt_ImpExpCsv_Exporter::do_export();
                            } else {
                                wp_redirect(wp_login_url());
                            }
                            break;
                        }
                    }
                }

                public function catch_save_settings() {
                    if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'hw_cmt_csv_im_ex') {
                        switch ($_GET['action']) {
                            case "settings" :
                            include_once( 'includes/settings/class-hf_cmt_impexpcsv-settings.php' );
                            HW_Cmt_ImpExpCsv_Settings::save_settings();
                            break;
                        }
                    }
                }

                /**
                 * Register importers for use
                 */
                public function register_importers() {
                    register_importer('product_comments_csv', 'WooCommerce Product Comments (CSV)', __('Import <strong>product Comments</strong> to your store via a csv file.', 'hw_cmt_import_export'), 'HW_Cmt_ImpExpCsv_Importer::product_importer');
                    register_importer('product_comments_csv_cron', 'WooCommerce Product Comments (CSV)', __('Cron Import <strong>product Comments</strong> to your store via a csv file.', 'hw_cmt_import_export'), 'WF_Cmt_ImpExpCsv_ImportCron::product_importer');
                }

                private function hf_user_permission() {
                    // Check if user has rights to export
                    $current_user = wp_get_current_user();
                    $user_ok = false;
                    $wf_roles = apply_filters('hw_user_permission_roles', array('administrator', 'shop_manager'));
                    if ($current_user instanceof WP_User) {
                        $can_users = array_intersect($wf_roles, $current_user->roles);
                        if (!empty($can_users)) {
                            $user_ok = true;
                        }
                    }
                    return $user_ok;
                    
                    
                }

            }

            endif;

            new HW_Product_Comments_Import_Export_CSV();
        
