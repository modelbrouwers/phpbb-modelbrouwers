<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/

namespace modelbrouwers\mbstyles\staticfiles\storages;

/**
 * Utility class to inject in the FileSystemStorage class. This takes care of
 * computing/retrieving hashes to files. It's a workaround for mixins...
 */
class HashUtility
{
    protected $storage = null;

    public function __construct(StaticFilesStorage $storage) {
        $this->storage = $storage;
    }

    public function get_hashed_name($file) {
        if($this->storage->DEBUG) return $file;
        $abs_path = realpath($this->storage->get_location() . DIRECTORY_SEPARATOR . $file);

        $pathinfo = pathinfo($file);
        $dirname = $pathinfo['dirname'];
        $filename = $pathinfo['filename'];
        $extension = $pathinfo['extension'];

        $md5 = md5_file($abs_path);
        $hash = substr($md5, 0, 12);

        $hashed_filename = $filename . '.' . $hash . '.' . $extension;
        $result = $dirname . DIRECTORY_SEPARATOR. $hashed_filename;
        return $result;
    }
}
