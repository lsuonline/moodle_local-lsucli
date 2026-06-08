<?php
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