<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HW_Cmt_ImpExpCsv_Settings {

	/**
	 * Product Exporter Tool
	 */
	public static function save_settings( ) {
		global $wpdb;

		$ftp_server                             = ! empty( $_POST['ftp_server'] ) ? $_POST['ftp_server'] : '';
		$ftp_user				= ! empty( $_POST['ftp_user'] ) ? $_POST['ftp_user'] : '';
		$ftp_password                           = ! empty( $_POST['ftp_password'] ) ? $_POST['ftp_password'] : '';
		$use_ftps                               = ! empty( $_POST['use_ftps'] ) ? true : false;
		$enable_ftp_ie                          = ! empty( $_POST['enable_ftp_ie'] ) ? true : false;
                
                $auto_export                            = ! empty( $_POST['auto_export'] ) ? $_POST['auto_export'] : 'Disabled';
                $auto_export_start_time                 = ! empty( $_POST['auto_export_start_time'] ) ? $_POST['auto_export_start_time'] : '';
                $auto_export_interval                   = ! empty( $_POST['auto_export_interval'] ) ? $_POST['auto_export_interval'] : '';
                
                $auto_import                            = ! empty( $_POST['auto_import'] ) ? $_POST['auto_import'] : 'Disabled';
                $auto_import_start_time                 = ! empty( $_POST['auto_import_start_time'] ) ? $_POST['auto_import_start_time'] : '';
                $auto_import_interval                   = ! empty( $_POST['auto_import_interval'] ) ? $_POST['auto_import_interval'] : '';
                $auto_import_profile                    = ! empty( $_POST['auto_import_profile'] ) ? $_POST['auto_import_profile'] : '';
                $auto_import_merge                      = ! empty( $_POST['auto_import_merge'] ) ?  true : false;

		$settings				= array();
		$settings[ 'ftp_server' ]		= $ftp_server;
		$settings[ 'ftp_user' ]			= $ftp_user;
		$settings[ 'ftp_password' ]		= $ftp_password;
		$settings[ 'use_ftps' ]			= $use_ftps;
		$settings[ 'enable_ftp_ie' ]            = $enable_ftp_ie;
                
                $settings[ 'auto_export' ]		= $auto_export;
                $settings[ 'auto_export_start_time' ]	= $auto_export_start_time;
                $settings[ 'auto_export_interval' ]	= $auto_export_interval;
                
                $settings[ 'auto_import' ]		= $auto_import;
                $settings[ 'auto_import_start_time' ]	= $auto_import_start_time;
                $settings[ 'auto_import_interval' ]	= $auto_import_interval;
                $settings[ 'auto_import_profile' ]	= $auto_import_profile;
                $settings[ 'auto_import_merge' ]	= $auto_import_merge;
                
                
                $settings_db = get_option( 'woocommerce_'.hw_PR_REV_IMP_EXP_ID.'_settings', null );
                
                $orig_export_start_inverval =  '';
                if(isset($settings_db['auto_export_start_time'])&& isset($settings_db['auto_export_interval'])){
                $orig_export_start_inverval = $settings_db['auto_export_start_time'] . $settings_db['auto_export_interval'];
                }
                
                $orig_import_start_inverval =  '';
                if(isset($settings_db['auto_import_start_time'])&& isset($settings_db['auto_import_interval'])){
                $orig_import_start_inverval = $settings_db['auto_import_start_time'] . $settings_db['auto_import_interval'];
                
                }
 
		update_option( 'woocommerce_'.hw_PR_REV_IMP_EXP_ID.'_settings', $settings );
                // clear scheduled export event in case export interval was changed
                if ($orig_export_start_inverval !== $settings['auto_export_start_time'] . $settings['auto_export_interval']) {
                    // note this resets the next scheduled execution time to the time options were saved + the interval
                    wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_export_products');
                }
		
                // clear scheduled import event in case import interval was changed
                if ($orig_import_start_inverval !== $settings['auto_import_start_time'] . $settings['auto_import_interval']) {
                    // note this resets the next scheduled execution time to the time options were saved + the interval
                    wp_clear_scheduled_hook('hw_cmt_csv_im_ex_auto_import_products');
                }
                
		wp_redirect( admin_url( '/admin.php?page='.hw_cmt_csv_im_ex.'&tab=settings' ) );
		exit;
	}
}