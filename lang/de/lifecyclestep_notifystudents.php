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
 * Lang strings for delete course step
 *
 * @package lifecyclestep_notifystudents
 * @copyright  2021 Aaron Koßler WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Studierende-benachrichtigen-Schritt';
$string['option'] = 'Option';
$string['optin'] = 'Opt-In';
$string['optout'] = 'Opt-Out';
$string['mail_title'] = 'Email Titel';
$string['mail_text'] = 'Email Text';
$string['mail_title_default'] = 'Kurse werden gelöscht';
$string['mail_text_default'] = '<p>' . 'Lieber Studierender,'
    . '<br><br>' . 'die folgenden Kurse werden bald gelöscht:'
    . '<br>' . '##courses-html##'
    . '<br>' . 'Bitte speichern Sie alle noetigen Materialien.'
    . '<br><br>' . 'Mit freundlichen Gruessen'
    . '<br>' . 'Dein Learnweb Team'
    . '</p>';
$emailplaceholders = '<p>' . 'Sie können die folgenden Platzhalter benutzen:'
    . '<br>' . 'Vorname des Empfängers: ##firstname##'
    . '<br>' . 'Nachname des Empfängers: ##lastname##'
    . '<br>' . 'Betroffene Kurse: ##courses-html##'
    . '</p>';
$string['mail_title_help'] = $emailplaceholders;
$string['mail_text_help'] = $emailplaceholders;