<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/

namespace modelbrouwers\mbstyles\staticfiles\storages;


class CombinedManifestStaticFilesStorage extends ManifestStaticFilesStorage
{
    protected $cache_key_prefix;

    public function __construct(
        \phpbb\config\config $config,
        \modelbrouwers\mbstyles\staticfiles\StaticCache $cache
    ) {
        parent::__construct($config, $cache);
        $this->hash_utility = new HashUtility($this);
        $this->compressor = new Compressor($this);
        $this->DEBUG = false;
    }

    public function url($files, $ext='js') {
        if ($this->DEBUG) {
            return $this->compressor->uncompressed($files, $ext);
        }

        $file_paths = array();
        $_output = array();

        // calculate all the file hashes to definitely use the latest file
        foreach ($files as $file) {
            $hashed = $this->getStoredName($file) ?: $file;
            $filename = $this->location . DIRECTORY_SEPARATOR . $hashed;
            $file_paths[] = $filename;
        }

        $url = $this->compressor->compress($file_paths, $ext);
        return $this->base_url . $url;
    }

    protected function getBundleScripts($apps) {

        $key = $this->cache_key_prefix . md5(serialize($apps));
        $result = $this->cache->get($key);

        if ($result === false) {
            foreach ($apps as &$app) {
                // $this->getStoredName yields the hash as generated by collectstatic
                // this is different from the bundled hash
                $app = $this->systemjs_output_dir . DIRECTORY_SEPARATOR . $app;
                $app = $this->hash_utility->get_hashed_name($app);
            }
            unset($app);
            $result = $this->compressor->getBundleScripts($apps);
            $this->cache->set($key, $result, 60*60*3); // cache for 3 hours
        }

        return $result;
    }
}
