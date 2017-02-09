<?php

namespace Isotop\Flypress;

use Exception;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FileNotFoundException;
use Ramsey\Uuid\Uuid;

class Flypress {

	/**
	 * Flysystem adapter instance.
	 *
	 * @var \League\Flysystem\AdapterInterface
	 */
	protected $adapter;

	/**
	 * Filsystem instance.
	 *
	 * @var \League\Flysystem\Filesystem
	 */
	protected $filesystem;

	/**
	 * Orginal file name.
	 *
	 * @var string
	 */
	protected $file_name;

	/**
	 * Orginal upload directory.
	 *
	 * @var array
	 */
	protected $orginal_dir;

	/**
	 * The class instance.
	 *
	 * @var \Isotop\Flypress\Flypress
	 */
	protected static $instance;

	/**
	 * Get class instance.
	 *
	 * @return \Isotop\Flypress\Flypress
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Flypress construct.
	 */
	public function __construct( AdapterInterface $adapter = null ) {
		// Set default adapter.
		if ( ! is_null( $adapter ) ) {
			$this->adapter = $adapter;
		}

		// Load built in adapter if exists.
		if ( defined( 'FLYPRESS_ADAPTER' ) && file_exists( __DIR__ . '/adapters/' . strtolower( FLYPRESS_ADAPTER ) . '.php' ) ) {
			require_once __DIR__ . '/adapters/' . strtolower( FLYPRESS_ADAPTER ) . '.php';
		}

		/**
		 * Modify default adapter.
		 *
		 * @param \League\Flysystem\AdapterInterface $adapter
		 */
		$this->adapter = apply_filters( 'flypress_adapter', $this->adapter );

		// Bail if no adapter or not a instance of adapter interface. Flypress requires a adapter to work.
		if ( ! $this->adapter || $this->adapter instanceof AdapterInterface === false ) {
			return;
		}

		// Create a new filesystem instance.
		$this->filesystem = new Filesystem( $this->adapter );

		// Register flysystem stream wrapper.
		Stream_Wrapper::register( 'fly', $this->filesystem );

		// Set original upload directory.
		$this->orginal_dir = wp_upload_dir( null, false );

		// Setup filter for filtering upload directory.
		add_filter( 'upload_dir', [$this, 'filter_upload_dir'] );

		// Setup action for deleting attachments and sizes.
		add_action( 'delete_attachment', [$this, 'delete_attachment'] );

		// Setup filter for filtering attachment url.
		add_filter( 'wp_get_attachment_url', [$this, 'get_attachment_url'] );

		// Setup filter for filtering filename.
		add_filter( 'wp_handle_upload_prefilter', [$this, 'filter_handle_upload_prefilter'] );

		// Setup filter for filtering image editors.
		add_filter( 'wp_image_editors', [$this, 'filter_image_editors'], 9 );

		// Setup filter for filtering read image metadata.
		add_filter( 'wp_read_image_metadata', [$this, 'filter_read_image_metadata'], 10, 2 );

		// Setup action for saving attachment metadata.
		add_filter( 'wp_update_attachment_metadata', [$this, 'update_attachment_metadata'], 10, 2 );
	}

	/**
	 * Update attachment metadata.
	 *
	 * @param  array $data
	 * @param  int   $attachment_id
	 *
	 * @return array
	 */
	public function update_attachment_metadata( array $data, int $attachment_id ) {
		wp_update_post( [
			'ID'         => $attachment_id,
			'post_title' => pathinfo( $this->file_name, PATHINFO_FILENAME )
		] );

		$this->file_name = '';

		return $data;
	}

	/**
	 * Get adapter instance.
	 *
	 * @return \League\Flysystem\AdapterInterface
	 */
	public function adapter() {
		return $this->adapter;
	}

	/**
	 * Get filesystem instance.
	 *
	 * @return \League\Flysystem\Filesystem
	 */
	public function filesystem() {
		return $this->filesystem;
	}

	/**
	 * Get original upload directory.
	 *
	 * @return array
	 */
	public function original_dir() {
		return $this->orginal_dir;
	}

	/**
	 * Filter image editors to add our own imagick editor.
	 *
	 * @param  array $editors
	 *
	 * @return array
	 */
	public function filter_image_editors( array $editors ) {
		if ( ( $position = array_search( 'WP_Image_Editor_Imagick', $editors ) ) !== false ) {
			unset( $editors[ $position ] );
		}

		if ( ( $position = array_search( 'WP_Image_Editor_GD', $editors ) ) !== false ) {
			unset( $editors[ $position ] );
		}

		array_unshift( $editors, __NAMESPACE__ . '\\Image_Editor_Imagick' );
		array_unshift( $editors, __NAMESPACE__ . '\\Image_Editor_GD' );

		return $editors;
	}

	/**
	 * Delete attachments and sizes.
	 *
	 * @param  int $attachment_id
	 *
	 * @return bool
	 */
	public function delete_attachment( int $attachment_id ) {
		$data = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		$data = is_array( $data ) ? $data : [];
		$data['sizes'] = $data['sizes'] ?? [];

		// Add default size as a size.
		if ( $file = get_post_meta( $attachment_id, '_wp_attached_file', true ) ) {
			$data['sizes'][] = [
				'file' => $file
			];
		}

		$dir = wp_upload_dir( null, true );

		foreach ( array_values( $data['sizes'] ) as $size ) {
			$path = $dir['basedir'] . '/' . $size['file'];
			$path = explode( '://', $path );

			if ( count( $path ) < 2 ) {
				continue;
			}

			try {
				$this->filesystem->delete( $path[1] );
			} catch ( FileNotFoundException $e ) {
				continue;
			}
		}
	}

	/**
	 * Filter read image metadata since `exif_read_data` don't work with streams.
	 *
	 * @param  array  $meta
	 * @param  string $file
	 *
	 * @return array
	 */
	public function filter_read_image_metadata( array $meta, string $file ) {
		remove_filter( 'wp_read_image_metadata', [$this, 'filter_read_image_metadata'], 10 );
		$temp_file = wp_tempnam( $file, 'flypress' );

		copy( $file, $temp_file );
		$meta = wp_read_image_metadata( $temp_file );

		add_filter( 'wp_read_image_metadata', [$this, 'filter_read_image_metadata'], 10, 2 );
		unlink( $temp_file );

		return $meta;
	}

	/**
	 * Add a timestamp to each file name since WordPress built in
	 * unique filename check don't work when modifying filesystem.
	 *
	 * @param  array  $file
	 *
	 * @return array
	 */
	public function filter_handle_upload_prefilter( array $file ) {
		$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );

		if ( ! $extension ) {
			return $file;
		}

		$filename = str_replace( '.' . $extension, '', $file['name'] );
		$newname = $name = Uuid::uuid4();

		$this->file_name = $file['name'];

		$file['name'] = str_replace( $filename, $newname, $file['name'] );

		return $file;
	}

	/**
	 * Modify upload directory paths and urls to fly paths and urls.
	 *
	 * @param  array $dir
	 *
	 * @return array
	 */
	public function filter_upload_dir( array $dir ) {
		/**
		 * Get Flypress upload url.
		 *
		 * @param string $url
		 */
		$url = apply_filters( 'flypress_upload_url', $this->orginal_dir['baseurl'] );
		$url = is_string( $url ) ? $url : $this->orginal_dir['baseurl'];
		$url = rtrim( $url, '/' );

		/**
		 * Get Flypress base path.
		 *
		 * @param  string $path
		 */
		$base_path = apply_filters( 'flypress_base_path', 'fly://' );
		$base_path = is_string( $base_path ) ? $base_path : 'fly://';
		$base_path = $base_path[strlen( $base_path ) - 1] === '/' ? str_replace( '://', ':/', $base_path ) : $base_path;

		// Replace upload directory paths with fly path.
		$dir['path']    = str_replace( WP_CONTENT_DIR, $base_path, $dir['path'] );
		$dir['basedir'] = str_replace( WP_CONTENT_DIR, $base_path, $dir['basedir'] );

		// Replace upload directory urls with fly path.
		$dir['url']     = str_replace( $base_path, $url, $dir['path'] );
		$dir['baseurl'] = str_replace( $base_path, $url, $dir['basedir'] );

		// Sometimes you get 'uploads/uploads' and that's bad.
		$uploads = defined( 'UPLOADS' ) ? UPLOADS : '/uploads';
		$dir['url'] = str_replace( $uploads . $uploads, $uploads, $dir['url'] );
		$dir['baseurl'] = str_replace( $uploads . $uploads, $uploads, $dir['baseurl'] );

		return $dir;
	}

	/**
	 * Get flypress attachment url.
	 *
	 * @param  string url
	 *
	 * @return string
	 */
	public function get_attachment_url( string $url ) {
		$dir = wp_upload_dir( null, true );
		$url = str_replace( $this->orginal_dir['baseurl'], $dir['baseurl'], $url );

		/**
		 * Modify Flypress attachment url.
		 *
		 * @param  string $url
		 * @param  \League\Flysystem\AdapterInterface $adapter
		 */
		return apply_filters( 'flypress_attachment_url', $url, $this->adapter );
	}
}
