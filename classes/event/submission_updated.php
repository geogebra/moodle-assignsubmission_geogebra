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
 * The assignsubmission_geogebra submission_updated event.
 *
 * @package        assignsubmission_geogebra
 * @author         Christoph Stadlbauer <christoph.stadlbauer@geogebra.org>
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later,
 * license of GeoGebra: http://creativecommons.org/licenses/by-nc-nd/3.0/
 * For commercial use please see: http://www.geogebra.org/license
 */

namespace assignsubmission_geogebra\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The assignsubmission_geogebra submission_updated event class.
 *
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later,
 * license of GeoGebra: http://creativecommons.org/licenses/by-nc-nd/3.0/
 * For commercial use please see: http://www.geogebra.org/license
 */
class submission_updated extends \mod_assign\event\submission_updated {

    /**
     * Init method.
     */
    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'assignsubmission_file';
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $descriptionstring = "The user with id '$this->userid' updated an online text submission with " .
            "'{$this->other['geogebrawordcount']}' words in the assignment with the course module id " .
            "'$this->contextinstanceid'";
        if (!empty($this->other['groupid'])) {
            $descriptionstring .= " for the group with id '{$this->other['groupid']}'.";
        } else {
            $descriptionstring .= ".";
        }

        return $descriptionstring;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
    }
}
