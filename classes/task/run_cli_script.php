<?php
namespace local_lsucli\task;

defined('MOODLE_INTERNAL') || die();

class run_cli_script extends \core\task\adhoc_task {
    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;

        $script_name = $this->get_custom_data()->script_name;
        $script_path = $CFG->dirroot . '/admin/cli/' . $script_name;

        if (file_exists($script_path)) {
            $cmd = escapeshellarg($CFG->pathtophp) . ' ' . escapeshellarg($script_path);
            proc_open($cmd, [], $pipes);
        }
    }
}
