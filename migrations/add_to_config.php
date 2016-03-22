<?php


namespace modelbrouwers\mbstyles\migrations;


class add_to_config extends \phpbb\db\migration\migration
{
    public function update_data()
    {
        return array(
            array('config.add', array('staticfiles_cache_prefix', getenv('KEY_PREFIX') ?: '')),
            array('config.add', array('staticfiles_static_root', '/path/to/static/')),
            array('config.add', array('staticfiles_static_url', getenv('STATIC_URL') ?: '/static/')),
            array('config.add', array('systemjs_output_dir', getenv('SYSTEMJS_OUTPUT_DIR') ?: 'SYSTEMJS')),
            array('config.add', array('raven_dsn', getenv('RAVEN_DSN') ?: '')),
        );
    }
}
