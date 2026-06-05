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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/cli_option.php');
require_once(__DIR__ . '/classes/cli_script.php');
require_once(__DIR__ . '/classes/form/helptext_form.php');

use local_lsucli\CLIScript;
use local_lsucli\form\helptext_form;

admin_externalpage_setup('local_lsucli_cliconfig');

$scripts = CLIScript::gen_scripts();

$form = new helptext_form(null, ['scripts' => $scripts]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/lsucli/index.php'));
} else if ($data = $form->get_data()) {
    foreach ($scripts as $scriptname => $script) {
        $fieldname = 'helptext_' . $scriptname;
        $helptext = $data->$fieldname ?? '';

        $record = $DB->get_record('local_lsucli_helptext', ['scriptname' => $scriptname]);
        if ($record) {
            $record->helptext = $helptext;
            $record->timemodified = time();
            $DB->update_record('local_lsucli_helptext', $record);
        } else if (!empty($helptext)) {
            $DB->insert_record('local_lsucli_helptext', [
                'scriptname' => $scriptname,
                'helptext' => $helptext,
                'timemodified' => time(),
            ]);
        }
    }
    redirect(
        new moodle_url('/local/lsucli/cliconfig.php'),
        get_string('changessaved', 'local_lsucli'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$formdata = [];
foreach ($scripts as $scriptname => $script) {
    $record = $DB->get_record('local_lsucli_helptext', ['scriptname' => $scriptname]);
    $formdata['helptext_' . $scriptname] = $record->helptext ?? $script->help_text;
}
$form->set_data($formdata);

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
