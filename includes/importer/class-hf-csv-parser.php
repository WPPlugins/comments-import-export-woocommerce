<?php
/**
 * CSV Importer class for managing parsing of CSV files.
 */
class HW_CSV_Parser {

	var $row;
	var $post_type;
	var $reserved_fields;		// Fields we map/handle (not custom fields)
	var $post_defaults;			// Default post data
	var $postmeta_defaults;		// default post meta
	var $postmeta_allowed;		// post meta validation
	var $allowed_product_types;	// Allowed product types
	/**
	 * Constructor
	 */
	public function __construct( $post_type = 'product' ) {
		$this->post_type         = $post_type;
		$this->reserved_fields   = include( 'data/data-hf-reserved-fields.php' );
		$this->post_defaults     = include( 'data/data-hf-post-defaults.php' );
		
	}
	/**
	 * Format data from the csv file
	 * @param  string $data
	 * @param  string $enc
	 * @return string
	 */
	public function format_data_from_csv( $data, $enc ) {
		return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
	}

	/**
	 * Parse the data
	 * @param  string  $file      [description]
	 * @param  string  $delimiter [description]
	 * @param  array  $mapping   [description]
	 * @param  integer $start_pos [description]
	 * @param  integer  $end_pos   [description]
	 * @return array
	 */
	public function parse_data( $file, $delimiter, $mapping, $start_pos = 0, $end_pos = null, $eval_field ) {
// Set locale
		$enc = mb_detect_encoding( $file, 'UTF-8, ISO-8859-1', true );
		if ( $enc )
			setlocale( LC_ALL, 'en_US.' . $enc );
		@ini_set( 'auto_detect_line_endings', true );

		$parsed_data = array();
		$raw_headers = array();

		// Put all CSV data into an associative array
		if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {

			$header   = fgetcsv( $handle, 0, $delimiter , '"', '"'  );
			if ( $start_pos != 0 )
				fseek( $handle, $start_pos );

		    while ( ( $postmeta = fgetcsv( $handle, 0, $delimiter , '"', '"' ) ) !== FALSE ) {
	            $row = array();
				
	            foreach ( $header as $key => $heading ) {
					$s_heading = $heading;

	            	// Check if this heading is being mapped to a different field
            		if ( isset( $mapping[$s_heading] ) ) {
            			if ( $mapping[$s_heading] == 'import_as_meta' ) {

            				$s_heading = 'meta:' . $s_heading;

            			} elseif ( $mapping[$s_heading] == 'import_as_images' ) {

            				$s_heading = 'images';

            			} else {
            				$s_heading = esc_attr( $mapping[$s_heading] );
            			}
            		}
                        foreach ($mapping as $mkey => $mvalue) {
                                if(trim($mvalue) === trim($heading)){
                                    $s_heading =  $mkey;
                                }
                        }

            		if ( $s_heading == '' )
            			continue;

	            	// Add the heading to the parsed data
					$row[$s_heading] = ( isset( $postmeta[$key] ) ) ? $this->format_data_from_csv( $postmeta[$key], $enc ) : '';

					$row[$s_heading] = $this->evaluate_field($row[$s_heading], $eval_field[$s_heading]);
					
	               	// Raw Headers stores the actual column name in the CSV
					$raw_headers[ $s_heading ] = $heading;
	            }
	            $parsed_data[] = $row;

	            unset( $postmeta, $row );

	            $position = ftell( $handle );

	            if ( $end_pos && $position >= $end_pos )
	            	break;
		    }
		    fclose( $handle );
		}
		return array( $parsed_data, $raw_headers, $position );
	}
	
	private function evaluate_field($value, $evaluation_field){
		$processed_value = $value;
		if(!empty($evaluation_field)){
			$operator = substr($evaluation_field, 0, 1);
			if(in_array($operator, array('=', '+', '-', '*', '/', '&'))){
				$eval_val = substr($evaluation_field, 1);
				switch($operator){
					case '=':
							$processed_value = trim($eval_val); 
							break;
					case '+':
							$processed_value = $this->hw_currency_formatter($value) + $eval_val; 
							break;
					case '-': 
							$processed_value = $value - $eval_val; 
							break;
					case '*': 
							$processed_value = $value * $eval_val; 
							break;
					case '/': 
							$processed_value = $value / $eval_val; 
							break;
					case '&': 
							if (strpos($eval_val, '[VAL]') !== false) {
								$processed_value = str_replace('[VAL]',$value,$eval_val);								 
							}
							else{
								$processed_value = $value . $eval_val;
							}
							break;					
				}
			}	
		}
		return $processed_value;	
	}

	/**
	 * Parse product comment
	 * @param  array  $item
	 * @param  integer $merge_empty_cells
	 * @return array
	 */
	public function parse_product_comment( $item, $merge_empty_cells = 0 ) {
		global $HW_CSV_Comments_Import, $wpdb;
		$this->row++;

		$terms_array = $postmeta = $product = array();
		$attributes = $default_attributes = $gpf_data = null;
		// Merging
		$merging = ( ! empty( $_GET['merge'] ) && $_GET['merge'] ) ? true : false;
		$post_id = ( ! empty( $item['comment_ID'] ) ) ? $item['comment_ID'] : 0;
		$post_id = ( ! empty( $item['post_id'] ) ) ? $item['post_id'] : $post_id;
		if ( $merging ) {

			$product['merging'] = true;
                        
                        if(function_exists('WC'))
                        {
			$HW_CSV_Comments_Import->log->add( 'csv-import', sprintf( __('> Row %s - preparing for merge.', 'hw_cmt_import_export'), $this->row ) );
                        }
			// Required fields
			if ( ! $post_id ) {
                            if(function_exists('WC'))
                            {
				$HW_CSV_Comments_Import->log->add( 'csv-import', __( '> > Cannot merge without id. Importing instead.', 'hw_cmt_import_export') );
                            }
				$merging = false;
			} else {

				// Check product exists
				if ( ! $post_id ) 
				{
                    $post_db_type = $this->post_defaults['post_type'];
                    $post_pass_type = '"'.$post_db_type.'"';
// Check product to merge exists
                    $db_query = $wpdb->prepare("
						SELECT comment_ID
					    FROM $wpdb->comments
					    WHERE $wpdb->comments = $post_id");
					$found_product_id = $wpdb->get_var($db_query);
					if ( ! $found_product_id ) {
						$HW_CSV_Comments_Import->log->add( 'csv-import', sprintf(__( '> > Skipped. Cannot find product comments with ID %s. Importing instead.', 'hw_cmt_import_export'), $item['ID']) );
						$merging = false;

					} else {

						$post_id = $found_product_id;

						$HW_CSV_Comments_Import->log->add( 'csv-import', sprintf(__( '> > Found product comments with ID %s.', 'hw_cmt_import_export'), $post_id) );
					}
				}
				$product['merging'] = true;
			}
		}

		if ( ! $merging ) {

			$product['merging'] = false;
                        if(function_exists('WC'))
                        {
			$HW_CSV_Comments_Import->log->add( 'csv-import', sprintf( __('> Row %s - preparing for import.', 'hw_cmt_import_export'), $this->row ) );
                        }
			// Required fields
			if ( $item['comment_content'] === '')
			{
                            if(function_exists('WC'))
                            {
				$HW_CSV_Comments_Import->log->add( 'csv-import', __( '> > Skipped. No comment content set for new product comments.', 'hw_cmt_import_export') );
                            }
                                return new WP_Error( 'parse-error', __( 'No comment content set for new product comments.', 'hw_cmt_import_export' ) );
			}
                        if ( $item['comment_post_ID'] === '' )
                        {
			if(function_exists('WC'))
                        {
                            $HW_CSV_Comments_Import->log->add( 'csv-import', __( '> > Skipped. No post(product) id found, for which new comment is to be imported', 'hw_cmt_import_export') );
                        }
                            return new WP_Error( 'parse-error', __( 'No post(product) id found, for which new comment is to be imported.', 'hw_cmt_import_export' ) );
			}

		}

		$product['post_id'] = $post_id;
		// Get post fields
		foreach ( $this->post_defaults as $column => $default ) {
			if ( isset( $item[ $column ] ) ) $product[ $column ] = $item[ $column ];
		}
                  if ( ! $merging ) {
			// Merge post meta with defaults
			$product  = wp_parse_args( $product, $this->post_defaults );
			$postmeta = wp_parse_args( $postmeta, $this->postmeta_defaults );
		}
		
		// Put set core product postmeta into product array
		foreach ( $postmeta as $key => $value ) {
			$product['postmeta'][] = array( 'key' 	=> esc_attr($key), 'value' => $value );
		}

		/**
		 * Handle other columns
		 */
		foreach ( $item as $key => $value ) 
		{

			if ( empty($item['post_parent']) && ! $merge_empty_cells && $value == "" )
				continue;


			/**
			 * Handle meta: columns - import as custom fields
			 */
			elseif ( strstr( $key, 'meta:' ) ) {

				// Get meta key name
				$meta_key = ( isset( $HW_CSV_Comments_Import->raw_headers[$key] ) ) ? $HW_CSV_Comments_Import->raw_headers[$key] : $key;
				$meta_key = trim( str_replace( 'meta:', '', $meta_key ) );

                                if($meta_key !== 'wcpb_bundle_products'){
				// Decode JSON
				$json = json_decode( $value, true );

				if ( is_array( $json ) || is_object( $json ) )
					$value = (array) $json;
                                
                                }
				// Add to postmeta array
				$product['postmeta'][] = array(
					'key' 	=> esc_attr( $meta_key ),
					'value' => $value
				);
			}

			

		}

		// Remove empty attribues
                if(!empty($attributes))
		foreach ( $attributes as $key => $value ) {
			if ( ! isset($value['name']) ) unset( $attributes[$key] );
		}

		
		$product['comment_content'] = ( ! empty( $item['comment_content'] ) ) ? $item['comment_content'] : '';
		unset( $item, $terms_array, $postmeta, $attributes, $gpf_data, $images );
		return $product;
	}
    
}