<?php
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");

require_once(__DIR__ . '/classes/form.php');

use local_lsucli\CLIScript;

admin_externalpage_setup('local_lsucli_runcli');
$PAGE->requires->css('/local/lsucli/styles.css');

$action = optional_param('action', '', PARAM_ALPHA);

$mform = new lsucli_form();

if ($data = $mform->get_data()) {
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
    exec($command, $output, $resultcode);

    // Limit how much script output we render based on admin settings.
    $maxlines = (int) get_config('local_lsucli', 'maxoutputlines');
    if ($maxlines > 0 && count($output) > $maxlines) {
        $output = array_slice($output, 0, $maxlines);
        $output[] = get_string('outputtruncated', 'local_lsucli', $maxlines);
    }

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

    $SESSION->local_lsucli_lastrun = (object) [
        'command' => $command,
        'exitcode' => $resultcode,
        'output' => $output,
        'message' => $message,
        'messagetype' => $messagetype,
    ];

    $SESSION->local_lsucli_lastformdata = $data;

    redirect(new moodle_url('/local/lsucli/index.php', ['action' => 'showresult']));
}

if ($action === 'showresult' && !empty($SESSION->local_lsucli_lastrun)) {
    $lastrun = $SESSION->local_lsucli_lastrun;

    $backurl = new moodle_url('/local/lsucli/index.php');
    $rerunurl = new moodle_url('/local/lsucli/index.php', ['action' => 'runagain']);

    // Render the action row twice
    $actionrow = '<div class="lsucli-result-actions">'
        . $OUTPUT->single_button($backurl, get_string('back_to_lsucli', 'local_lsucli'), 'get')
        . $OUTPUT->single_button($rerunurl, get_string('run_again', 'local_lsucli'), 'get')
        . '</div>';

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

    echo $OUTPUT->notification($lastrun->message, $lastrun->messagetype);
    echo $actionrow;
    echo '<div class="lsucli-command-preview">'
        . s(get_string('executedcommand', 'local_lsucli')) . ': ' . s($lastrun->command)
        . '</div>';
    echo '<pre class="lsucli-output">' . s(implode("\n", $lastrun->output)) . '</pre>';
    echo $actionrow;

    echo $OUTPUT->footer();
    exit;
}

if ($action === 'runagain' && !empty($SESSION->local_lsucli_lastformdata)) {
    $mform->set_data($SESSION->local_lsucli_lastformdata);
}
unset($SESSION->local_lsucli_lastrun);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$mform->display();

echo '<div id="command-preview" class="lsucli-command-preview">Command Preview: </div>';

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
