<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adhoc task to run CLI script for local_lsucli.
 *
 * @package    local_lsucli
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Steven Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
