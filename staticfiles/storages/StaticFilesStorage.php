<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/

namespace modelbrouwers\mbstyles\staticfiles\storages;


class StaticFilesStorage
{
    /**
     * phpBB config
     *
     * @var \phpbb\config\config
     */
    protected $config;

    /**
     * root path on the file system of this storage
     *
     * @var string
     */
    protected $location = null;

    /**
     * base url that serves the static files (/static/, or http://static.foo.com)
     *
     * @var string
     */
    protected $base_url = null;

    /**
     * @var boolean
     */
    public $DEBUG = false;

    protected $systemjs_output_dir;

    // Use dependency injection?
    public function __construct(\phpbb\config\config $config) {
        $this->DEBUG = (bool) getenv('DEBUG') || defined('DEBUG');

        $this->config = $config;

        $this->base_url = $config['staticfiles_static_root'];
        $this->systemjs_output_dir = $config['systemjs_output_dir'];
    }

    /**
     * Get the url to the static file, either from cache or calculate the hashed path.
     */
    public function url($file) {
        return $this->base_url . $file;
    }

    public function get_location() {
        return $this->location;
    }

    public function get_base_url() {
        return $this->base_url;
    }

    public function get_systemjs_output_dir() {
        return $this->systemjs_output_dir;
    }

    public function system_import($appOrArray) {

        if (!is_array($appOrArray)) {
            $apps = array($appOrArray);
        } else {
            $apps = $appOrArray;
        }

        if ($this->DEBUG) {
            $imports = array_reduce($apps, function($reduced, $app) {
                $reduced .= "\tSystem.import('{$app}');\n";
                return $reduced;
            }, '');
            return "<script type=\"text/javascript\">\n". $imports . "</script>";
        } else {
            return $this->getBundleScripts($apps);
        }
    }

    protected function getBundleScripts($apps) {
        $imports = '';
        foreach ($apps as $app) {
            $filename = $this->systemjs_output_dir . DIRECTORY_SEPARATOR . $app;
            $url = $this->url($filename);
            $imports .= "\n" . "<script type=\"text/javascript\" src=\"{$url}\"></script>";
        }
        return $imports;
    }
}
