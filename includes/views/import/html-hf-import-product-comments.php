<div class="tool-box">
    <h3 class="title"><?php _e('Import Comments in CSV Format:', 'hw_cmt_import_export'); ?></h3>
    <p><?php _e('Import comments in CSV format from different sources (  from your computer OR from another server via FTP )', 'hw_cmt_import_export'); ?></p>
    <p class="submit">
        <?php
        $merge_url = admin_url('admin.php?import=product_comments_csv&merge=1');
        $import_url = admin_url('admin.php?import=product_comments_csv');
        ?>
        <a class="button button-primary" id="mylink" href="<?php echo admin_url('admin.php?import=product_comments_csv'); ?>"><?php _e('Import Comments', 'hw_cmt_import_export'); ?></a>
        &nbsp;
        <input type="checkbox" id="merge" value="0"><?php _e('Merge comments if exists', 'hw_cmt_import_export'); ?> <br>
    </p>
</div>
<script type="text/javascript">
    jQuery('#merge').click(function () {
        if (this.checked) {
            jQuery("#mylink").attr("href", '<?php echo $merge_url ?>');
        } else {
            jQuery("#mylink").attr("href", '<?php echo $import_url ?>');
        }
    });
</script>