<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    if ($tasksnode = $ADMIN->get_node('server')->get_node('tasks')) {
        $url = new moodle_url('/local/lsucli/index.php');
        $lsuclinode = new admin_externalpage(
            'local_lsucli',
            get_string('lsucli', 'local_lsucli'),
            $url,
            'moodle/site:config'
        );
        $tasksnode->add_node($lsuclinode);
    }
}
