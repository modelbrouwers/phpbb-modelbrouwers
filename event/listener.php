<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/
namespace modelbrouwers\mbstyles\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* @ignore
*/
use modelbrouwers\mbstyles\staticfiles\StaticCache;


class listener implements EventSubscriberInterface
{
    /**
     * phpBB config
     *
     * @var \phpbb\config\config
     */
    protected $config;

    /**
     * phpBB template object
     *
     * @var \phpbb\template\twig\twig
     */
    protected $template;

    /**
     * phpBB user object
     *
     * @var \phpbb\user
     */
    protected $user;

    /**
     * php file extension
     *
     * @var string
     */
    protected $php_ext;

    /**
     * Memcached instance
     *
     * @var \modelbrouwers\mbstyles\staticfiles\StaticCache
     */

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\template\twig\twig $template,
        \phpbb\user $user,
        $php_ext
    ) {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;
        $this->php_ext = $php_ext;

        $this->user->add_lang_ext('modelbrouwers/mbstyles', 'acp');
        $this->cache = new StaticCache($this->config['staticfiles_cache_prefix']);
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.page_footer'                  => 'core_page_footer',
            'core.acp_board_config_edit_add'    => 'core_config',
        );
    }

    public function core_page_footer($event)
    {

        // var_dump($this->cache);


        // $this->template->assign_vars(array(
        // ));

    }

    public function core_config($event)
    {
        if ($event['mode'] !== 'server') {
            return;
        }

        $display_vars = $event['display_vars'];

        $extra_config = array(
            'legend_staticfiles' => 'STATICFILES_CONFIG',
            'staticfiles_cache_prefix' => array(
                'lang' => 'MEMCACHED_PREFIX',
                'validate' => 'string',
                'type' => 'text::255',
                'explain' => true,
            ),
            'staticfiles_static_root' => array(
                'lang' => 'STATICFILES_STATIC_ROOT',
                'validate' => 'string',
                'type' => 'text::255',
                'explain' => true,
            ),
            'staticfiles_static_url' => array(
                'lang' => 'STATICFILES_STATIC_URL',
                'validate' => 'string',
                'type' => 'text::255',
                'explain' => true,
            ),
            'systemjs_output_dir' => array(
                'lang' => 'SYSTEMJS_OUTPUT_DIR',
                'validate' => 'string',
                'type' => 'text::255',
                'explain' => true,
            ),
            'legend_monitoring' => 'LEGEND_MONITORING',
            'raven_dsn' => array(
                'lang' => 'RAVEN_DSN',
                'validate' => 'string',
                'type' => 'text::255',
                'explain' => true,
            ),
        );

        // insert before the confirm button
        $position = array_search('legend4', array_keys($display_vars['vars']));
        $display_vars['vars'] = array_merge(
                array_slice($display_vars['vars'], 0, $position),
                $extra_config,
                array_slice($display_vars['vars'], $position)
            );
        $event['display_vars'] = $display_vars;
    }
}
