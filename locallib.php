<?php

/**
 * This file contains the definition for the library class for GeoGebra submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package        assignsubmission_geogebra
 * @author         Christoph Stadlbauer <christoph.stadlbauer@geogebra.org>
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.geogebra.org/license
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for GeoGebra submission plugin extending submission plugin base class
 *
 * @author         Christoph Stadlbauer <christoph.stadlbauer@geogebra.org>
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.geogebra.org/license
 */
class assign_submission_geogebra extends assign_submission_plugin {

    public $deployscript = '<script type="text/javascript" src="https://www.geogebra.org/scripts/deployggb.js"></script>';

    public $ggbscript = '<script type="text/javascript" src="submission/geogebra/ggba.js"></script>';

    /**
     * Get the name of the GeoGebra text submission plugin
     *
     * @return string
     */
    public function get_name() {
        return 'GeoGebra';
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
        $usefile = $this->get_config('usefile');
        if (!$usefile) {
            $template = $this->get_config('ggbtemplate');
        }
        if ($submissionorgrade) {
            $geogebrasubmission = $this->get_geogebra_submission($submissionid);
            if ($geogebrasubmission) {
                // Only load stored applet if ggbparameters and ggbviews not empty.
                if (empty($geogebrasubmission->ggbparameters) || empty($geogebrasubmission->ggbviews)) {
                    if ($usefile) {
                        $applet = $this->get_applet(null, $this->get_config('ggbparameters'),
                                $this->get_config('ggbcodebaseversion'), $this->get_config('ggbviews'));
                    } else {
                        $parameters = $this->get_ggb_params($template);
                        $applet = $this->get_applet(null, $parameters);
                    }
                } else {
                    $applet = $this->get_applet($geogebrasubmission);
                }
            } else {
                if ($usefile) {
                    $applet = $this->get_applet(null, $this->get_config('ggbparameters'),
                            $this->get_config('ggbcodebaseversion'), $this->get_config('ggbviews'));
                } else {
                    $parameters = $this->get_ggb_params($template);
                    $applet = $this->get_applet(null, $parameters);
                }
            }
        } else {
            if ($usefile) {
                $applet = $this->get_applet(null, $this->get_config('ggbparameters'),
                        $this->get_config('ggbcodebaseversion'), $this->get_config('ggbviews'));
            } else {
                $parameters = $this->get_ggb_params($template);
                $applet = $this->get_applet(null, $parameters);
            }
        }

        $mform->addElement('hidden', 'ggbparameters');
        $mform->setType('ggbparameters', PARAM_RAW);
        $mform->addElement('hidden', 'ggbviews');
        $mform->setType('ggbviews', PARAM_RAW);
        $mform->addElement('hidden', 'ggbcodebaseversion');
        $mform->setType('ggbcodebaseversion', PARAM_RAW);

        $mform->addElement('html', $this->deployscript);
        if ($usefile || $template == "userdefined") {
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

        $ggbtrepo = repository::get_type_by_typename('geogebratube');

        $template = $this->get_config('ggbtemplate');
        $url = $this->get_config('ggbturl');
        $usefile = $this->get_config('usefile');
        if ($usefile) {
            $ggbparameters = $this->get_config('ggbparameters');
            $ggbviews = $this->get_config('ggbviews');
            $ggbcodebaseversion = $this->get_config('ggbcodebaseversion');
        }
        $mform->addElement('hidden', 'ggbparameters');
        $mform->setType('ggbparameters', PARAM_RAW);

        $mform->addElement('hidden', 'ggbviews');
        $mform->setType('ggbviews', PARAM_RAW);

        $mform->addElement('hidden', 'ggbcodebaseversion');
        $mform->setType('ggbcodebaseversion', PARAM_RAW);

        $ggbtemplates = array(
                '1'           => get_string('algebra', 'assignsubmission_geogebra'),
                '2'           => get_string('geometry', 'assignsubmission_geogebra'),
                '3'           => get_string('spreadsheet', 'assignsubmission_geogebra'),
                '4'           => get_string('cas', 'assignsubmission_geogebra'),
                '5'           => get_string('perspective3d', 'assignsubmission_geogebra'),
                '6'           => get_string('probCalc', 'assignsubmission_geogebra'),
                'userdefined' => get_string('userdefined', 'assignsubmission_geogebra')
        );

        // Partly copied from qtype ggb.
        $ggbturlinput = array();

        $clientid = uniqid();
        $fp = $this->initggtfilepicker($clientid, 'ggbturl');

        $ggbturlinput[] =& $mform->createElement('select', 'ggbtemplate', get_string('ggbtemplates',
                'assignsubmission_geogebra'), $ggbtemplates);
        $mform->setDefault('ggbtemplate', $template);
        $mform->disabledIf('ggbtemplate', 'assignsubmission_geogebra_enabled', 'notchecked');
        if ($ggbtrepo) {
            $ggbturlinput[] =& $mform->createElement('html', $fp);
            $ggbturlinput[] =& $mform->createElement('button', 'filepicker-button-' . $clientid, get_string('choosealink',
                    'repository'));
            $mform->disabledIf('filepicker-button-' . $clientid, 'ggbtemplate', 'neq', 'userdefined');
        }
        $ggbturlinput[] =& $mform->createElement('text', 'ggbturl', '', array('size' => '20', 'value' => $url));
        $mform->disabledIf('ggbturl', 'ggbtemplate', 'neq', 'userdefined');
        $mform->setType('ggbturl', PARAM_RAW_TRIMMED);
        $mform->addGroup($ggbturlinput, 'ggbturlinput', get_string('ggbturl', 'assignsubmission_geogebra'), array(' '), false);
        $mform->disabledIf('ggbturlinput', 'assignsubmission_geogebra_enabled', 'notchecked');
        $mform->addHelpButton('ggbturlinput', 'ggbturl', 'assignsubmission_geogebra');
        $mform->addElement('checkbox', 'usefile', get_string('useafile', 'qtype_geogebra'), get_string('dragndrop', 'assignsubmission_geogebra'));
        $mform->addHelpButton('usefile', 'useafile', 'assignsubmission_geogebra');
        if ($usefile) {
            $mform->setDefault('usefile', true);
        }
        $mform->disabledIf('ggbtemplate', 'usefile', 'checked');
        $mform->disabledIf('ggbturl', 'usefile', 'checked');
        if ($ggbtrepo) {
            $mform->disabledIf('filepicker-button-' . $clientid, 'usefile', 'checked');
        }
        $mform->disabledIf('usefile', 'assignsubmission_geogebra_enabled', 'notchecked');

        $mform->addElement('html', $this->deployscript);

        $mform->addElement('html', '<div class="fitem"><div id="applet_container1" class="felement"></div></div>');

        if ($usefile && $ggbparameters != '') {
            $applet = $this->get_applet(null, $ggbparameters, $ggbcodebaseversion, $ggbviews);
            $mform->addElement('html', $applet);
            $mform->addElement('html', $this->ggbscript);
        }

        $this->add_applet_options($mform);
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
        if (isset($formdata->usefile) && $formdata->usefile) {
            if (empty($formdata->ggbparameters)
                    || empty($formdata->ggbviews)
                    || empty($formdata->ggbcodebaseversion)
            ) {
                parent::set_error(get_string('noappletloaded', 'qtype_geogebra'));
                return false;
            } else {
                $this->set_config('usefile', $formdata->usefile);
                $this->set_config('ggbparameters', $formdata->ggbparameters);
                $this->set_config('ggbviews', $formdata->ggbviews);
                $this->set_config('ggbcodebaseversion', $formdata->ggbcodebaseversion);
            }
        } else {
            $this->set_config('ggbtemplate', $formdata->ggbtemplate);
            $this->set_config('usefile', false);
            $this->set_config('ggbparameters', "");
            $this->set_config('ggbviews', "");
            $this->set_config('ggbcodebaseversion', "");
            if ($formdata->ggbtemplate == 'userdefined') {
                if (!isset($formdata->ggbturl)) {
                    parent::set_error("No url chosen!");
                    return false;
                }
                $this->set_config('ggbturl', $formdata->ggbturl);
            }
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
                'context'  => context_module::instance($this->assignment->get_course_module()->id),
                'courseid' => $this->assignment->get_course()->id,
                'objectid' => $submissionorgrade->id,
                'other'    => array(
                        'pathnamehashes' => array(),
                        'content'        => ''
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
                'submissionid'      => $submissionorgrade->id,
                'submissionattempt' => $submissionorgrade->attemptnumber,
                'submissionstatus'  => $submissionorgrade->status,
                'groupid'           => $groupid,
                'groupname'         => $groupname
        );

        // We must not save the assignment if anything goes wrong with the applet and above strings are empty.
        if ($geogebrasubmission) {
            if (empty($data->ggbparameters) || empty($data->ggbviews)) {
                $this->set_error(get_string('appletmissinginsubmission', 'assignsubmission_geogebra'));
                return false;
            }
        }

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
            $filename = 'geogebra.html';
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
                    'src'  => 'https://www.geogebra.org/scripts/deployggb.js'));
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
        return get_string('geogebra','assignsubmission_geogebra');
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
        $args->lang = current_language();
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

    private function add_applet_options($mform) {

        $applet_advanced_settings = get_string('applet_advanced_settings', 'qtype_geogebra');
        $enable_label_drags = get_string('enable_label_drags', 'qtype_geogebra');
        $enable_right_click = get_string('enable_right_click', 'qtype_geogebra');
        $enable_shift_drag_zoom = get_string('enable_shift_drag_zoom', 'qtype_geogebra');
        $show_algebra_input = get_string('show_algebra_input', 'qtype_geogebra');
        $show_menu_bar = get_string('show_menu_bar', 'qtype_geogebra');
        $show_reset_icon = get_string('show_reset_icon', 'qtype_geogebra');
        $show_tool_bar = get_string('show_tool_bar', 'qtype_geogebra');

        $options = <<<HTML
<div id='applet_options' class="fitem" >
    <div class="fitemtitle"><label for="applet_options">$applet_advanced_settings</label></div>
    <fieldset class="felement fgroup">
        <input type="checkbox" id="enableRightClick" name="enableRightClick" value="1">
        <label for="enableRightClick">$enable_right_click</label><br>
        <input type="checkbox" id="enableLabelDrags" name="enableLabelDrags" value="1">
        <label for="enableLabelDrags">$enable_label_drags</label><br>
        <input type="checkbox" id="showResetIcon" name="showResetIcon" value="1" checked="checked">
        <label for="showResetIcon">$show_reset_icon</label><br>
        <input type="checkbox" id="enableShiftDragZoom" name="enableShiftDragZoom" value="1" checked="checked">
        <label for="enableShiftDragZoom">$enable_shift_drag_zoom</label><br>
        <input type="checkbox" id="showMenuBar" name="showMenuBar" value="1">
        <label for="showMenuBar">$show_menu_bar</label><br>
        <input type="checkbox" id="showToolBar" name="showToolBar" value="1">
        <label for="showToolBar">$show_tool_bar</label><br>
        <input type="checkbox" id="showAlgebraInput" name="showAlgebraInput" value="1">
        <label for="showAlgebraInput">$show_algebra_input</label><br>
    </fieldset>
</div>
HTML;

        $mform->addElement('html', $options, 'advanced');
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
        $applet .= 'parameters.moodle = "viewOrEditSubmission";';
        $applet .= 'var applet1;';
        if ($toggle) {
            $applet .= <<<EOD
        ggbloaded = false;
        ggbdisplaytoggle = Y.one('#applet_container1').ancestor().siblings().pop().get('children').shift();
        if ((typeof ggbdisplaytoggle != 'undefined') && ggbdisplaytoggle.hasAttribute('src')) {
            ggbdisplaytoggle.on('click', function () {
EOD;
            $applet .= 'applet1 = new GGBApplet(';
            $applet .= ($ggbcodebaseversion !== '') ? '"' . $ggbcodebaseversion . '",' : '';
            $applet .= ($ggbparameters !== '') ? 'parameters,' : '';
            $applet .= ($ggbviews !== '') ? $ggbviews . ',' : '';
            $applet .= 'true);';
            $applet .= <<<EOD

                if (!ggbloaded) {
                    applet1.inject("applet_container1", "preferHTML5");
                }
                ggbloaded = true;
            });
        } else {
EOD;
        }
        $applet .= 'window.onload = function () {';
        $applet .= 'applet1 = new GGBApplet(';
        $applet .= ($ggbcodebaseversion !== '') ? '"' . $ggbcodebaseversion . '",' : '';
        $applet .= ($ggbparameters !== '') ? 'parameters,' : '';
        $applet .= ($ggbviews !== '') ? $ggbviews . ',' : '';
        $applet .= 'true);';
        $applet .= <<<EOD
            applet1.inject("applet_container1", "preferHTML5");
            ggbloaded = true;
        }
EOD;
        if ($toggle) {
            $applet .= '}';
        }
        $applet .= '</script>';

        return $applet;
    }

    /**
     * @param $template
     * @return array
     */
    private function get_ggb_params($template) {
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
        return $parameters;
    }
}
