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
 * Main entry point for local_lsucli.
 *
 * @package    local_lsucli
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Steven Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");

require_once(__DIR__ . '/classes/form.php');

use local_lsucli\CLIScript;

admin_externalpage_setup('local_lsucli_runcli');

unset($SESSION->local_lsucli_lastrun, $SESSION->local_lsucli_lastformdata);

if (!isset($SESSION->local_lsucli_nonce)) {
    $SESSION->local_lsucli_nonce = random_string(20);
}

$mform = new lsucli_form(null, ['nonce' => $SESSION->local_lsucli_nonce]);

if ($data = $mform->get_data()) {
    $submittednonce = (string) ($data->lsucli_nonce ?? '');
    if (!hash_equals($SESSION->local_lsucli_nonce, $submittednonce)) {
        redirect(
            new moodle_url('/local/lsucli/index.php'),
            get_string('err_replay', 'local_lsucli'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
    if (!CLIScript::is_enabled($data->script)) {
        redirect(
            new moodle_url('/local/lsucli/index.php'),
            get_string('scriptdisabled', 'local_lsucli', $data->script),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    $command = $mform->build_cmd();
    $resultcode = null;

    $SESSION->local_lsucli_nonce = random_string(20);

    // Deal with the run mode.
    if (isset($data->runmode) && $data->runmode === 'adhoc') {
        $task = new \local_lsucli\task\run_cli_script();
        $task->set_custom_data([
            'script_name' => $data->script,
            'command' => $command
        ]);
        \core\task\manager::queue_adhoc_task($task);

        $url = new moodle_url('/admin/tool/task/adhoctasks.php');
        $link = \html_writer::link($url, get_string('adhoctasks', 'tool_task'));
        $msg = get_string('taskscheduled', 'local_lsucli') . ' ' . $link;
        redirect(
            new moodle_url('/local/lsucli/index.php'),
            $msg,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    $backurl = new moodle_url('/local/lsucli/index.php');

    $rerunform = '<form method="post" action="' . s($backurl->out(false)) . '" class="singlebutton lsucli-rerun-form">';
    foreach ($_POST as $key => $value) {
        if (is_array($value) || $key === 'sesskey' || $key === 'lsucli_nonce') {
            continue;
        }
        $rerunform .= '<input type="hidden" name="' . s($key) . '" value="' . s((string)$value) . '">';
    }
    $rerunform .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    $rerunform .= '<input type="hidden" name="lsucli_nonce" value="'
        . s($SESSION->local_lsucli_nonce) . '">';
    $rerunform .= '<button type="submit" class="btn btn-secondary">'
        . s(get_string('run_again', 'local_lsucli')) . '</button>';
    $rerunform .= '</form>';

    $actionrow = '<div class="lsucli-result-actions">'
        . $OUTPUT->single_button($backurl, get_string('back_to_lsucli', 'local_lsucli'), 'get')
        . $rerunform
        . '</div>';

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

    echo $actionrow;

    @ob_flush();
    @flush();

    echo '<div class="lsucli-outputwindow">';
    echo '<div class="lsucli-command-preview">'
        . s(get_string('executedcommand', 'local_lsucli')) . ': ' . s($command)
        . '</div>';

    echo '<pre class="lsucli-output">';

    $maxlines = (int) get_config('local_lsucli', 'maxoutputlines');
    $linecount = 0;

    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["redirect", 1]
    ];

    $process = proc_open($command, $descriptorspec, $pipes);

    if (is_resource($process)) {
        fclose($pipes[0]);

        while (($line = fgets($pipes[1])) !== false) {
            $linecount++;
            if ($maxlines > 0 && $linecount > $maxlines) {
                echo '.';
                if (($linecount - $maxlines) % 100 === 0) {
                    echo "\n";
                }
            } else {
                echo s($line);
            }
            @ob_flush();
            @flush();
        }
        fclose($pipes[1]);

        $resultcode = proc_close($process);
    } else {
        echo s("Error: Failed to open process.");
        $resultcode = -1;
    }

    if ($maxlines > 0 && $linecount > $maxlines) {
        echo "\n" . s(get_string('outputtruncated', 'local_lsucli', $maxlines)) . "\n";
    }

    echo '</pre>';
    echo '</div>';

    if ($resultcode === 0) {
        $message = get_string('runsuccess', 'local_lsucli', $data->script);
        $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    } else {
        $message = get_string('runfailed', 'local_lsucli', (object) [
            'script' => $data->script,
            'code' => $resultcode ?? -1,
        ]);
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    echo $OUTPUT->notification($message, $messagetype);
    echo $actionrow;

    echo $OUTPUT->footer();
    exit;
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$mform->display();

echo $OUTPUT->footer();
?>
<script>

    // Moodle themes don't always like setting titles on labels properly.
    document.querySelectorAll('input').forEach((e, i) => {
        var label = e.closest('label');
        if (label === null)
            return;
        label.setAttribute('title', e.getAttribute('title'));
        label.setAttribute('data-toggle', 'tooltip');
    });

    /*
    * Update the command preview as the form changes.
    */
    function updateCommandPreview() {
        const form = document.querySelector('form.mform');
        if (!form) return;

        const scriptSelect = form.querySelector('[name="script"]');
        if (!scriptSelect) return;

        const script = scriptSelect.value;
        let command = 'php admin/cli/' + script;

        // Find all enabled options for the selected script.
        const enabledCheckboxes = form.querySelectorAll('[name^="' + script + '_"][name$="_enabled"]:checked');
        enabledCheckboxes.forEach(checkbox => {
            const optionName = checkbox.name.replace(script + '_', '').replace('_enabled', '');
            const valueInput = form.querySelector('[name="' + script + '_' + optionName + '"]');

            if (valueInput) {
                if (valueInput.type === 'checkbox') {
                    if (!valueInput.name.endsWith('_enabled')) {
                        const val = valueInput.checked ? 'true' : 'false';
                        command += ' --' + optionName + '=' + val;
                    } else {
                        command += ' --' + optionName;
                    }
                } else if (valueInput.type === 'text' && valueInput.value) {
                    command += ' --' + optionName + '=' + valueInput.value;
                }
            } else {
                command += ' --' + optionName;
            }
        });

        const previewDiv = document.getElementById('command-preview');
        if (previewDiv) {
            previewDiv.textContent = 'Command Preview: ' + command;
        }
    }

    document.querySelector('form.mform')?.addEventListener('change', updateCommandPreview);
    document.querySelector('form.mform')?.addEventListener('input', updateCommandPreview);

    if (document.getElementById('command-preview')) {
        updateCommandPreview();
    }
</script>
