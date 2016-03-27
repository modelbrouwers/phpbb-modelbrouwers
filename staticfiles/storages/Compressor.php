<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/

namespace modelbrouwers\mbstyles\staticfiles\storages;


class Compressor {

    protected $CACHE_DIR = 'PHP_CACHE';

    protected $storage;

    public function __construct(StaticFilesStorage $storage) {
        $this->storage = $storage;
    }

    /**
     *  Used in DEBUG mode: return the files as separete <script> entries, no
     *  compressing is done.
     */
    public function uncompressed($files, $ext) {
        $base_url = $this->storage->get_base_url();
        $urls = array_map(function($path) use ($base_url) {
            return $base_url . $path;
        }, $files);

        if ($ext == 'js') {
            $glue = "\"></script>\n<script src=\"";
            return implode($glue, $urls);
        } else {
            return '';
        }
    }

    protected function get_cache_dir() {
        $dirname = $this->storage->get_location() . DIRECTORY_SEPARATOR . $this->CACHE_DIR;
        var_dump($dirname);
        if (!is_dir($dirname)) {
            mkdir($dirname);
            chmod($dirname, 0755);
        }
        return $dirname;
    }

    public function compress($file_paths, $ext) {
        $md5_result = md5(implode("::", $file_paths));
        $destDir = $this->get_cache_dir();
        $dest_file = $destDir . DIRECTORY_SEPARATOR . $md5_result.'.'.$ext;

        if(!is_file($dest_file)) {
            foreach ($file_paths as $filename) {
                $_output[] = file_get_contents($filename);
            }
            $output = implode("\n;", $_output);
            if ($ext == 'js') {
                $output = \JShrink\Minifier::minify($output);
            }
            file_put_contents($dest_file, $output);
        }

        return substr($dest_file, strlen($this->storage->get_location())+1);
    }

    public function getBundleScripts($apps) {
        $filenames = array();
        $combinedUrl = $this->storage->url($apps, $ext='js');
        return "<script type=\"text/javascript\" src=\"{$combinedUrl}\"></script>";
    }
}
