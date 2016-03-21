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
        \phpbb\template\twig\twig $template,
        \phpbb\user $user,
        $php_ext
    ) {
        $this->template = $template;
        $this->user = $user;
        $this->php_ext = $php_ext;
        $this->cache = new StaticCache('dummy-key');
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.page_footer'          => 'core_page_footer',
        );
    }

    public function core_page_footer($event)
    {

        var_dump($this->cache);


        // $this->template->assign_vars(array(
        // ));

    }
}
