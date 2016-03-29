<?php
/**
*
* @package phpBB Extension - modelbrouwers styles
* @copyright (c) 2016 Sergei Maertens
* @license http://opensource.org/licenses/MIT MIT License
*
*/
namespace modelbrouwers\mbstyles\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use modelbrouwers\mbstyles\staticfiles\StaticCache;
use modelbrouwers\mbstyles\staticfiles\storages\ManifestStaticFilesStorage;


class systemjs extends \phpbb\console\command\command
{
    /** @var \phpbb\extension\manager */
    protected $extension_manager;

    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\path_helper */
    protected $path_helper;

    /** @var \phpbb\template\context */
    protected $context;

    /** @var string phpBB root path */
    protected $phpbb_root_path;

    function __construct(
        \phpbb\user $user,
        \phpbb\extension\manager $extension_manager,
        \phpbb\config\config $config,
        \phpbb\path_helper $path_helper,
        \phpbb\template\context $context,
        $phpbb_root_path
    )
    {
        $this->extension_manager = $extension_manager;
        $this->config = $config;
        $this->path_helper = $path_helper;
        $this->context = $context;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->cache = new StaticCache($this->config['staticfiles_cache_prefix']);
        parent::__construct($user);
    }

    protected function configure()
    {
        $this
            ->setName('systemjs:bundle')
            ->setDescription('Bundles the Javascript modules with jspm.')
            ->addOption(
               'jspm-executable',
               null,
               InputOption::VALUE_REQUIRED,
               'Specify the jspm executable',
               'jspm'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new \phpbb\template\twig\loader('');
        $twig = new \phpbb\template\twig\environment(
            $this->config,
            $this->path_helper,
            $this->extension_manager,
            $loader,
            array(
                'cache'         => false,
                'debug'         => false,
                'auto_reload'   => false,
                'autoescape'    => false,
            )
        );
        $twig->addExtension(
            new \phpbb\template\twig\extension(
                $this->context,
                $this->user
            )
        );
        $lexer = new \phpbb\template\twig\lexer($twig);
        $twig->setLexer($lexer);

        // it's a non-default feature, so we only look in extension styles
        // $styles_dir = realpath($this->phpbb_root_path . DIRECTORY_SEPARATOR . 'styles');
        $ext_dir = realpath($this->phpbb_root_path . DIRECTORY_SEPARATOR . 'ext');
        $output->writeln('<info>Looking for styles in extension styles \'' . $ext_dir . '\'</info>');

        $extensions = array();
        $iterator = new \RecursiveIteratorIterator(
            new \phpbb\recursive_dot_prefix_filter_iterator(
                new \RecursiveDirectoryIterator(
                    $ext_dir,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                )
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $iterator->setMaxDepth(2);

        foreach ($iterator as $file_info)
        {
            if ($file_info->getFilename() !== 'styles' || $iterator->getDepth() !== 2 ) {
                continue;
            }
            $ext_name = substr($file_info->getPathName(), strlen($ext_dir));
            $output->writeln('<info>Adding \'' . $ext_name . '\' to template search path');
            $extensions[] = $file_info->getPathName();
        }

        $output->writeln('<info>Parsing templates for bundle markers...</info>');
        $appsFound = array();
        foreach ($extensions as $styles_dir) {
            $directory = new \RecursiveDirectoryIterator($styles_dir);
            $iterator = new \RecursiveIteratorIterator($directory);
            $templates = new \RegexIterator($iterator, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH);

            foreach ($templates as $name => $fileObj) {
                $output->writeln('<info>Checking ' . $name . '</info>');
                $source = file_get_contents($name);
                $stream = $twig->tokenize($source, $name);
                $nodes = $twig->parse($stream);

                $flattened = array();
                $body = $nodes->getNode('body');
                foreach ($body as $node) {
                    $this->getNodes($node, $flattened);
                }

                // now loop over the nodes, and if a Expression_AssignName is found, group the following nodes
                $assignments = array();
                $lastAssign = null;
                foreach ($flattened as $node) {
                    if (is_a($node, '\Twig_Node_Expression_AssignName')) {
                        $lastAssign = $node->getAttribute('name');
                        $assignments[$lastAssign] = array();
                    } elseif (is_a($node, '\Twig_Node_Expression_Constant') && $lastAssign) {
                        if (is_string($node->getAttribute('value'))) {
                            $assignments[$lastAssign][] = $node->getAttribute('value');
                        }
                    } else {
                        $lastAssign = null;
                    }
                }

                // we only accept the variable name 'systemjs_apps' as second best option
                if (!isset($assignments['systemjs_apps'])) {
                    continue;
                }
                $appsFound[$name] = $assignments['systemjs_apps'];
            }
        }

        $apps = array_reduce($appsFound, function($reduced, $_apps) {
            return array_merge($reduced, $_apps);
        }, array());
        $numApps = count($apps);
        $numTemplates = count(array_keys($appsFound));

        $output->writeln("<info>Found {$numApps} apps in {$numTemplates} templates</info>");

        $jspm = $input->getOption('jspm-executable');
        $cmdTpl = "{$jspm} bundle %s %s --log err 2>&1";
        $systemjsDir = $this->config['staticfiles_static_root'] .
                       DIRECTORY_SEPARATOR . $this->config['systemjs_output_dir'];
        if (!is_dir($systemjsDir)) {
            mkdir($systemjsDir);
        }
        $systemjsDir = realpath($systemjsDir);

        // we need to be in the jspm package.json search path
        $storage = new ManifestStaticFilesStorage($this->config, $this->cache);
        chdir($this->config['staticfiles_static_root']);
        foreach ($apps as $app) {
            $source = $this->config['staticfiles_static_root'] .DIRECTORY_SEPARATOR . $app;
            $dest = $systemjsDir . DIRECTORY_SEPARATOR . $app;
            $cmd = sprintf($cmdTpl, $source, $dest);
            $output->writeln("<comment>Bundling \"{$app}\" ...</comment>");
            $output->writeln($cmd);
            $_output = exec($cmd, $out, $exitCode);
            if ($_output || $exitCode != 0) {
                $output->writeln("<error>Bundle for \"$app\" failed with: {$_output}</error>");
                continue;
            }

            // add the System.import statement
            $js = "\nSystem.import('{$app}');\n";
            $retval = file_put_contents($dest, $js, FILE_APPEND | LOCK_EX);

            if (!is_file($dest)) {
                $output->writeln("<error>Bundle for \"$app\" failed...</error>");
                continue;
            }

            // post process
            // $hash = md5_file($dest);
            // $hash = substr($hash, 0, 12);

            // $info = pathinfo($file);

            // $link = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '.' . $hash . '.' . $info['extension'];
            // if (!is_file($link)) {
            //     symlink($dest, $link);
            //     $relative = substr($link, strlen($settings->STATIC_ROOT) + 1);
            //     $output->writeln("<info>Post-processed file \"{$relative}\"</info>");
            // } else {
            //     $output->writeln("<info>Skipped post-processing: link exists</info>");
            // }
        }

        $storage->postProcess($apps);
    }

    // flatten the nodes
    protected function getNodes($parentNode, &$flattened) {
        foreach ($parentNode as $node) {
            if (count($node) > 0) {
                $this->getNodes($node, $flattened);
            } else {
                $flattened[] = $node;
            }
        }
    }
}
