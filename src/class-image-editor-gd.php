<?php

namespace Isotop\Flypress;

use WP_Image_Editor_GD;
use WP_Error;

class Image_Editor_GD extends WP_Image_Editor_GD {

	/**
	 * GD editor can't handle stream wrapper paths by default.
	 *
	 * @param  resource $image
	 * @param  string   $filename
	 * @param  string   $mime_type
	 *
	 * @return array
	 */
	protected function _save( $image, $filename = null, $mime_type = null ) {
	    list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

	    if ( ! $filename ) {
	        $filename = $this->generate_filename( null, null, $extension );
	    }

		$upload_dir = wp_upload_dir();

		if ( strpos( $filename, $upload_dir['basedir'] ) === 0 ) {
			$temp_filename = tempnam( get_temp_dir(), 'flypress' );
		}

		$save = parent::_save( $image, $temp_filename, $mime_type );

		if ( is_wp_error( $save ) ) {
			unlink( $temp_filename );
			return $save;
		}

		$copy_result = copy( $save['path'], $filename );

		unlink( $save['path'] );
		unlink( $temp_filename );

		if ( ! $copy_result ) {
			return new WP_Error( 'unable-to-copy-flypress', 'Unable to copy the temp image to adapter storage using flypress' );
		}

	    /**
	     * Filters the name of the saved image file.
	     *
	     * @since 2.6.0
	     *
	     * @param string $filename Name of the file.
	     */
	    return [
	        'path'      => $filename,
	        'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
	        'width'     => $this->size['width'],
	        'height'    => $this->size['height'],
	        'mime-type' => $mime_type,
	    ];
	}
}
