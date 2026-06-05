<?php
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");

require_once(__DIR__ . '/classes/form.php');

admin_externalpage_setup('local_lsucli_runcli');
$PAGE->requires->css('/local/lsucli/styles.css');

$mform = new lsucli_form();

if ($data = $mform->get_data()) {
    $command = $mform->build_cmd();
    $resultcode = null;
    exec($command, $output, $resultcode);

    // Limit how much script output we render based on admin settings.
    $maxlines = (int) get_config('local_lsucli', 'maxoutputlines');
    if ($maxlines > 0 && count($output) > $maxlines) {
        $output = array_slice($output, 0, $maxlines);
        $output[] = get_string('outputtruncated', 'local_lsucli', $maxlines);
    }

    // Store the output from the last run.
    $SESSION->local_lsucli_lastrun = (object) [
        'command' => $command,
        'exitcode' => $resultcode,
        'output' => $output,
    ];

    if ($resultcode === 0) {
        redirect(
            $PAGE->url,
            get_string('runsuccess', 'local_lsucli', $data->script),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            $PAGE->url,
            get_string('runfailed', 'local_lsucli', (object) [
                'script' => $data->script,
                'code' => $resultcode ?? -1,
            ]),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

// Render the stored output
if (!empty($SESSION->local_lsucli_lastrun)) {
    $lastrun = $SESSION->local_lsucli_lastrun;
    unset($SESSION->local_lsucli_lastrun);

    echo '<pre>';
    echo 'EXECUTING: ' . s($lastrun->command) . "\n";
    echo s(implode("\n", $lastrun->output));
    echo '</pre>';
}

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

    // Command preview functionality.
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
                    // BOOL type - check if it's the toggle (not the enable checkbox).
                    if (!valueInput.name.endsWith('_enabled')) {
                        const val = valueInput.checked ? 'true' : 'false';
                        command += ' --' + optionName + '=' + val;
                    } else {
                        // FLAG type - just the option name.
                        command += ' --' + optionName;
                    }
                } else if (valueInput.type === 'text' && valueInput.value) {
                    command += ' --' + optionName + '=' + valueInput.value;
                }
            } else {
                // FLAG type - no value input, just the checkbox.
                command += ' --' + optionName;
            }
        });

        document.getElementById('command-preview').textContent = 'Command Preview: ' + command;
    }

    // Update on any form change.
    document.querySelector('form.mform')?.addEventListener('change', updateCommandPreview);
    document.querySelector('form.mform')?.addEventListener('input', updateCommandPreview);

    // Initial update.
    updateCommandPreview();
</script>