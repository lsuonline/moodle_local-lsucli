<?php
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
    public string $file_path;
    public string $file_name;
    public string $help_text;
    /** @var CLIOption[] $options */
    public array $options;
    public string $example_text;

    public function __construct($file_name) {
        global $CFG, $DB;
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
     * Scans the script file for its information.
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
        // exec("php $this->file_path -- -h", $output);
        // $this->help_text = implode(PHP_EOL, $output);
        // $this->help_text = preg_replace('/^Warning\:.*$', '', $this->help_text) ?? '';
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
            ]
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
        'purge_caches' => [
            'courses' => [
                'type' => OptionType::STRING,
            ],
        ],
    ];
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