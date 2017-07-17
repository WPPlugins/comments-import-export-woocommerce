<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class HW_Cmt_ImpExpCsv_Cron {

    public $settings;

    public function __construct() {
        add_filter('cron_schedules', array($this, 'hw_auto_export_schedule'));
        add_action('init', array($this, 'hw_new_scheduled_cmt_export'));
        add_action('hw_cmt_csv_im_ex_auto_export_products', array($this, 'hw_scheduled_export_products'));
        $this->settings = get_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', null);
        $this->exports_enabled = FALSE;
        if ($this->settings['auto_export'] === 'Enabled')
            $this->exports_enabled = TRUE;
    }

    public function hw_auto_export_schedule($schedules) {
        if ($this->exports_enabled) {
            $export_interval = $this->settings['auto_export_interval'];
            if ($export_interval) {
                $schedules['export_interval'] = array(
                    'interval' => (int) $export_interval * 60,
                    'display' => sprintf(__('Every %d minutes', 'hw_cmt_import_export'), (int) $export_interval)
                );
            }
        }
        return $schedules;
    }

    public function hw_new_scheduled_cmt_export() {
        if ($this->exports_enabled) {
            if (!wp_next_scheduled('hw_cmt_csv_im_ex_auto_export_products')) {
                $start_time = $this->settings['auto_export_start_time'];
                $current_time = current_time('timestamp');
                if ($start_time) {
                    if ($current_time > strtotime('today ' . $start_time, $current_time)) {
                        $start_timestamp = strtotime('tomorrow ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    } else {
                        $start_timestamp = strtotime('today ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    }
                } else {
                    $export_interval = $this->settings['auto_export_interval'];
                    $start_timestamp = strtotime("now +{$export_interval} minutes");
                }
                wp_schedule_event($start_timestamp, 'export_interval', 'hw_cmt_csv_im_ex_auto_export_products');
            }
        }
    }

    public function hw_scheduled_export_products() {
        include_once( 'exporter/class-hf_cmt_impexpcsv-exporter.php' );
        HW_Cmt_ImpExpCsv_Exporter::do_export();
    }

    public function clear_hw_scheduled_cmt_export() {
        wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_export_products');
    }

}