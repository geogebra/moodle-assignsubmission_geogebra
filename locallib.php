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
 * This file contains the definition for the library class for GeoGebra submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package        assignsubmission_geogebra
 * @author         Christoph Stadlbauer <christoph.stadlbauer@geogebra.org>
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later,
 * license of GeoGebra: http://creativecommons.org/licenses/by-nc-nd/3.0/
 * For commercial use please see: http://www.geogebra.org/license
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for GeoGebra submission plugin extending submission plugin base class
 *
 * @author         Christoph Stadlbauer <christoph.stadlbauer@geogebra.org>
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later,
 * license of GeoGebra: http://creativecommons.org/licenses/by-nc-nd/3.0/
 * For commercial use please see: http://www.geogebra.org/license
 */
class assign_submission_geogebra extends assign_submission_plugin {

    public $deployscript = '<script type="text/javascript" src="https://www.geogebratube.org/scripts/deployggb.js"></script>';

    public $ggbscript = '<script type="text/javascript" src="submission/geogebra/ggba.js"></script>';

    /**
     * Get the name of the GeoGebra text submission plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('geogebra', 'assignsubmission_geogebra');
    }

    /**
     * Get geogebra submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_geogebra_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_geogebra', array('submission' => $submissionid));
    }

    /**
     * Adding the applet and hidden fields for parameters (inc. ggbbase64), views and codebase to the Moodleform
     *
     * @param mixed           $submissionorgrade submission|grade - the submission data
     * @param MoodleQuickForm $mform             - This is the form
     * @param stdClass        $data              - This is the form data that can be modified for example by a filemanager element
     * @param int             $userid            - This is the userid for the current submission.
     *                                           This is passed separately as there may not yet be a submission or grade.
     * @return boolean - true since we added something to the form
     */
    public function get_form_elements_for_user($submissionorgrade, MoodleQuickForm $mform, stdClass $data, $userid) {
        $submissionid = $submissionorgrade ? $submissionorgrade->id : 0;
        $template = $this->get_config('ggbtemplate');

        if ($submissionorgrade) {
            $geogebrasubmission = $this->get_geogebra_submission($submissionid);
            if ($geogebrasubmission) {
                $applet = $this->get_applet($geogebrasubmission);
            }
        } else {
            if ($template == "userdefined") {
                $url = $this->get_config('ggbturl');
                $tmp = explode('/', $url);
                if (!empty($tmp)) {
                    $materialid = array_pop($tmp);
                    if (strpos($materialid, 'm') === 0) {
                        $materialid = substr($materialid, 1);
                    }
                } else {
                    if (strpos($url, 'm') === 0) {
                        $materialid = substr($url, 1);
                    } else {
                        $materialid = $url;
                    }
                }
                $parameters = json_encode(array(
                        "material_id" => $materialid
                ));
            } else {
                $parameters = json_encode(array(
                        "perspective"     => $template,
                        "showMenuBar"     => false,
                        "showResetIcon"   => false,
                        "showToolBar"     => true,
                        "showToolBarHelp" => true,
                        "useBrowserForJS" => true
                ));
            }
            $applet = $this->get_applet(null, $parameters);
        }

        $mform->addElement('hidden', 'ggbparameters');
        $mform->setType('ggbparameters', PARAM_RAW);
        $mform->addElement('hidden', 'ggbviews');
        $mform->setType('ggbviews', PARAM_RAW);
        $mform->addElement('hidden', 'ggbcodebaseversion');
        $mform->setType('ggbcodebaseversion', PARAM_RAW);

        $mform->addElement('html', $this->deployscript);
        if (isset($materialid)) {
            $mform->addElement('html', '<div class="fitem"><div id="applet_container1" class="felement"></div></div>');
        } else {
            $mform->addElement('html',
                    '<div class="fitem">
                        <div id="applet_container1" class="felement" style="display: block; height: 600px;"></div>
                        </div>');
        }

        $mform->addElement('html', $applet);
        $mform->addElement('html', $this->ggbscript);

        return true;
    }

    /**
     * This function adds the elements to the settings page of an assignment
     * i.e. dropdown and filepicker for the template to use for the student
     *
     * @param MoodleQuickForm $mform The form to add the elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $template = $this->get_config('ggbtemplate');
        $url = $this->get_config('ggbturl');

        $ggbtemplates = array(
                '1'           => get_string('algebragraphics', 'assignsubmission_geogebra'),
                '2'           => get_string('basicgeo', 'assignsubmission_geogebra'),
                '3'           => get_string('geometry', 'assignsubmission_geogebra'),
                '4'           => get_string('spreadsheetgraphics', 'assignsubmission_geogebra'),
                '5'           => get_string('casgraphics', 'assignsubmission_geogebra'),
                '6'           => get_string('graphics3d', 'assignsubmission_geogebra'),
                'userdefined' => get_string('userdefined', 'assignsubmission_geogebra')
        );

        // Partly copied from qtype ggb
        $ggbturlinput = array();
        $clientid = uniqid();
        $fp = $this->initggtfilepicker($clientid, 'ggbturl');

        $ggbturlinput[] =& $mform->createElement('select', 'ggbtemplate', get_string('ggbtemplates',
                'assignsubmission_geogebra'), $ggbtemplates);
        $mform->setDefault('ggbtemplate', $template);
        $mform->disabledIf('ggbtemplate', 'assignsubmission_geogebra_enabled', 'notchecked');
        $ggbturlinput[] =& $mform->createElement('html', $fp);
        $ggbturlinput[] =& $mform->createElement('button', 'filepicker-button-' . $clientid, get_string('choosealink',
                'repository'));
        $mform->disabledIf('filepicker-button-' . $clientid, 'ggbtemplate', 'neq', 'userdefined');
        $ggbturlinput[] =& $mform->createElement('text', 'ggbturl', '', array('size' => '20', 'value' => $url));
        $mform->disabledIf('ggbturl', 'ggbtemplate', 'neq', 'userdefined');
        $mform->setType('ggbturl', PARAM_RAW_TRIMMED);
        $mform->addGroup($ggbturlinput, 'ggbturlinput', get_string('ggbturl', 'assignsubmission_geogebra'), array(' '), false);
        $mform->disabledIf('ggbturlinput', 'assignsubmission_geogebra_enabled', 'notchecked');
        $mform->addHelpButton('ggbturlinput', 'ggbturl', 'assignsubmission_geogebra');
    }

    /**
     * We have to save the template id and if user defined is chosen also the url to the GeoGebratube Worksheet.
     *
     * @see \assign_plugin::save_settings
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(stdClass $formdata) {
        $this->set_config('ggbtemplate', $formdata->ggbtemplate);
        if ($formdata->ggbtemplate == 'userdefined') {
            if (!isset($formdata->ggbturl)) {
                parent::set_error("No url chosen!");
                return false;
            }
            $this->set_config('ggbturl', $formdata->ggbturl);
        }
        return true;
    }

    /**
     * Save ggbparameters, views and codebase to DB
     *  (most of this is copied from onlinetext)
     *
     * @param stdClass $submissionorgrade - the submission data,
     * @param stdClass $data              - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save(stdClass $submissionorgrade, stdClass $data) {
        global $USER, $DB;

        $geogebrasubmission = $this->get_geogebra_submission($submissionorgrade->id);

        $params = array(
                'context' => context_module::instance($this->assignment->get_course_module()->id),
                'courseid' => $this->assignment->get_course()->id,
                'objectid' => $submissionorgrade->id,
                'other' => array(
                        'pathnamehashes' => array(),
                        'content' => ''
                )
        );
        if (!empty($submissionorgrade->userid) && ($submissionorgrade->userid != $USER->id)) {
            $params['relateduserid'] = $submissionorgrade->userid;
        }
        $event = \assignsubmission_geogebra\event\assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submissionorgrade->userid) && !empty($submissionorgrade->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submissionorgrade->groupid), '*', MUST_EXIST);
            $groupid = $submissionorgrade->groupid;
        } else {
            $params['relateduserid'] = $submissionorgrade->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
                'submissionid' => $submissionorgrade->id,
                'submissionattempt' => $submissionorgrade->attemptnumber,
                'submissionstatus' => $submissionorgrade->status,
                'groupid' => $groupid,
                'groupname' => $groupname
        );

        if ($geogebrasubmission) {
            $geogebrasubmission->ggbparameters = $data->ggbparameters;
            $geogebrasubmission->ggbviews = $data->ggbviews;
            $geogebrasubmission->ggbcodebaseversion = $data->ggbcodebaseversion;

            $params['objectid'] = $geogebrasubmission->id;
            $updatestatus = $DB->update_record('assignsubmission_geogebra', $geogebrasubmission);
            $event = \assignsubmission_geogebra\event\submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $updatestatus;
        } else {
            $geogebrasubmission = new stdClass();
            $geogebrasubmission->ggbparameters = $data->ggbparameters;
            $geogebrasubmission->ggbviews = $data->ggbviews;
            $geogebrasubmission->ggbcodebaseversion = $data->ggbcodebaseversion;

            $geogebrasubmission->submission = $submissionorgrade->id;
            $geogebrasubmission->assignment = $this->assignment->get_instance()->id;
            $geogebrasubmission->id = $DB->insert_record('assignsubmission_geogebra', $geogebrasubmission);
            $params['objectid'] = $geogebrasubmission->id;
            $event = \assignsubmission_geogebra\event\submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $geogebrasubmission->id > 0;
        }
    }

    /**
     * Is there a GeoGebra submission?
     *
     * @param stdClass $submissionorgrade assign_submission or assign_grade
     * @return bool if ggbparameters do not exist
     */
    public function is_empty(stdClass $submissionorgrade) {
        $geogebrasubmission = $this->get_geogebra_submission($submissionorgrade->id);

        return empty($geogebrasubmission->ggbparameters);
    }

    /**
     * Produce a list of files each containing the state of the applet the student submitted.
     *
     * @param stdClass $submissionorgrade assign_submission, the submission data
     * @param stdClass $user              The user record for the current submission. Not used here!
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submissionorgrade, stdClass $user) {
        $files = array();
        $geogebrasubmission = $this->get_geogebra_submission($submissionorgrade->id);

        if ($geogebrasubmission) {
            $applet = $this->get_applet($geogebrasubmission);
            $head = '<head><meta charset="UTF-8">' .
                    '<title>' . $user->firstname . ' ' . $user->lastname . ' - ' . $this->assignment->get_instance()->name .
                    '</title>' . $this->deployscript . $applet . '</head>';
            $submissioncontent = '<!DOCTYPE html><html>' . $head . '<body><div id="applet_container1"></div></body></html>';
            $filename = get_string('geogebrafilename', 'assignsubmission_geogebra');
            $files[$filename] = array($submissioncontent);
        }

        return $files;
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     *
     * @param stdClass $submissionorgrade assign_submission, the submission data,
     * @return string - return a string representation of the submission in full
     */
    public function view(stdClass $submissionorgrade) {
        $result = '';
        $geogebrasubmission = $this->get_geogebra_submission($submissionorgrade->id);
        if ($geogebrasubmission) {
            $result .= html_writer::tag('script', '', array(
                    'type' => 'text/javascript',
                    'src'  => 'https://www.geogebratube.org/scripts/deployggb.js'));
            $result .= html_writer::div('', '', array('id' => 'applet_container1'));
            // We must not load the applet before it is visible, it would show nothing then.
            $applet = $this->get_applet($geogebrasubmission, '', '', '', true);
            $result .= $applet;
        }

        return $result;
    }

    /**
     * We only want to show the view link, because the applet would consume to much space in the table.
     *
     * @param stdClass $submissionorgrade assign_submission, the submission data
     * @param bool     $showviewlink      Modified to return whether or not to show a link to the full submission/feedback
     * @return string - return a string representation of the submission in full -> empty in this case
     */
    public function view_summary(stdClass $submissionorgrade, & $showviewlink) {
        // Always show the view link.
        // FEATURE: We could show a lightbox onmousover or a preview image.
        $showviewlink = true;
        return '';
    }

    /**
     * Filepicker init and HTML, limits the accepted types to external files and type .html
     * Code reused from qtype_geogebra
     *
     * @param string $clientid    The unique ID for this filepicker
     * @param string $elementname elementname of the target
     * @return string
     */
    public function initggtfilepicker($clientid, $elementname) {
        global $PAGE, $OUTPUT, $CFG;

        $args = new stdClass();
        // GGBT Repository gives back mimetype html.
        $args->accepted_types = '.html';
        $args->return_types = FILE_EXTERNAL;
        $args->context = $PAGE->context;
        $args->client_id = $clientid;
        $args->elementname = $elementname;
        $args->env = 'ggbt';
        // Is $args->type = 'geogebratube'; not working?
        $fp = new file_picker($args);
        $options = $fp->options;

        // Print out file picker.
        $str = $OUTPUT->render($fp);

        // Depends on qtype_geogebra. We probably could factor out code to lib, but that would require another plugin.
        $module = array('name'     => 'form_ggbt',
                        'fullpath' => new moodle_url($CFG->wwwroot . '/question/type/geogebra/ggbt.js'),
                        'requires' => array('core_filepicker'));
        $PAGE->requires->js_init_call('M.form_ggbt.init', array($options), true, $module);

        return $str;
    }

    /**
     * @param        $geogebrasubmission
     * @param string $ggbparameters json encoded parameters for the applet.
     * @param string $ggbcodebaseversion
     * @param string $ggbviews
     * @param bool   $toggle
     * @return string
     */
    private function get_applet($geogebrasubmission, $ggbparameters = '', $ggbcodebaseversion = '', $ggbviews = '',
            $toggle = false) {
        $lang = current_language();
        if ($geogebrasubmission !== null) {
            $ggbparameters = $geogebrasubmission->ggbparameters;
            $ggbcodebaseversion = $geogebrasubmission->ggbcodebaseversion;
            $ggbviews = $geogebrasubmission->ggbviews;
        }
        $applet = '<script type="text/javascript">';
        if ($ggbparameters !== '') {
            $applet .= 'var parameters=' . $ggbparameters . ';';
        }
        $applet .= 'parameters.language = "' . $lang . '";';
        $applet .= 'var applet1 = new GGBApplet(';
        $applet .= ($ggbcodebaseversion !== '') ? '"' . $ggbcodebaseversion . '",' : '';
        $applet .= ($ggbparameters !== '') ? 'parameters,' : '';
        $applet .= ($ggbviews !== '') ? $ggbviews . ',' : '';
        $applet .= 'true);';
        $applet .= $this->get_applet_injectstring($toggle);
        $applet .= '</script>';

        return $applet;
    }

    /**
     * @param bool $toggle
     * @return string
     */
    private function get_applet_injectstring($toggle = false) {
        $injectstring = '';
        if ($toggle) {
            $injectstring .= <<<EOD
        ggbloaded = false;
        ggbdisplaytoggle = Y.one('#applet_container1').ancestor().siblings().pop().get('children').shift();
        if (ggbdisplaytoggle.hasAttribute('src')) {
            ggbdisplaytoggle.on('click', function () {
                if (!ggbloaded) {
                    applet1.inject("applet_container1", "preferHTML5");
                }
                ggbloaded = true;
            });
        } else {
EOD;
        }
        $injectstring .= <<<EOD
        window.onload = function () {
            applet1.inject("applet_container1", "preferHTML5");
            ggbloaded = true;
        }
EOD;
        if ($toggle) {
            $injectstring .= '}';
        }

        return $injectstring;
    }
}
