<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'development',
        new admin_externalpage(
            'local_lsucli',
            get_string('pluginname', 'local_lsucli'),
            new moodle_url('/local/lsucli/index.php'),
            'moodle/site:config'
        )
    );
}
