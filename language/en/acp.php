<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    'STATICFILES_CONFIG'        => 'Static files settings',
    'MEMCACHED_PREFIX'          => 'Memcached key prefix',
    'STATICFILES_STATIC_ROOT'   => 'Staticfiles root',
    'STATICFILES_STATIC_URL'    => 'Staticfiles url',
    'SYSTEMJS_OUTPUT_DIR'       => 'SystemJS output directory',
    'LEGEND_MONITORING'         => 'Application monitoring',
    'RAVEN_DSN'                 => 'Raven DSN',
));
