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
 * CLI script class for local_lsucli.
 *
 * @package    local_lsucli
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Steven Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lsucli;

defined('MOODLE_INTERNAL') || die();

if (!function_exists('array_find_key')) {
    #PHP version is less than 8.4
    /**
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param (callable(TValue $value, TKey $key): bool)|(callable(TValue $value): bool) $callback
     * @return TKey|null
     * @since 8.4
     */
    function array_find_key(array $array, callable $callback): mixed {
        foreach ($array as $k => $v) {
            if ($callback($v, $k)) {
                return $k;
            }
        }
        return null;
    }
}

class CLIScript {

    /** @var string The full path to the script file. */
    public string $file_path;

    /** @var string The normalized name of the script. */
    public string $file_name;

    /** @var string The help text associated with the script. */
    public string $help_text;

    /** @var CLIOption[] An array of options parsed from the help text. */
    public array $options;

    /** @var string The example usage text associated with the script. */
    public string $example_text;

    /**
     * Constructs a new CLIScript instance.
     *
     * @param string $file_name The name of the CLI script.
     */
    public function __construct($file_name) {
        global $CFG, $DB;

        $file_name = basename($file_name);
        $this->file_path = "$CFG->dirroot/admin/cli/$file_name";
        $this->file_name = self::normalize_name($file_name);

        // Check for custom help text in the database first.
        $custom = $DB->get_record('local_lsucli_helptext', ['scriptname' => $this->file_name]);
        if ($custom && !empty($custom->helptext)) {
            $this->help_text = $custom->helptext;
        } else {
            $this->grep_help_text();
        }
        $this->parse_help_text();
    }

    /**
     * Extracts the help text directly from the script file content.
     *
     * @return void
     */
    function grep_help_text() {
        $full_text = file_get_contents($this->file_path);
        $lines = explode(PHP_EOL, $full_text);
        $start_idx = array_find_key($lines, function($v, $k) {
            return preg_match('/s*(\$help|\$usage|\$options\[\'help\'\]).*/', $v);
        });
        if ($start_idx !== null) {
            $lines = array_slice($lines, $start_idx + 0);
        }
        $end_idx = array_find_key($lines, function($v, $k) {
            return preg_match('/^\s*(EOT|EOL|EOF|");\s*/', $v);
        });
        if ($end_idx !== null) {
            $lines = array_slice($lines, 0, $end_idx);
        }
        $this->help_text = implode(PHP_EOL, $lines);
        $this->help_text = preg_replace(
            '/.*\$options.*/',
            '',
            $this->help_text,
        ) ?? '';
        $this->help_text = preg_replace(
            '/.*\/\/.*/',
            '',
            $this->help_text,
        ) ?? '';
        $this->help_text = preg_replace(
            '/\s*(\$help|\$usage|echo)\s*\=?\s*(<<<)?(EOT|EOL|EOF|echo)?"?\s*/',
            '',
            $this->help_text
        ) ?? '';
    }

    /**
     * Parses the extracted help text to build the script options and example text.
     *
     * @return void
     */
    function parse_help_text() {
        $lines = explode(PHP_EOL, $this->help_text);
        $option_lines = [];
        $example_lines = [];
        $stage = 0;
        foreach ($lines as $line) {
            if ($stage == 0) {
                if (strpos($line, 'Options:') !== false) {
                    $stage = 1;
                    continue;
                }
            } else if ($stage == 1) {
                if (trim($line) === '' && count($option_lines) > 0) {
                    $stage = 2;
                    continue;
                }
                $option_lines[] = $line;
            } else if ($stage == 2) {
                if (preg_match('/\"\;?/', $line) || $line == 'EOT;') {
                    break;
                }
                $example_lines[] = $line;
            }
        }
        $this->options = CLIOption::parse_lines($option_lines);
        $this->special_cases();
        $this->example_text = implode(PHP_EOL, $example_lines);
    }

    /**
     * Scans the admin/cli folder for cli scripts and parses them for information.
     * @return CLIScript[]
     */
    public static function gen_scripts(): array {
        $results = [];
        foreach (self::list_filenames() as $script) {
            $new_script = new CLIScript($script);
            $results[$new_script->file_name] = $new_script;
        }
        return $results;
    }

    /**
     * Returns a sorted list of .php filenames under admin/cli.
     * @return string[]
     */
    public static function list_filenames(): array {
        global $CFG;
        $entries = scandir($CFG->dirroot . '/admin/cli');
        if ($entries === false) {
            return [];
        }
        $files = array_filter($entries, function ($f) {
            return $f !== '.' && $f !== '..' && substr($f, -4) === '.php';
        });
        sort($files);
        return array_values($files);
    }

    /**
     * Converts a file name path to a normal 'purge_caches' style name.
     * @return string
     */
    public static function normalize_name(string $file_name): string {
        if (substr($file_name, -4) === '.php') {
            $file_name = substr($file_name, 0, -4);
        }
        return ltrim(strtolower(preg_replace('/([A-Z])/', '_$1', $file_name)), '_');
    }

    /**
     * Returns all enabled cli script names
     * @return string[]
     */
    public static function get_enabled_names(): array {
        $value = get_config('local_lsucli', 'enabledscripts');
        if ($value === false || $value === null) {
            return array_map([self::class, 'normalize_name'], self::list_filenames());
        }
        if ($value === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
    }

    /**
     * Checks if a script is enabled by the admin allowlist
     * @return bool
     */
    public static function is_enabled(string $name): bool {
        return in_array($name, self::get_enabled_names(), true);
    }

    /**
     * Get all enabled scripts as CLIScript objects
     * @return CLIScript[]
     */
    public static function gen_enabled_scripts(): array {
        $enabled = array_flip(self::get_enabled_names());
        return array_intersect_key(self::gen_scripts(), $enabled);
    }

    /**
     * @return CLIOption[]
     */
    public function get_options(): array {
        return $this->options;
    }

    /**
     * An array of corrected values for poor documentation in the script files.
     * Structure is [file_name][longname][property] = <NEW_VALUE>
     * @var array<string, array<string, array<string, mixed>>>
     */
    const SPECIAL_CASE_PROPS = [
        'adhoc_task' => [
            'classname' => [
                'type' => OptionType::STRING,
            ],
            'id' => [
                'type' => OptionType::NUMBER,
            ],
        ],
        'build_theme_css' => [
            'themes' => [
                'type' => OptionType::STRING,
            ],

            // Help text lies, the script accepts `--direction=ltr|rtl`.
            'direction' => [
                'type' => OptionType::STRING,
            ],
        ],
        'checks' => [
            'filter' => [
                'type' => OptionType::STRING,
            ],
            'type' => [
                'type' => OptionType::STRING,
            ],
        ],
        'delete_course' => [
            'courseid' => [
                'type' => OptionType::STRING,
                'required' => true,
            ],
            'non-interactive' => [
                'default_enabled' => true,
            ],
        ],
        'fix_course_sequence' => [
            'courses' => [
                'type' => OptionType::STRING,
                'required' => true,
            ],
        ],
        'generate_key' => [
            'method' => [
                'type' => OptionType::STRING,
            ],
        ],
        'maintenance' => [

            // `MINUTES` isn't in the parser's NUMBER_TEXTS whitelist, so it
            // falls through to STRING; the script casts to int either way.
            'enablelater' => [
                'type' => OptionType::NUMBER,
            ],
        ],
        'purge_caches' => [
            'courses' => [
                'type' => OptionType::STRING,
            ],
        ],
        'upgrade' => [
            'maintenance' => [
                'type' => OptionType::BOOL,
            ],
            'non-interactive' => [
                'default_enabled' => true,
            ],
        ],
    ];

    /**
     * Applies special case corrections to poorly documented script options.
     *
     * @return void
     */
    private function special_cases() {
        $params = self::SPECIAL_CASE_PROPS[$this->file_name] ?? [];
        if (empty($params)) {
            return;
        }
        foreach ($this->options as $option) {
            if (!key_exists($option->longname, $params)) {
                continue;
            }
            foreach ($params[$option->longname] as $key => $value) {
                $option->{$key} = $value;
            }
        }
    }
}
