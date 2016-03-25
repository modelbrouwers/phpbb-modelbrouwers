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
 * Combines an array of static files into one (minified) file.
 * Similar to the 'compress' tag.
 */
class CombinedCachedStaticFilesStorage extends CachedFilesStorage
{
    public function __construct($cache) {
        parent::__construct($cache);
        $this->compressor = new Compressor($this);
    }

    public function url($files, $ext='js') {
        if ($this->DEBUG) {
            return $this->compressor->uncompressed($files, $ext);
        }

        $file_paths = array();
        $_output = array();

        // calculate all the file hashes to definitely use the latest file
        foreach ($files as $file) {
            $filename = $this->location . DIRECTORY_SEPARATOR . $this->hash_utility->get_hashed_name($file);
            $file_paths[] = $filename;
        }

        $url = $this->compressor->compress($file_paths, $ext);
        return $this->base_url . $url;
    }

    protected function getBundleScripts($apps) {
        return $this->compressor->getBundleScripts($apps);
    }
}
