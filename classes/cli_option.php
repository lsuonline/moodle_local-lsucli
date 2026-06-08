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
 * @copyright  2026 onwards Steven Mattsen
 * @copyright  2026 onwards Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lsucli;

defined('MOODLE_INTERNAL') || die();

enum OptionType: int {
    case FLAG = 1;
    case STRING = 2;
    case NUMBER = 3;
    case BOOL = 4;
}

class CLIOption {
    public ?string $shortname;
    public string $longname;
    public ?string $description;
    public OptionType $type;
    public bool $required = false;
    public bool $default_enabled = false;

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
