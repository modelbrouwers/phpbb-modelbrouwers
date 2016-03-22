<?php

namespace modelbrouwers\mbstyles\staticfiles;


class StaticCache extends \Memcached {
    protected $TIMEOUT = 900; // 15 minutes

    public function __construct($prefix_key, $persistent_id='') {
        parent::__construct($persistent_id);

        $this->setOption(\Memcached::OPT_PREFIX_KEY, $prefix_key);
    }

    /**
     * Set up the connection
     */
    function init() {
        $server_list = $this->getServerList();
        if(count($server_list) === 0) {
            $this->addServer('localhost', 11211);
        }
    }

    function set($key, $value, $expiration=null, $udf_flags=null) {
        if(!$expiration) {
            $expiration = $this->TIMEOUT;
        }
        parent::set($key, $value, $expiration);
    }
}
