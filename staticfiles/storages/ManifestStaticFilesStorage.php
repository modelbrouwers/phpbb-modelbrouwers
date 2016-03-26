<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/

namespace modelbrouwers\mbstyles\staticfiles\storages;


class ManifestStaticFilesStorage extends StaticFilesStorage
{

    protected $manifest_name = 'staticfiles.json';
    protected $hashed_files = null;
    protected $cache = null;

    public function __construct(
        \phpbb\config\config $config,
        \modelbrouwers\mbstyles\staticfiles\StaticCache $cache
    ) {
        parent::__construct($config);
        $this->cache = $cache;
        $this->cache_key_prefix = 'php:staticfiles:';

        // reduce disk IO by caching stuffs
        $key = $this->cache_key_prefix . 'manifest:hashed-files';
        $this->hashed_files = $this->cache->get($key);
        if (!$this->hashed_files) {
            $this->hashed_files = $this->loadManifest();
            $this->cache->set($key, $this->hashed_files, 60*60*3); // cache for 3 hours
        }
    }

    protected function loadManifest() {
        $content = $this->readManifest();
        if ($content === null) {
            return array();
        }
        $stored = json_decode($content, true);
        $version = $stored['version'];
        if ($version == '1.0') {
            return $stored['paths'] ?: array();
        }
        throw new \Exception("Couldn't load manifest - unknown version {$version}");
    }

    protected function readManifest() {
        $path = $this->location . DIRECTORY_SEPARATOR . $this->manifest_name;
        $content = file_get_contents($path);
        return $content;
    }

    public function url($file) {
        if ($this->DEBUG) return $this->base_url . $file;
        return $this->base_url . $this->getStoredName($file);
    }

    protected function getStoredName($file) {
        return $this->hashed_files[$file] ?: null;
    }
}
