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
 * @copyright  2026 onwards Steve Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lsucli\task;

defined('MOODLE_INTERNAL') || die();

class run_cli_script extends \core\task\adhoc_task {

    /**
     * Executes the task to run the specified CLI script with arguments.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        $customdata = $this->get_custom_data();
        $command = $customdata->command ?? null;

        // Fallback for previously scheduled tasks that only had script_name.
        if (!$command && isset($customdata->script_name)) {
            $script_name = basename($customdata->script_name);
            $script_path = $CFG->dirroot . '/admin/cli/' . $script_name;
            if (file_exists($script_path)) {
                $phpbinary = $CFG->pathtophp;
                $command = escapeshellarg($phpbinary) . ' ' . escapeshellarg($script_path);
            }
        }

        // Run this as a scheduled task based on the command sent.
        if ($command) {
            mtrace("Executing command: " . $command);

            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["redirect", 1]
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                fclose($pipes[0]);

                while (($line = fgets($pipes[1])) !== false) {
                    mtrace(trim($line));
                }
                fclose($pipes[1]);

                $resultcode = proc_close($process);

                if ($resultcode !== 0) {
                    mtrace("Command failed with result code: " . $resultcode);
                }
            } else {
                mtrace("Error: Failed to open process.");
            }
        } else {
            mtrace("Error: No valid command or script_name provided.");
        }
    }
}
