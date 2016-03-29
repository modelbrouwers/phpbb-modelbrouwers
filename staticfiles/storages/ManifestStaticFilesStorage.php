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
        $key = $this->getCacheKey();
        $this->hashed_files = $this->cache->get($key);
        if (!$this->hashed_files) {
            $this->hashed_files = $this->loadManifest();
            $this->saveCache($key);
        }
    }

    protected function loadManifest() {
        $content = $this->readManifest();
        if ($content === null) {
            return array();
        }
        $stored = json_decode($content, true);
        $version = $stored['version'];
        if ($version === '1.0') {
            return $stored['paths'] ?: array();
        }
        throw new \Exception("Couldn't load manifest - unknown version {$version}");
    }

    protected function getManifestLocation() {
        return $this->location . DIRECTORY_SEPARATOR . $this->manifest_name;
    }

    protected function readManifest() {
        $path = $this->getManifestLocation();
        $content = file_get_contents($path);
        return $content;
    }

    protected function getCacheKey() {
        return $key = $this->cache_key_prefix . 'manifest:hashed-files';
    }

    protected function saveCache($key=null) {
        if ($key === null) {
            $key = $this->getCacheKey();
        }
        $this->cache->set($key, $this->hashed_files, 60*60*3); // cache for 3 hours
    }

    protected function saveManifest($version='1.0') {
        // update the cache
        $this->saveCache();
        if ($version === '1.0') {
            $toStore = array(
                'version' => $version,
                'paths' => $this->hashed_files
            );
            $encoded = json_encode($toStore, JSON_UNESCAPED_SLASHES);
            $path = $this->getManifestLocation();
            return file_put_contents($path, $encoded);
        } else {
            throw new \Exception("Couldn't write manifest - unknown version {$version}");
        }
    }

    public function url($file) {
        if ($this->DEBUG) return $this->base_url . $file;
        return $this->base_url . $this->getStoredName($file);
    }

    public function postProcess($apps) {
        if (!$apps) {
            return;
        }
        $this->DEBUG = false;
        $hashUtility = new HashUtility($this);
        $systemjsDir = $this->config['staticfiles_static_root'] .
                       DIRECTORY_SEPARATOR . $this->config['systemjs_output_dir'];
        $postProcessed = array();
        foreach ($apps as $app) {
            // we need to calculate the hash of the entire bundle, not just the top-level file
            $bundledApp = $this->config['systemjs_output_dir'] . DIRECTORY_SEPARATOR . $app;
            $source = $systemjsDir . DIRECTORY_SEPARATOR . $app;
            $hashed = $hashUtility->get_hashed_name($bundledApp);
            $target = $this->config['staticfiles_static_root'] . DIRECTORY_SEPARATOR . $hashed;
            if (!is_file($target)) {
                symlink($source, $target);
            }
            $postProcessed[$app] = $hashed;
        }

        // append to the manifest
        $this->hashed_files = array_merge($this->hashed_files, $postProcessed);
        $this->saveManifest();
    }

    protected function getStoredName($file) {
        return $this->hashed_files[$file] ?: null;
    }
}
