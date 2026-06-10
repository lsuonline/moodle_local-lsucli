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
 * CLI option class for local_lsucli.
 *
 * @package    local_lsucli
 * @copyright  2026 onwards Louisiana State University
 * @copyright  2026 onwards Steve Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lsucli;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the type of an option.
 */
enum OptionType: int {
    case FLAG = 1;
    case STRING = 2;
    case NUMBER = 3;
    case BOOL = 4;
}

/**
 * Represents a command-line option for a CLI script.
 */
class CLIOption {

    /** @var string|null Short name of the option */
    public ?string $shortname;

    /** @var string Long name of the option */
    public string $longname;

    /** @var string|null Description of the option */
    public ?string $description;

    /** @var OptionType Type of the option */
    public OptionType $type;

    /** @var bool Whether the option is required */
    public bool $required = false;

    /** @var bool Whether the option is enabled by default */
    public bool $default_enabled = false;

    /**
     * Constructs a new CLIOption instance.
     *
     * @param string|null $shortname Short name of the option.
     * @param string $longname Long name of the option.
     * @param string|null $description Description of the option.
     * @param OptionType $type Type of the option.
     */
    public function __construct($shortname, $longname, $description, OptionType $type) {
        $this->shortname = $shortname;
        $this->longname = $longname;
        $this->description = $description;
        $this->type = $type;
    }

    /**
     * Parses the option lines of a help text.
     * Creates a CLIOption for each line.
     * @param   string[]    $lines  An array of strings
     * @return CLIOption[]
     */
    static function parse_lines(array $lines) {
        $options_array = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] !== '-') {
                continue;
            }

            $words = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            
            $shortname = null;
            $longname = null;
            $description = '';
            $type = OptionType::FLAG;

            $word = array_shift($words);
            if (preg_match('/^-\S,?$/', $word)) {
                $shortname = $word[1];
                $word = array_shift($words);
            }
            if (substr($word, 0, 2) !== '--') {

                // Invalid option format.
                continue;
            }
            $longtext = substr($word, 2);
            if (strpos($longtext, '=') !== false) {
                $split = explode('=', $longtext, 2);
                $longname = $split[0];
                $typehint = $split[1];
                $number_texts = ['N', 'INTEGER', 'INT', 'NUMBER', 'FLOAT', 'DOUBLE'];
                if ( in_array(strtoupper($typehint), $number_texts) ) {
                    $type = OptionType::NUMBER;
                } else {
                    $type = OptionType::STRING;
                }
            } else {
                $longname = $longtext;
            }
            if ($longname === 'help') {

                // Skip help option.
                continue;
            }

            $description = implode(' ', $words);
            $description = html_entity_decode($description);
            $description = preg_replace('/\\\(.)/', '$1', $description);

            // Just in case someone does something stupid.
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $longname)) { continue; }

            $options_array[] = new CLIOption(
                $shortname,
                $longname,
                $description,
                $type
            );
        }
        return $options_array;
    }
}
