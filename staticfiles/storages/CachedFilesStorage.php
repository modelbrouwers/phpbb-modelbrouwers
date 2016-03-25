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
 * This class builds the hashed filenames similar to Django's cached storage.
 * Because PHP is a lot dumber and I don't want to spend too much effort,
 * we assume Django's collectstatic has been run and the files are present.
 * For when a file is thus requested, we calculate the hash and cache it in
 * Memcached. If the hashed file doesn't exist, return the unchanged URL.
 * Django updates the cached filenames as part of the collectstatic command.
 */
class CachedFilesStorage extends StaticFilesStorage
{
    protected $base_url;
    protected $cache_key_prefix;

    protected $cache = null;

    public function __construct($cache) {
        parent::__construct();
        global $settings;
        $this->cache_key_prefix = 'staticfiles:';
        $this->cache = $cache;
        $this->hash_utility = new HashUtility($this);
    }

    /**
     * @param $name is the filename relative to the static root
     */
    protected function cache_key($name) {
        // don't do utf8 encoding, the hashes differ, even with utf8-strings
        return $this->cache_key_prefix.md5($name);
    }

    /**
     * Get the url to the static file, either from cache or calculate the hashed path.
     */
    public function url($file) {
        $cache_key = $this->cache_key($file);
        $hashed_name = ($this->DEBUG) ? false : $this->cache->get($cache_key);

        if($hashed_name === false) {
            $hashed_name = $this->hash_utility->get_hashed_name($file);
            if(!$this->DEBUG) $this->cache->set($cache_key, $hashed_name);
        }
        return $this->base_url . $hashed_name;
    }
}
