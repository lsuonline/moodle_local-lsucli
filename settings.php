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
 * Settings definition for local_lsucli.
 *
 * @package    local_lsucli
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Steven Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'localplugins',
        new admin_category(
            'local_lsucli',
            get_string('lsuclifolder', 'local_lsucli')
        )
    );

    $ADMIN->add(
        'local_lsucli',
        new admin_externalpage(
            'local_lsucli_cliconfig',
            get_string('cliconfiguration', 'local_lsucli'),
            new moodle_url('/local/lsucli/cliconfig.php'),
            'moodle/site:config'
        )
    );

    $settingspage = new admin_settingpage(
        'local_lsucli_settings',
        get_string('settings')
    );

    // Only true when Moodle needs the actual setting controls.
    if ($ADMIN->fulltree) {
        $settingspage->add(new admin_setting_heading(
            'local_lsucli/overview_heading',
            get_string('settings_overview_heading', 'local_lsucli'),
            get_string('settings_overview_desc', 'local_lsucli')
        ));

        $pathtophp = !empty($CFG->pathtophp)
            ? '<code>' . s($CFG->pathtophp) . '</code>'
            : '<em>' . get_string('settings_phpbinary_unset', 'local_lsucli') . '</em>';

        // Show the PHP binary we're using.
        $settingspage->add(new admin_setting_heading(
            'local_lsucli/phpbinary_heading',
            get_string('settings_phpbinary_heading', 'local_lsucli'),
            get_string('settings_phpbinary_desc', 'local_lsucli', (object) ['pathtophp' => $pathtophp])
        ));

        // Limit how much script output we render.
        $settingspage->add(new admin_setting_configtext(
            'local_lsucli/maxoutputlines',
            get_string('maxoutputlines', 'local_lsucli'),
            get_string('maxoutputlines_desc', 'local_lsucli'),
            5000,
            PARAM_INT
        ));

        // Enabled scripts allowlist setting.
        require_once(__DIR__ . '/classes/cli_script.php');
        $scriptchoices = [];
        $scriptdefaults = [];
        foreach (\local_lsucli\CLIScript::list_filenames() as $file) {
            $key = \local_lsucli\CLIScript::normalize_name($file);
            $scriptchoices[$key] = $file;
            $scriptdefaults[$key] = 1;
        }
        $settingspage->add(new admin_setting_configmulticheckbox(
            'local_lsucli/enabledscripts',
            get_string('settings_enabledscripts', 'local_lsucli'),
            get_string('settings_enabledscripts_desc', 'local_lsucli'),
            $scriptdefaults,
            $scriptchoices
        ));
    }

    $ADMIN->add('local_lsucli', $settingspage);

    $ADMIN->add(
        'local_lsucli',
        new admin_externalpage(
            'local_lsucli_runcli',
            get_string('runcli', 'local_lsucli'),
            new moodle_url('/local/lsucli/index.php'),
            'moodle/site:config'
        )
    );
}
