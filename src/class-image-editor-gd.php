<?php

namespace Isotop\Flypress;

use WP_Image_Editor_GD;
use WP_Error;

class Image_Editor_GD extends WP_Image_Editor_GD {

	public function load() {
	    if ( $this->image )
	        return true;

	  #  var_dump(file_get_contents($this->file));
	   var_dump(is_file($this->file), $this->file);exit;



	    if ( ! is_file( $this->file ) )
	        return new WP_Error( 'error_loading_image', __('File doesn&#8217;t exist?'), $this->file );

	    // Set artificially high because GD uses uncompressed images in memory.
	    wp_raise_memory_limit( 'image' );

	    $this->image = @imagecreatefromstring( file_get_contents( $this->file ) );

	    if ( ! is_resource( $this->image ) )
	        return new WP_Error( 'invalid_image', __('File is not an image.'), $this->file );

	    $size = @getimagesize( $this->file );
	    if ( ! $size )
	        return new WP_Error( 'invalid_image', __('Could not read image size.'), $this->file );

	    if ( function_exists( 'imagealphablending' ) && function_exists( 'imagesavealpha' ) ) {
	        imagealphablending( $this->image, false );
	        imagesavealpha( $this->image, true );
	    }

	    $this->update_size( $size[0], $size[1] );
	    $this->mime_type = $size['mime'];

	    return $this->set_quality();
	}

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

		var_dump($temp_filename);exit;

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
