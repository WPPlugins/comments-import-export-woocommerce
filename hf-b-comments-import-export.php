<?php

/*
  Plugin Name: WordPress Comments Import & Export (BASIC)
  Plugin URI: https://www.xadapter.com/product/wordpress-woocommerce-comments-import-export-plugin/
  Description: Import and Export WordPress Comments From and To your WordPress Site.
  Author: HikeForce
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
/**
 * Check if WooCommerce is active
 */
//print_r(get_option( 'active_plugins' ));

require_once(ABSPATH."wp-admin/includes/plugin.php");
// Change the Pack IF BASIC  mention switch('BASIC') ELSE mention switch('PREMIUM')

// Enter your plugin unique option name below $option_name variable
$option_name='cmt_ex_im_option';
if(get_option($option_name)== 'premium')
{
    add_action('admin_notices','eh_wc_admin_notices', 99);
    deactivate_plugins(plugin_basename(__FILE__));
    function eh_wc_admin_notices()
    {
        is_admin() && add_filter('gettext', function($translated_text, $untranslated_text, $domain)
        {
            $old = array(
                "Plugin <strong>activated</strong>.",
                "Selected plugins <strong>activated</strong>."
            );
            $error_text="PREMIUM Version of this Plugin Installed. Please uninstall the PREMIUM Version before activating BASIC.";
            $new = "<span style='color:red'>".$error_text."</span>";
            if (in_array($untranslated_text, $old, true)) {
                $translated_text = $new;
            }
            return $translated_text;
        }, 99, 3);
    }
    return;
}
else
{
    update_option($option_name, 'basic');	
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
                    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'hw_plugin_action_links'));
                    add_action('init', array($this, 'load_plugin_textdomain'));
                    add_action('init', array($this, 'catch_export_request'), 20);
                    add_action('admin_init', array($this, 'register_importers'));

                    include_once( 'includes/class-hf_cmt_impexpcsv-admin-screen.php' );
                    include_once( 'includes/importer/class-hf_cmt_impexpcsv-importer.php' );
                    if (defined('DOING_AJAX')) {
                        include_once( 'includes/class-hf_cmt_impexpcsv-ajax-handler.php' );
                    }
                  }

                public function hw_plugin_action_links($links) {
                    $plugin_links = array(
                        '<a href="' . admin_url('admin.php?page=hw_cmt_csv_im_ex') . '">' . __('Import Export', 'hw_cmt_import_export') . '</a>',
                        '<a href="https://www.xadapter.com/support/forum/wordpress-woocommerce-comments-import-export-plugin/">' . __('Support', 'hw_cmt_import_export') . '</a>',
                    );
                    return array_merge($plugin_links, $links);
                }
                
                function hw_product_comments_ie_admin_notice() {
                    global $pagenow;
                    global $post;

                    if (!isset($_GET["hw_product_Comment_ie_msg"]) && empty($_GET["hw_product_Comment_ie_msg"])) {
                        return;
                    }

                    $wf_bbpress_ie_msg = $_GET["hf_bb_ie_msg"];

                    switch ($wf_bbpress_ie_msg) {
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
    }    
