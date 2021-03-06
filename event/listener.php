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
use modelbrouwers\mbstyles\staticfiles\storages\ManifestStaticFilesStorage;
use modelbrouwers\mbstyles\staticfiles\storages\CombinedManifestStaticFilesStorage;


class listener implements EventSubscriberInterface
{
    /**
     * phpBB config
     *
     * @var \phpbb\config\config
     */
    protected $config;

    /**
     * phpBB request object
     *
     * @var \phpbb\request\request
     */
    protected $request;

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
        \phpbb\request\request $request,
        \phpbb\template\twig\twig $template,
        \phpbb\user $user,
        $php_ext
    ) {
        $this->config = $config;
        $this->request = $request;
        // set up Raven asap
        // $this->registerErrorhandler();

        $this->template = $template;
        $this->user = $user;
        $this->php_ext = $php_ext;

        $this->user->add_lang_ext('modelbrouwers/mbstyles', 'acp');
        $this->cache = new StaticCache($this->config['staticfiles_cache_prefix']);
    }

    private function registerErrorhandler() {
        $this->request->enable_super_globals();  // needed for Raven
        $client = new \Raven_Client($this->config['raven_dsn']);
        $error_handler = new \Raven_ErrorHandler($client);
        $error_handler->registerExceptionHandler();
        $error_handler->registerErrorHandler();
        $error_handler->registerShutdownFunction();
        // $this->request->disable_super_globals();
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.page_footer'                  => 'core_page_footer',
            'core.acp_board_config_edit_add'    => 'core_config',
            // 'core.display_forums_modify_forum_rows' => 'core_display_forums_modify_forum_rows',
            'core.display_forums_modify_template_vars'   => 'core_display_forums_modify_template_vars',
        );
    }

    public function core_page_footer($event)
    {
        $storage = new ManifestStaticFilesStorage($this->config, $this->cache);
        $combined_storage = new CombinedManifestStaticFilesStorage($this->config, $this->cache);

        $this->template->assign_vars(array(
            'static' => $storage,
            'combined_static' => $combined_storage
        ));

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

    public function core_display_forums_modify_template_vars($event) {
        $subforums_row = $event['subforums_row'];
        foreach ($subforums_row as $i => &$subforum) {
            $querystring = parse_url($subforum['U_SUBFORUM'], PHP_URL_QUERY);
            parse_str($querystring, $query);
            $subforum['ID'] = $query['f'];
        }
        unset($subforum);
        $event['subforums_row'] = $subforums_row;
    }

    public function core_display_forums_modify_forum_rows($event) {
        $subforums = $event['subforums'];
        foreach ($subforums as &$forums) {
            foreach ($forums as $id => &$subforum) {
                $subforum['id'] = $id;
            }
            unset($subforum);
        }
        unset($forums);
        $event['subforums'] = $subforums;
    }
}
