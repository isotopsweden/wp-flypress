<?php

namespace Isotop\Flysystem;

use Imagick;
use WP_Image_Editor_Imagick;

class Image_Editor_Imagick extends WP_Image_Editor_Imagick {

	/**
	 * Imagick can't by default handle stream wrappers so let's fix it.
	 *
	 * @param  \Imagick $image
	 * @param  string   $filename
	 * @param  string   $mime_type
	 *
	 * @return array
	 */
	protected function _save( $image, $filename = '', $mime_type = '' ) {
		list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

		if ( empty( $filename ) ) {
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

		return [
			'path'      => $filename,
			'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
			'width'     => $this->size['width'],
			'height'    => $this->size['height'],
			'mime-type' => $mime_type,
		];
	}
}
