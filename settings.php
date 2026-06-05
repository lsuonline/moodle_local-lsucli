<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'localplugins',
        new admin_category(
            'local_lsucli',
            get_string('lsuclifolder', 'local_lsucli')
        )
    );

    $ADMIN->add(
        'local_lsucli',
        new admin_externalpage(
            'local_lsucli_cliconfig',
            get_string('cliconfiguration', 'local_lsucli'),
            new moodle_url('/local/lsucli/cliconfig.php'),
            'moodle/site:config'
        )
    );

    $settingspage = new admin_settingpage(
        'local_lsucli_settings',
        get_string('settings')
    );
    $ADMIN->add('local_lsucli', $settingspage);

    $ADMIN->add(
        'local_lsucli',
        new admin_externalpage(
            'local_lsucli_runcli',
            get_string('runcli', 'local_lsucli'),
            new moodle_url('/local/lsucli/index.php'),
            'moodle/site:config'
        )
    );
}
