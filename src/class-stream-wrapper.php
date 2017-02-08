<?php

namespace Isotop\Flypress;

use League\Flysystem\FilesystemInterface;
use Twistor\FlysystemStreamWrapper;

class Stream_Wrapper extends FlysystemStreamWrapper {

    /**
     * Registers the stream wrapper protocol if not already registered.
     *
     * @param string              $protocol      The protocol.
     * @param FilesystemInterface $filesystem    The filesystem.
     * @param array|null          $configuration Optional configuration.
     *
     * @return bool True if the protocal was registered, false if not.
     */
    public static function register( $protocol, FilesystemInterface $filesystem, array $configuration = null ) {
        if ( static::streamWrapperExists( $protocol ) ) {
            return false;
        }

        static::$config[$protocol] = $configuration ?: static::$defaultConfiguration;
        static::registerPlugins( $protocol, $filesystem );
        static::$filesystems[$protocol] = $filesystem;

        return stream_wrapper_register( $protocol, __CLASS__ );
    }

	/**
	 * If the file is actually just a path to a directory
	 * then return it as always existing.
	 *
	 * This is to work around `wp_upload_dir` doing
	 * `file_exists` checks on the uploads directory
	 * on every page load.
	 *
	 * @param  string $uri
	 * @param  int    $flags
	 *
	 * @return array
	 */
	public function url_stat( $uri, $flags ) {
		$extension = pathinfo( $uri, PATHINFO_EXTENSION );

		if ( ! $extension ) {
			return [
				0         => 0,
				'dev'     => 0,
				1         => 0,
				'ino'     => 0,
				2         => 16895,
				'mode'    => 16895,
				3         => 0,
				'nlink'   => 0,
				4         => 0,
				'uid'     => 0,
				5         => 0,
				'gid'     => 0,
				6         => -1,
				'rdev'    => -1,
				7         => 0,
				'size'    => 0,
				8         => 0,
				'atime'   => 0,
				9         => 0,
				'mtime'   => 0,
				10        => 0,
				'ctime'   => 0,
				11        => -1,
				'blksize' => -1,
				12        => -1,
				'blocks'  => -1,
			];
		}

		return parent::url_stat( $uri, $flags );
	}
}
