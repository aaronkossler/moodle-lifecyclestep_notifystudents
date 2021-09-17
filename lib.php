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
 * Step subplugin to notify students of a course that the course is being deleted.
 *
 * @package    lifecyclestep_notifystudents
 * @copyright  2021 Aaron Koßler WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lifecycle\step;

use context_course;
use core_user;
use tool_lifecycle\local\manager\settings_manager;
use tool_lifecycle\local\manager\step_manager;
use tool_lifecycle\local\response\step_response;
use tool_lifecycle\settings_type;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Step subplugin to notify students of a course that the course is being deleted.
 *
 * @package    lifecyclestep_notifystudents
 * @copyright  2021 Aaron Koßler WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifystudents extends libbase {

    /**
     * Processes the course and returns a response.
     * The response tells either
     *  - that the subplugin is finished processing.
     *  - that the subplugin is not yet finished processing.
     *  - that a rollback for this course is necessary.
     * @param int $processid of the respective process.
     * @param int $instanceid of the step instance.
     * @param mixed $course to be processed.
     * @return step_response
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function process_course($processid, $instanceid, $course) {
        global $DB;
        $context = context_course::instance($course->id);
        $userrecords = get_enrolled_users($context, '', 0, '*');
        foreach ($userrecords as $userrecord) {
            if (user_has_role_assignment($userrecord->id, 5)) {
                $record = new \stdClass();
                $record->touser = $userrecord->id;
                $record->courseid = $course->id;
                $record->instanceid = $instanceid;
                $DB->insert_record('lifecyclestep_notifystudents', $record);
            }
        }

        return step_response::proceed();
    }

    /**
     * Processes the course in status waiting and returns a response.
     * The response tells either
     *  - that the subplugin is finished processing.
     *  - that the subplugin is not yet finished processing.
     *  - that a rollback for this course is necessary.
     * @param int $processid of the respective process.
     * @param int $instanceid of the step instance.
     * @param mixed $course to be processed.
     * @return step_response
     */
    public function process_waiting_course($processid, $instanceid, $course) {
        return $this->process_course($processid, $instanceid, $course);
    }

    /**
     * Send emails to all students, but only one mail per students.
     */
    public function post_processing_bulk_operation() {
        global $DB, $PAGE;
        $stepinstances = step_manager::get_step_instances_by_subpluginname($this->get_subpluginname());
        foreach ($stepinstances as $step) {
            $settings = settings_manager::get_settings($step->id, settings_type::STEP);
            // Set system context, since format_text needs a context.
            $PAGE->set_context(\context_system::instance());
            // Format the raw string in the DB to FORMAT_HTML.
            $settings['contenthtml'] = format_text($settings['contenthtml'], FORMAT_HTML);

            $userstobeinformed = $DB->get_records('lifecyclestep_notifystudents',
                array('instanceid' => $step->id), '', 'distinct touser');
            foreach ($userstobeinformed as $userrecord) {
                $user = \core_user::get_user($userrecord->touser);
                $transaction = $DB->start_delegated_transaction();
                $mailentries = $DB->get_records('lifecyclestep_notifystudents',
                    array('instanceid' => $step->id,
                        'touser' => $user->id));

                $parsedsettings = $this->replace_placeholders($settings, $user, $step->id, $mailentries);

                $subject = $parsedsettings['subject'];
                $contenthtml = $parsedsettings['contenthtml'];
                email_to_user($user, \core_user::get_noreply_user(), $subject, html_to_text($contenthtml), $contenthtml);
                $DB->delete_records('lifecyclestep_notifystudents',
                    array('instanceid' => $step->id,
                        'touser' => $user->id));
                $transaction->allow_commit();
            }
        }

    }

    /**
     * Replaces certain placeholders within the mail template.
     * @param string[] $strings array of mail templates.
     * @param core_user $user User object.
     * @param int $stepid Id of the step instance.
     * @param array[] $mailentries Array consisting of course entries from the database.
     * @return string[] array of mail text.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function replace_placeholders($strings, $user, $stepid, $mailentries) {

        $patterns = array();
        $replacements = array();

        // Replaces firstname of the user.
        $patterns [] = '##firstname##';
        $replacements [] = $user->firstname;

        // Replaces lastname of the user.
        $patterns [] = '##lastname##';
        $replacements [] = $user->lastname;

        // Replace courses html.
        $patterns [] = '##courses-html##';
        $courses = $mailentries;
        $coursestabledata = array();
        foreach ($courses as $entry) {
            $coursestabledata[$entry->courseid] = $this->parse_course_row_data($entry->courseid);
        }
        $coursestable = new \html_table();
        $coursestable->data = $coursestabledata;
        $replacements [] = \html_writer::table($coursestable);

        return str_ireplace($patterns, $replacements, $strings);
    }

    /**
     * Parses a course for the non html format.
     * @param int $courseid id of the course
     * @return string
     * @throws \dml_exception
     */
    private function parse_course($courseid) {
        $course = get_course($courseid);
        $result = $course->fullname;
        return $result;
    }

    /**
     * Parses a course for the html format.
     * @param int $courseid id of the course
     * @return array column of a course
     * @throws \dml_exception
     */
    private function parse_course_row_data($courseid) {
        $course = get_course($courseid);
        return array($course->fullname);
    }

    /**
     * The return value should be equivalent with the name of the subplugin folder.
     * @return string technical name of the subplugin
     */
    public function get_subpluginname() {
        return 'notifystudents';
    }

    /**
     * Defines which settings each instance of the subplugin offers for the user to define.
     * @return instance_setting[] containing settings keys and PARAM_TYPES
     */
    public function instance_settings() {
        return array(
            new instance_setting('subject', PARAM_TEXT),
            new instance_setting('contenthtml', PARAM_RAW),
        );
    }

    /**
     * This method can be overriden, to add form elements to the form_step_instance.
     * It is called in definition().
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function extend_add_instance_form_definition($mform) {

        // Adding radio buttons for opt-in or opt-out.
        $elementname = 'opt';
        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', $elementname, '', get_string('optin'), 1, $attributes);
        $radioarray[] = $mform->createElement('radio', $elementname, '', get_string('optout'), 0, $attributes);
        $mform->addGroup($radioarray, 'option', '', array(' '), false);

        // Adding a subject field for the email.
        $elementname = 'subject';
        $mform->addElement('textarea', $elementname, get_string('subject', 'lifecyclestep_notifystudents'),
            array('style="resize:none" wrap="virtual" rows="1" cols="100"'));
        $mform->addHelpButton($elementname, 'subject', 'lifecyclestep_notifystudents');
        $mform->setType($elementname, PARAM_TEXT);
        $mform->setDefault($elementname, get_string('subject_default', 'lifecyclestep_notifystudents'));

        // Adding a content field for the email.
        $elementname = 'contenthtml';
        $mform->addElement('editor', $elementname, get_string('contenthtml', 'lifecyclestep_notifystudents'))
            ->setValue(array('text' => get_string('contenthtml_default', 'lifecyclestep_notifystudents')));
        $mform->addHelpButton($elementname, 'contenthtml', 'lifecyclestep_notifystudents');
        $mform->setType($elementname, PARAM_RAW);

    }
}