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
 * Language strings for local_lsucli.
 *
 * @package    local_lsucli
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Steven Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LSU CLI Tasks';
$string['lsucli'] = 'LSU CLI Tasks';
$string['lsucli_desc'] = 'Run CLI scripts as adhoc tasks.';
$string['scriptname'] = 'Script name';
$string['err_required'] = 'This field is required.';
$string['confighelptext'] = 'Configure CLI Help Text';
$string['helptext'] = 'Help text';
$string['changessaved'] = 'Changes saved successfully.';
$string['lsuclifolder'] = 'LSUCLI';
$string['cliconfiguration'] = 'CLI configuration';
$string['runcli'] = 'Run CLI';
$string['settings_overview_heading'] = 'Overview';
$string['settings_overview_desc'] = 'This plugin lets administrators run Moodle CLI scripts from the web UI. Use <strong>Run CLI</strong> to execute a script and <strong>CLI configuration</strong> to customise per-script help text.';
$string['settings_phpbinary_heading'] = 'PHP binary';
$string['settings_phpbinary_desc'] = 'Scripts are executed using the PHP binary configured in <strong>Site administration &rarr; Server &rarr; System paths</strong> (<code>$CFG-&gt;pathtophp</code>).<br />Current value: {$a->pathtophp}';
$string['settings_phpbinary_unset'] = 'not set';
$string['maxoutputlines'] = 'Maximum output lines';
$string['maxoutputlines_desc'] = 'Maximum number of lines of script output to display on the <em>Run CLI</em> page after a script finishes. Set to 0 for no limit.';
$string['outputtruncated'] = 'Output truncated to {$a} lines (configurable in plugin settings).';
$string['runsuccess'] = 'Successfully ran {$a}.';
$string['runfailed'] = 'Script {$a->script} failed (exit code {$a->code}).';
$string['settings_enabledscripts'] = 'Enabled scripts';
$string['settings_enabledscripts_desc'] = 'Allowlist of CLI scripts that the <em>Run CLI</em> page is allowed to execute. Only the scripts checked here can be selected and run from the form. Uncheck destructive scripts (for example <code>delete_course.php</code> or <code>uninstall_plugins.php</code>) to keep them out of the web UI.';
$string['scriptdisabled'] = 'The script "{$a}" is not enabled. An administrator must enable it under Site administration &rarr; Plugins &rarr; Local plugins &rarr; LSUCLI &rarr; Settings before it can be run.';
$string['back_to_lsucli'] = 'Back to LSU CLI';
$string['run_again'] = 'Run again';
$string['executedcommand'] = 'Executed command';
$string['err_replay'] = 'This run has already been processed. Use "Run again" or resubmit the form to execute it again.';
$string['runmode'] = 'Execution mode';
$string['runmode_inline'] = 'Run now (in browser)';
$string['runmode_adhoc'] = 'Schedule as adhoc task';
$string['taskscheduled'] = 'Task scheduled successfully.';
