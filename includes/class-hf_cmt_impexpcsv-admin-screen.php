<?php
if (!defined('ABSPATH')) {
    exit;
}

class HW_Cmt_ImpExpCsv_Admin_Screen {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_print_styles', array($this, 'admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));

        add_action('bulk_actions-edit-comments', array($this, 'add_product_reviews_bulk_actions'));
        add_action('admin_action_download_to_cmtiew_csv_hf', array($this, 'process_product_reviews_bulk_actions'));

        add_filter('manage_edit-comments_columns', array($this, 'custom_comment_columns'));
        add_filter('manage_comments_custom_column', array($this, 'custom_comment_column_data'), 10, 2);

        if (is_admin()) {
            add_action('wp_ajax_comment_export_to_csv_single', array($this, 'process_ajax_export_single_comment'));
        }
    }

    public function custom_comment_columns($columns) {
        $columns['comment_export_to_csv'] = __('Export');
        return $columns;
    }

    public function custom_comment_column_data($column, $comment_ID) {
        if ('comment_export_to_csv' == $column) {
            $action_general = 'download_comment_csv';
            $url_general = wp_nonce_url(admin_url('admin-ajax.php?action=comment_export_to_csv_single&comment_ID=' . $comment_ID), 'hw_cmt_import_export');
            $name_general = __('Download to CSV', 'hw_cmt_import_export');
            printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', $action_general, esc_url($url_general), $name_general, $name_general);
        }
    }

    public function process_ajax_export_single_comment() {
        if (!is_admin()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'hw_csv_import_export'));
        }

        $comment_ID = !empty($_GET['comment_ID']) ? absint($_GET['comment_ID']) : '';
        if (!$comment_ID) {
            die;
        }
        $comment_IDs = array(0 => $comment_ID);
        include_once( 'exporter/class-hw_cmt_impexpcsv-exporter.php' );
        HW_Cmt_ImpExpCsv_Exporter::do_export($comment_IDs);
    }

    /**
     * Notices in admin
     */
    public function admin_notices() {
        if (!function_exists('mb_detect_encoding')) {
            echo '<div class="error"><p>' . __('Product CSV Import Export requires the function <code>mb_detect_encoding</code> to import and export CSV files. Please ask your hosting provider to enable this function.', 'hw_cmt_import_export') . '</p></div>';
        }
    }

    /**
     * Admin Menu
     */
    public function admin_menu() {
        // $page = add_menu_page( __('Comments Im-Ex', 'hw_cmt_import_export'), __('Comments Im-Ex', 'hw_cmt_import_export'), apply_filters('product_reviews_csv_product_role', 'read'), 'hw_cmt_csv_im_ex', array($this, 'output'),'dashicons-admin-comments',$position='25');
        if (function_exists('WC')) {
            $page = add_submenu_page('woocommerce', __('Comments Im-Ex', 'hw_cmt_import_export'), __('Comments Im-Ex', 'hw_cmt_import_export'), apply_filters('product_reviews_csv_product_role', 'read'), 'hw_cmt_csv_im_ex', array($this, 'output'));
        } else {
            $page = add_comments_page(__('Comments Im-Ex', 'hw_cmt_import_export'), __('Comments Im-Ex', 'hw_cmt_import_export'), apply_filters('product_reviews_csv_product_role', 'read'), 'hw_cmt_csv_im_ex', array($this, 'output'));
        }
    }

    /**
     * Get WC Plugin path without fail on any version
     */
    public static function hw_get_wc_path() {
        if (function_exists('WC')) {
            $wc_path = WC()->plugin_url();
        } else {
            $wc_path = plugins_url() . '/comments-import-export-woocommerce';
        }
        return $wc_path;
    }

    /**
     * Admin Scripts
     */
    public function admin_scripts() {

        $wc_path = self::hw_get_wc_path();
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_style('woocommerce_admin_styles', $wc_path . '/assets/css/admin.css');
        wp_enqueue_style('woocommerce-product-csv-importer1', plugins_url(basename(plugin_dir_path(HW_CMT_ImpExpCsv_FILE)) . '/styles/wf-style.css', basename(__FILE__)), '', '1.0.0', 'screen');
        wp_enqueue_style('woocommerce-product-csv-importer3', plugins_url(basename(plugin_dir_path(HW_CMT_ImpExpCsv_FILE)) . '/styles/jquery-ui.css', basename(__FILE__)), '', '1.0.0', 'screen');

        wp_enqueue_script('woocommerce-product-csv-importer2', plugins_url(basename(plugin_dir_path(HW_CMT_ImpExpCsv_FILE)) . '/js/product-rev-csv-import-export-for-woocommerce.min.js', basename(__FILE__)), '', '1.0.0', 'screen');
        wp_enqueue_script('jquery-ui-datepicker');
    }

    /**
     * Admin Screen output
     */
    public function output() {
        include('market.php' );
        $tab = 'import';
        if (!empty($_GET['tab'])) {
            if ($_GET['tab'] == 'export') {
                $tab = 'export';
            } else if ($_GET['tab'] == 'settings') {
                $tab = 'settings';
            }
        }

        include( 'views/html-hf-admin-screen.php' );
    }

    /**
     * Product review list page bulk export action add to action list
     * 
     */
    public function add_product_reviews_bulk_actions($action) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                var $downloadToCSV = $('<option>').val('download_to_cmt_csv_hf').text('<?php _e('Download as CSV', 'hw_cmt_import_export') ?>');
                $('select[name^="action"]').append($downloadToCSV);
            });
        </script>
        <?php
        return $action;
    }

    /**
     * Product page bulk export action
     * 
     */
    public function process_product_reviews_bulk_actions() {
        //wp_die( '<pre>' . print_r( $_REQUEST ) . '</pre>' ); 
        $action = $_REQUEST['action'];
        if (!in_array($action, array('download_to_cmtiew_csv_hf')))
            return;

        if (isset($_REQUEST['delete_comments'])) {
            $cmt_ids = array_map('absint', $_REQUEST['delete_comments']);
        }
        if (empty($cmt_ids)) {
            return;
        }
        // give an unlimited timeout if possible
        @set_time_limit(0);

        if ($action == 'download_to_cmtiew_csv_hf') {
            include_once( 'exporter/class-hf_cmt_impexpcsv-exporter.php' );
            HW_Cmt_ImpExpCsv_Exporter::do_export($cmt_ids);
        }
    }

    /**
     * Admin page for importing
     */
    public function admin_import_page() {
        include( 'views/html-hf-getting-started.php' );
        include( 'views/import/html-hf-import-product-comments.php' );
        $post_columns = include( 'exporter/data/data-hf-post-columns.php' );
        if (in_array('woodiscuz-woocommerce-comments/wpc.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            include( 'views/export/html-hf-export-WordPress-Comments.php' );
        } else {
            include( 'views/export/html-hf-export-WordPress-Comments-normal.php' );
        }
    }

    /**
     * Admin Page for exporting
     */
    public function admin_export_page() {
        $post_columns = include( 'exporter/data/data-hf-post-columns.php' );

        if (in_array('woodiscuz-woocommerce-comments/wpc.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            include( 'views/export/html-hf-export-WordPress-Comments.php' );
        } else {
            include( 'views/export/html-hf-export-WordPress-Comments-normal.php' );
        }
    }

    /**
     * Admin Page for settings
     */
    public function admin_settings_page() {
        include( 'views/settings/html-hf-settings-products.php' );
    }

    public function admin_backup_restore() {
        include('views/settings/html-hf-backup.php');
    }

}

new HW_Cmt_ImpExpCsv_Admin_Screen();
