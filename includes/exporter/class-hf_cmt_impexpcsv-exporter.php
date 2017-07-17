<?php

if (!defined('ABSPATH')) {
    exit;
}

class HW_Cmt_ImpExpCsv_Exporter {

    /**
     * WordPress Comments Exporter Tool
     */
    public static function do_export($cmt_ids = array()) {
        global $wpdb;
        if (!function_exists('get_current_screen')) {
            require_once(ABSPATH . 'wp-admin/includes/screen.php');
        }
        if (!empty($cmt_ids)) {
            $selected_cmt_ids = implode(', ', $cmt_ids);
        } else {
            $selected_cmt_ids = '';
        }

        $export_limit = !empty($_POST['limit']) ? intval($_POST['limit']) : '';
        $delimiter = !empty($_POST['delimiter']) ? $_POST['delimiter'] : ',';
        $articles = !empty($_POST['articles']) ? $_POST['articles'] : '';
        $products = !empty($_POST['products']) ? $_POST['products'] : '';
        if ($limit > $export_limit)
            $limit = $export_limit;

        if ($_POST['woo_enable'] != 0) {
            $woo_set = '1';
            $product_enable = 'product';
            $cmd_type = 'woodiscuz';
        } else {
            $product_enable = 'Post';
            $woo_set = '0';
            $cmd_type = null;
        }
        $cmt_date_from = !empty($_POST['cmt_date_from']) ? $_POST['cmt_date_from'] : date('Y-m-d 00:00', 0);
        $cmt_date_to = !empty($_POST['cmt_date_to']) ? $_POST['cmt_date_to'] : date('Y-m-d 23:59', current_time('timestamp'));
        $csv_columns = include( 'data/data-hf-post-columns.php' );
        $user_columns_name = !empty($_POST['columns_name']) ? $_POST['columns_name'] : $csv_columns;
        $export_columns = !empty($_POST['columns']) ? $_POST['columns'] : '';
        if ($limit > $export_limit)
            $limit = $export_limit;
        $settings = get_option('woocommerce_' . HW_CMT_IMP_EXP_ID . '_settings', null);
        $ftp_server = isset($settings['ftp_server']) ? $settings['ftp_server'] : '';
        $ftp_user = isset($settings['ftp_user']) ? $settings['ftp_user'] : '';
        $ftp_password = isset($settings['ftp_password']) ? $settings['ftp_password'] : '';
        $use_ftps = isset($settings['use_ftps']) ? $settings['use_ftps'] : '';
        $enable_ftp_ie = isset($settings['enable_ftp_ie']) ? $settings['enable_ftp_ie'] : '';
        $wpdb->hide_errors();
        @set_time_limit(0);
        if (function_exists('apache_setenv'))
            @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        @ob_clean();
        if ($enable_ftp_ie) {
            $file = "WP_comments-export-" . date('Y_m_d_H_i_s', current_time('timestamp')) . ".csv";
            $fp = fopen($file, 'w');
        } else {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=WP-comments-export-' . date('Y_m_d_H_i_s', current_time('timestamp')) . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');
            $fp = fopen('php://output', 'w');
        }

        // Headers
        $all_meta_keys = array('');
        $found_coupon_meta = array();
        // Some of the values may not be usable (e.g. arrays of arrays) but the worse
        // that can happen is we get an empty column.
        foreach ($all_meta_keys as $meta) {
            if (!$meta)
                continue;
            if (!in_array($meta, array_keys($csv_columns)) && substr((string) $meta, 0, 1) == '_')
                continue;
            if (in_array($meta, array_keys($csv_columns)))
                continue;
            $found_coupon_meta[] = $meta;
        }

        $found_coupon_meta = array_diff($found_coupon_meta, array_keys($csv_columns));

        // Variable to hold the CSV data we're exporting
        $row = array();





        // Export header rows
        foreach ($csv_columns as $column => $value) {

            $temp_head = esc_attr($user_columns_name[$column]);
            if (strpos($temp_head, 'yoast') === false) {
                $temp_head = ltrim($temp_head, '_');
            }
            if (!$export_columns || in_array($column, $export_columns))
                $row[] = $temp_head;
        }





        if (!$export_columns || in_array('meta', $export_columns)) {
            foreach ($found_coupon_meta as $product_meta) {
                $row[] = 'meta:' . self::format_data($product_meta);
            }
        }

        $row = array_map('HW_Cmt_ImpExpCsv_Exporter::wrap_column', $row);
        fwrite($fp, implode($delimiter, $row) . "\n");
        unset($row);
        $args = apply_filters('product_Comments_csv_product_export_args', array(
            'status' => 'all',
            'post_type' => $product_enable,
            'orderby' => 'comment_ID',
            'order' => 'ASC',
            'type' => $cmd_type,
            'number' => $export_limit,
            'suppress_filters' => false,
            'date_query' => array(
                array(
                    'before' => $cmt_date_to,
                    'after' => $cmt_date_from,
                    'inclusive' => true,
                ),),
        ));

        if (!empty($selected_cmt_ids)) {
            $args['comment__in'] = $selected_cmt_ids;
        }

        if ($woo_set != '0') {
            if (!empty($products)) {
                for ($i = 0; $i < count($products); $i++) {
                    $args = array(
                        'post__in' => implode(',', $products),
                        'orderby' => 'comment_ID',
                        'order' => 'ASC',
                        'post_type' => $product_enable,
                        'type' => $cmd_type,
                        'number' => $export_limit,
                        'suppress_filters' => false,
                        'date_query' => array(
                            array(
                                'before' => $cmt_date_to,
                                'after' => $cmt_date_from,
                                'inclusive' => true,
                            ),),
                    );
                }
            }
        } else {
            if (!empty($articles)) {
                for ($i = 0; $i < count($articles); $i++) {
                   $args = array(
                        'post__in' => implode(',', $articles),
                        'orderby' => 'comment_ID',
                        'order' => 'ASC',
                        'post_type' => $product_enable,
                        'type' => $cmd_type,
                        'number' => $export_limit,
                        'suppress_filters' => false,
                        'date_query' => array(
                            array(
                                'before' => $cmt_date_to,
                                'after' => $cmt_date_from,
                                'inclusive' => true,
                            ),),
                    );
                  
                   // $args['post__in'] = implode(',', $articles);
                }
            }
        }

        global $wpdb;

        $comments_query = new WP_Comment_Query;
        $comments = $comments_query->query($args);

        foreach ($comments as $comment) {
            $row = array();

            $comment_ID = $comment->comment_ID;
            $obj = new HW_Cmt_ImpExpCsv_Exporter();
            $meta_data = $obj->get_meta_status($comment_ID, $woo_set);

            if ($meta_data) {
                $comment->meta = new stdClass;
                $comment->meta->verified = get_comment_meta($comment_ID, 'rating', true);

                if ($woo_set != '0') {
                    // Meta data
                    foreach ($meta_data as $meta => $value) {
                        if (!$meta) {
                            continue;
                        }
                        if (!in_array($meta, array_keys($csv_columns)) && substr($meta, 0, 1) == '_') {
                            continue;
                        }


                        $meta_value = maybe_unserialize(maybe_unserialize($value));

                        if (is_array($meta_value)) {
                            $meta_value = json_encode($meta_value);
                        }

                        $comment->meta->$meta = self::format_export_meta($meta_value, $meta);
                    }
                }
                foreach ($csv_columns as $column => $value) {

                    if (!$export_columns || in_array($column, $export_columns)) {
                        if ($column === 'comment_alter_id') {
                            $row[] = self::format_data($comment_ID);
                        }

                        if (isset($comment->meta->$column)) {
                            $row[] = self::format_data($comment->meta->$column);
                        } elseif (isset($comment->$column) && !is_array($comments[0]->$column)) {
                            if ($column === 'post_title') {
                                $row[] = sanitize_text_field($comment->$column);
                            } else {
                                $row[] = self::format_data($comment->$column);
                            }
                        } else {
                            $row[] = '';
                        }
                    }
                }


                if (!$export_columns || in_array('meta', $export_columns)) {
                    foreach ($found_coupon_meta as $product_meta) {
                        if (isset($comment->meta->$product_meta)) {
                            $row[] = self::format_data($comment->meta->$product_meta);
                        } else {
                            $row[] = '';
                        }
                    }
                }
                $row = array_map('HW_Cmt_ImpExpCsv_Exporter::wrap_column', $row);
                fwrite($fp, implode($delimiter, $row) . "\n");
                unset($row);
            }
        }
        if ($enable_ftp_ie) {
            if ($use_ftps) {
                $ftp_conn = ftp_ssl_connect($ftp_server) or die("Could not connect to $ftp_server");
            } else {
                $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
            }
            $login = ftp_login($ftp_conn, $ftp_user, $ftp_password);

            // upload file
            if (ftp_put($ftp_conn, $file, $file, FTP_ASCII)) {
                $hw_product_comment_ie_msg = 1;
                wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&hw_product_comment_ie_msg=' . $hw_product_comment_ie_msg));
            } else {
                $hw_product_comment_ie_msg = 2;
                wp_redirect(admin_url('/admin.php?page=hw_cmt_csv_im_ex&hw_product_comment_ie_msg=' . $hw_product_comment_ie_msg));
            }

            // close connection
            ftp_close($ftp_conn);
        }

        fclose($fp);
        exit;
    }

    /**
     * Format the data if required
     * @param  string $meta_value
     * @param  string $meta name of meta key
     * @return string
     */
    public static function format_export_meta($meta_value, $meta) {
        switch ($meta) {
            case '_sale_price_dates_from' :
            case '_sale_price_dates_to' :
                return $meta_value ? date('Y-m-d', $meta_value) : '';
                break;
            case '_upsell_ids' :
            case '_crosssell_ids' :
                return implode('|', array_filter((array) json_decode($meta_value)));
                break;
            default :
                return $meta_value;
                break;
        }
    }

    public static function format_data($data) {
        if (!is_array($data))
            ;
        $data = (string) urldecode($data);
        $enc = mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);
        $data = ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
        return $data;
    }

    /**
     * Wrap a column in quotes for the CSV
     * @param  string data to wrap
     * @return string wrapped data
     */
    public static function wrap_column($data) {
        return '"' . str_replace('"', '""', $data) . '"';
    }

    public static function get_meta_status($id, $woo_set) {
        if ($woo_set != '0') {
            // global $wpdb;
            //  $query = "SELECT comment_type FROM $wpdb->comments WHERE comment_ID=".$id;
            // $result = $wpdb->get_var($query);
            // if($result === 'woodiscuz')
            // {
            $new_comment_type = get_comment_type($comment_id = $id);
            if ($new_comment_type == 'woodiscuz') {
               return true;
            }
            //  }
            return false;
        } else {
            if (count(get_comment_meta($id)) != 0) {
                return true;
            } else {
                return true;
            }
        }
    }

}
