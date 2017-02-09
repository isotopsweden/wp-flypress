<?php

namespace Isotop\Flypress;

use Exception;
use WP_CLI;
use WP_CLI_Command;
use WP_Query;

class CLI extends WP_CLI_Command {

	/**
	 * Test to write, read and delete a file.
	 */
	public function test() {
		try {
			flypress()->filesystem()->write( 'wp-cli.txt', 'working!' );
		} catch ( Exception $e ) {
			WP_CLI::error( $e );
		}

		try {
			WP_CLI::success( flypress()->filesystem()->readAndDelete( 'wp-cli.txt' ) );
		} catch ( Exception $e ) {
			WP_CLI::error( $e );
		}
	}

	/**
	 * Migrate attachment to flysystem.
	 *
	 * @param  array $args
	 * @param  array $args_assoc
	 *
	 * @synopsis [--delete-local]
	 */
	public function migrate( $args, $args_assoc ) {
		$attachments = new WP_Query( [
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'all',
		] );

		$org = flypress()->original_dir();
		$dir = wp_upload_dir();

		WP_CLI::line( sprintf( 'Attempting to move %d attachments to flypress filesystem', $attachments->found_posts ) );

		foreach ( $attachments->posts as $attachment ) {
			$files = [get_post_meta( $attachment->ID, '_wp_attached_file', true )];
			$data  = wp_get_attachment_metadata( $attachment->ID );

			if ( ! empty( $data ) && isset( $data['sizes'] ) ) {
				foreach ( (array) $data['sizes'] as $file ) {
					$files[] = path_join( dirname( $data['file'] ), $file['file'] );
				}
			}

			foreach ( $files as $file ) {
				if ( file_exists( $path = $org['basedir'] . '/' . $file ) ) {
					if ( ! copy( $path, $dir['basedir'] . '/' . $file ) ) {
						WP_CLI::line( sprintf( 'Failed to moved %s to flypress filesystem', $file ) );
					} else {
						if ( ! empty( $args_assoc['delete-local'] ) ) {
							unlink( $path );
						}

						WP_CLI::success( sprintf( 'Moved file %s to flypress filesystem', $file ) );
					}
				} else {
					WP_CLI::line( sprintf( 'Already moved to %s to flypress filesystem', $file ) );
				}
			}
		}

		WP_CLI::success( 'Moved all attachment to flypress adapter filesystem.' );
		WP_CLI::line( '' );
	}

}

WP_CLI::add_command( 'flypress', __NAMESPACE__ . '\\CLI' );
