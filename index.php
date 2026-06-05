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

    // Stash everything for inline rendering on the next page load. The notification is
    // rendered inline (between the live preview and the output) rather than as a top
    // banner, so the user lands at the iteration zone instead of having to scroll.
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

    // Anchor the redirect so the browser scrolls to the iteration zone on reload.
    $returnurl = clone $PAGE->url;
    $returnurl->set_anchor('runresult');
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$mform->display();

// Pull the stashed last-run result, if any, and render the iteration zone below the form:
// live preview (merged with executed command) -> notification -> output.
$lastrun = null;
if (!empty($SESSION->local_lsucli_lastrun)) {
    $lastrun = $SESSION->local_lsucli_lastrun;
    unset($SESSION->local_lsucli_lastrun);
}

// Live preview + executed-command share this div. The JS overwrites the EXECUTING text
// with the live preview as soon as the user changes any form field, signalling staleness.
echo '<div id="command-preview" class="lsucli-command-preview">';
if ($lastrun !== null) {
    echo 'EXECUTING: ' . s($lastrun->command);
} else {
    echo 'Command Preview: ';
}
echo '</div>';

if ($lastrun !== null) {
    echo '<div id="runresult">';
    echo $OUTPUT->notification($lastrun->message, $lastrun->messagetype);
    echo '<pre class="lsucli-output">' . s(implode("\n", $lastrun->output)) . '</pre>';
    echo '</div>';
}

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

    // Update on any form change. This naturally overwrites any server-rendered
    // "EXECUTING: ..." text from a previous run, signalling that the output is stale.
    document.querySelector('form.mform')?.addEventListener('change', updateCommandPreview);
    document.querySelector('form.mform')?.addEventListener('input', updateCommandPreview);

    // Initial update on a fresh page load — but skip if the server already rendered
    // an EXECUTING result, so the executed command stays visible until the user retouches the form.
    const previewDiv = document.getElementById('command-preview');
    if (previewDiv && !previewDiv.textContent.startsWith('EXECUTING:')) {
        updateCommandPreview();
    }
</script>