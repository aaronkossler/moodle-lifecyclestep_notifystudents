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
 * Capability definitions for the notifystudents step subplugin
 * @package lifecyclestep_notifystudents
 * @copyright  2021 Aaron Koßler WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'lifecyclestep/notifystudents:choice' => array(
        'contextlevel' => CONTEXT_COURSE,
        'captype' => 'write',
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'tool/lifecycle:managecourses'
    ),

    'lifecyclestep/notifystudents:students' => array(
        'contextlevel' => CONTEXT_COURSE,
        'captype' => 'read',
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        )
    ),

);
