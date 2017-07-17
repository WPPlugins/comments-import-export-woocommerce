<?php
$settings = get_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', null);
$ftp_server = isset($settings['ftp_server']) ? $settings['ftp_server'] : '';
$ftp_user = isset($settings['ftp_user']) ? $settings['ftp_user'] : '';
$ftp_password = isset($settings['ftp_password']) ? $settings['ftp_password'] : '';
$use_ftps = isset($settings['use_ftps']) ? $settings['use_ftps'] : '';
$enable_ftp_ie = isset($settings['enable_ftp_ie']) ? $settings['enable_ftp_ie'] : '';

$auto_export = isset($settings['auto_export']) ? $settings['auto_export'] : 'Disabled';
$auto_export_start_time = isset($settings['auto_export_start_time']) ? $settings['auto_export_start_time'] : '';
$auto_export_interval = isset($settings['auto_export_interval']) ? $settings['auto_export_interval'] : '';

$auto_import = isset($settings['auto_import']) ? $settings['auto_import'] : 'Disabled';
$auto_import_start_time = isset($settings['auto_import_start_time']) ? $settings['auto_import_start_time'] : '';
$auto_import_interval = isset($settings['auto_import_interval']) ? $settings['auto_import_interval'] : '';
$auto_import_profile = isset($settings['auto_import_profile']) ? $settings['auto_import_profile'] : '';
$auto_import_merge = isset($settings['auto_import_merge']) ? $settings['auto_import_merge'] : 0;
if(function_exists('WC'))
{
$timefor = wc_time_format();
$datefor = wc_date_format();
}
else
{
$timefor = apply_filters( 'woocommerce_time_format', get_option( 'time_format' ) );
$datefor = apply_filters( 'woocommerce_date_format', get_option( 'date_format' ) );
}
wp_localize_script('woocommerce-product-csv-importer', 'woocommerce_product_csv_importer_params', array('auto_export' => $auto_export, 'auto_import' => $auto_import));
if ($scheduled_timestamp = wp_next_scheduled('hw_cmt_csv_im_ex_auto_export_products')) {
    $scheduled_desc = sprintf(__('The next export is scheduled on <code>%s</code>', 'hw_cmt_import_export'), get_date_from_gmt(date('Y-m-d H:i:s', $scheduled_timestamp), $datefor . ' ' . $timefor));
} else {
    $scheduled_desc = __('There is no export scheduled.', 'hw_cmt_import_export');
}
if ($scheduled_import_timestamp = wp_next_scheduled('hw_cmt_csv_im_ex_auto_import_products')) {
    $scheduled_import_desc = sprintf(__('The next import is scheduled on <code>%s</code>', 'hw_cmt_import_export'), get_date_from_gmt(date('Y-m-d H:i:s', $scheduled_import_timestamp), $datefor . ' ' . $timefor));
} else {
    $scheduled_import_desc = __('There is no import scheduled.', 'hw_cmt_import_export');
}

?>
<div class="tool-box">
    <form action="<?php echo admin_url('admin.php?page=hw_cmt_csv_im_ex&action=settings'); ?>" method="post">
        <table class="form-table">
            <tr>
                <th>
                    <h3 class="title"><?php _e('FTP Settings for Export', 'hw_cmt_import_export'); ?></h3>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="enable_ftp_ie"><?php _e('Enable FTP', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="enable_ftp_ie" id="enable_ftp_ie" class="checkbox" <?php checked($enable_ftp_ie, 1); ?> />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ftp_server"><?php _e('FTP Server Host/IP', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="ftp_server" id="ftp_server" placeholder="<?php _e('XXX.XXX.XXX.XXX', 'hw_cmt_import_export'); ?>" value="<?php echo $ftp_server; ?>" class="input-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ftp_user"><?php _e('FTP User Name', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="ftp_user" id="ftp_user"  value="<?php echo $ftp_user; ?>" class="input-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ftp_password"><?php _e('FTP Password', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <input type="password" name="ftp_password" id="ftp_password"  value="<?php echo $ftp_password; ?>" class="input-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="use_ftps"><?php _e('Use FTPS', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="use_ftps" id="use_ftps" class="checkbox" <?php checked($use_ftps, 1); ?> />
                </td>
            </tr>


            <tr>
                <th>
                    <label for="auto_export"><?php _e('Automatically Export WordPress Comments', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <select class="" style="" id="auto_export" name="auto_export">
                        <option <?php if ($auto_export === 'Disabled') echo 'selected'; ?> value="Disabled"><?php _e('Disabled', 'hw_cmt_import_export'); ?></option>
                        <option <?php if ($auto_export === 'Enabled') echo 'selected'; ?> value="Enabled"><?php _e('Enabled', 'hw_cmt_import_export'); ?></option>
                    </select>
                </td>
            </tr>
            <tbody class="export_section">
                <tr>
                    <th>
                        <label for="auto_export_start_time"><?php _e('Export Start Time', 'hw_cmt_import_export'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_export_start_time" id="auto_export_start_time"  value="<?php echo $auto_export_start_time; ?>"/>
                        <span class="description"><?php echo sprintf(__('Local time is <code>%s</code>.', 'hw_cmt_import_export'), date_i18n($timefor)) . ' ' . $scheduled_desc; ?></span>
                        <br/>
                        <span class="description"><?php _e('<code>Enter like 6:18pm or 12:27am</code>', 'hw_cmt_import_export'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_export_interval"><?php _e('Export Interval [ Minutes ]', 'hw_cmt_import_export'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_export_interval" id="auto_export_interval"  value="<?php echo $auto_export_interval; ?>"  />
                    </td>
                </tr>
            </tbody>





            <tr>
                <th>
                    <label for="auto_import"><?php _e('Automatically Import WordPress Comments', 'hw_cmt_import_export'); ?></label>
                </th>
                <td>
                    <select class="" style="" id="auto_import" name="auto_import">
                        <option <?php if ($auto_import === 'Disabled') echo 'selected'; ?> value="Disabled"><?php _e('Disabled', 'hw_cmt_import_export'); ?></option>
                        <option <?php if ($auto_import === 'Enabled') echo 'selected'; ?> value="Enabled"><?php _e('Enabled', 'hw_cmt_import_export'); ?></option>
                    </select>
                </td>
            </tr>
            <tbody class="import_section">
                <tr>
                    <th>
                        <label for="auto_import_start_time"><?php _e('Import Start Time', 'hw_cmt_import_export'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_import_start_time" id="auto_export_start_time"  value="<?php echo $auto_import_start_time; ?>"/>
                        <span class="description"><?php echo sprintf(__('Local time is <code>%s</code>.', 'hw_cmt_import_export'), date_i18n($timefor)) . ' ' . $scheduled_import_desc; ?></span>
                        <br/>
                        <span class="description"><?php _e('<code>Enter like 6:18pm or 12:27am</code>', 'hw_cmt_import_export'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_import_interval"><?php _e('Import Interval [ Minutes ]', 'hw_cmt_import_export'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="auto_import_interval" id="auto_export_interval"  value="<?php echo $auto_import_interval; ?>"  />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="auto_import_merge"><?php _e('Update Comment if exist', 'hw_cmt_import_export'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="auto_import_merge" id="auto_import_merge"  class="checkbox" <?php checked($auto_import_merge, 1); ?> />
                    </td>
                </tr>

                <?php
                $mapping_from_db = get_option('hw_prod_cmt_csv_imp_exp_mapping');
                if (!empty($mapping_from_db)) {
                    ?>
                    <tr>
                        <th>
                            <label for="auto_import_profile"><?php _e('Select a mapping file.'); ?></label>
                        </th>
                        <td>
                            <select name="auto_import_profile">
                                <option value="">--Select--</option>
                                <?php foreach ($mapping_from_db as $key => $value) { ?>
                                    <option value="<?php echo $key; ?>" <?php selected($key, $auto_import_profile); ?>><?php echo $key; ?></option>

                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                <?php } ?>

            </tbody>        


        </table>

        <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Settings', 'hw_cmt_import_export'); ?>" /></p>

    </form>
</div>