<?php
defined('MOODLE_INTERNAL') || die();

function local_lsucli_extend_settings_navigation($settingsnav, $context) {
    global $CFG;

    if (!is_siteadmin()) {
        return;
    }

    if ($tasksnode = $settingsnav->find('server', navigation_node::TYPE_SYSTEM)) {
        $url = new moodle_url('/local/lsucli/index.php');
        $lsuclinode = navigation_node::create(
            get_string('lsucli', 'local_lsucli'),
            $url,
            navigation_node::NODETYPE_LEAF,
            'lsucli',
            'lsucli',
            new pix_icon('i/scheduledtasks', '')
        );
        $tasksnode->add_node($lsuclinode);
    }
}
