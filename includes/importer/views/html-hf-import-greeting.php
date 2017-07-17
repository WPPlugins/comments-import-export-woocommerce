<?php

	$ftp_server = '';
	$ftp_user = '';
	$ftp_password = '';
	$use_ftps = '';
	$enable_ftp_ie = '';	
	$ftp_server_path = '';	
	if(!empty($ftp_settings)){
		$ftp_server = !empty($ftp_settings[ 'ftp_server' ]) ? $ftp_settings[ 'ftp_server' ] : '';
		$ftp_user = !empty($ftp_settings[ 'ftp_user' ]) ? $ftp_settings[ 'ftp_user' ] : '';
		$ftp_password = !empty($ftp_settings[ 'ftp_password' ]) ? $ftp_settings[ 'ftp_password' ] : '';
		$use_ftps = !empty($ftp_settings[ 'use_ftps' ]) ? $ftp_settings[ 'use_ftps' ] : '';
		$enable_ftp_ie = !empty($ftp_settings[ 'enable_ftp_ie' ]) ? $ftp_settings[ 'enable_ftp_ie' ] : '';
		$ftp_server_path = !empty($ftp_settings[ 'ftp_server_path' ]) ? $ftp_settings[ 'ftp_server_path' ] : '';				
	}

?>
<div>
	<p><?php _e( 'You can import comments (in CSV format) in to the shop using any of below methods.', 'hw_cmt_import_export' ); ?></p>

	<?php if ( ! empty( $upload_dir['error'] ) ) : ?>
		<div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:'); ?></p>
		<p><strong><?php echo $upload_dir['error']; ?></strong></p></div>
	<?php else : ?>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
			<table class="form-table">
				<tbody>
					<tr>
						<th>
							<label for="upload"><?php _e( 'Method 1: Select a file from your computer' ); ?></label>
						</th>
						<td>
							<input type="file" id="upload" name="import" size="25" />
							<input type="hidden" name="action" value="save" />
							<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
							<small><?php printf( __('Maximum size: %s' ), $size ); ?></small>
						</td>
					</tr>
                                        
					<tr>
						<th>
							<label for="ftp"><?php _e( 'Method 2: Provide FTP Details:', 'hw_cmt_import_export' ); ?></label>
						</th>
						<td>
							<table class="form-table">
								<tr>
									<th>
										<label for="enable_ftp_ie"><?php _e( 'Enable FTP import', 'hw_cmt_import_export' ); ?></label>
									</th>
									<td>
										<input type="checkbox" name="enable_ftp_ie" id="enable_ftp_ie" class="checkbox" <?php checked( $enable_ftp_ie, 1 ); ?> />
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_server"><?php _e( 'FTP Server Host/IP', 'hw_cmt_import_export' ); ?></label>
									</th>
									<td>
										<input type="text" name="ftp_server" id="ftp_server" placeholder="<?php _e('XXX.XXX.XXX.XXX', 'hw_cmt_import_export'); ?>" value="<?php echo $ftp_server; ?>" class="input-text" />
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_user"><?php _e( 'FTP User Name', 'hw_cmt_import_export' ); ?></label>
									</th>
									<td>
										<input type="text" name="ftp_user" id="ftp_user"  value="<?php echo $ftp_user; ?>" class="input-text" />
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_password"><?php _e( 'FTP Password', 'hw_cmt_import_export' ); ?></label>
									</th>
									<td>
										<input type="password" name="ftp_password" id="ftp_password"  value="<?php echo $ftp_password; ?>" class="input-text" />
									</td>
								</tr>
								<tr>
									<th>
										<label for="ftp_server_path"><?php _e( 'FTP Server Path', 'hw_cmt_import_export' ); ?></label>
									</th>
									<td>
										<input type="text" name="ftp_server_path" id="ftp_server_path"  value="<?php echo $ftp_server_path; ?>" class="input-text" />
									</td>
								</tr>
								
								<tr>
									<th>
										<label for="use_ftps"><?php _e( 'Use FTPS', 'hw_cmt_import_export' ); ?></label>
									</th>
									<td>
										<input type="checkbox" name="use_ftps" id="use_ftps" class="checkbox" <?php checked( $use_ftps, 1 ); ?> />
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php
                    $mapping_from_db = get_option('hw_prod_comment_csv_imp_exp_mapping');
                    if (!empty($mapping_from_db)) {
                        ?>
                        <tr>
                            <th>
                                <label for="profile"><?php _e('Select a mapping file.'); ?></label>
                            </th>
                            <td>
                                <select name="profile">
                                    <option value="">--Select--</option>
                                    <?php foreach ($mapping_from_db as $key => $value) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo $key; ?></option>

                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
					<tr>
						<th><label><?php _e( 'Delimiter', 'hw_cmt_import_export' ); ?></label><br/></th>
						<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
					</tr>
					
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Upload file and import' ); ?>" />
			</p>
		</form>
	<?php endif; ?>
</div>