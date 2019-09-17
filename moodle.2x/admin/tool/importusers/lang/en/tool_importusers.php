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
 * Strings for component 'tool_importusers', language 'en', branch 'MOODLE_30_STABLE'
 *
 * @package    tool
 * @subpackage importusers
 * @copyright  2019 Gordon Bateson {@link http://quizport.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// essential strings
$string['pluginname'] = 'Import users';

// more strings
$string['actionaddandupdate'] = 'Add new users and update existing users';
$string['actionaddnewonly'] = 'Add new users (and skip existing users)';
$string['actionupdateexisting'] = 'Update existing users (and skip new users)';
$string['changepassword_help'] = 'Do you wish to force imported users to change their password?';
$string['changepassword'] = 'Force password change';
$string['coursemissing'] = 'Course not found';
$string['datafile_help'] = 'Add the Excel or OpenOffice file that contains data about students you wish to import.';
$string['datafile'] = 'Data file';
$string['defaultdescription'] = 'Hi';
$string['defaultvalues'] = 'Default values';
$string['emptydatafile'] = 'The data file was empty, missing or unreadable. Please go back and try again.';
$string['fixusernames_help'] = 'Do you wish to standard the usernames to use only lowercase letters, numerals, "-", "." and "_"?';
$string['fixusernames'] = 'Standardize usernames';
$string['formatfile_help'] = 'Add the XML file that specifies the format of the contents in the data file.';
$string['formatfile'] = 'Format file';
$string['import'] = 'Import formatted data';
$string['pageheader'] = 'Import users: {$a}';
$string['passwordaction_help'] = 'Select the action to be taken for each row of user data in the passworded file.';
$string['passwordaction'] = 'Password action';
$string['passwordcreatenew'] = 'Create password, if needed';
$string['passwordfilefield'] = 'Use passport field in import file';
$string['passwordformfield'] = 'Use specified value for all users';
$string['passwordmissingfromfile'] = 'Password missing from file';
$string['passwordmissingfromform'] = 'Password missing from form';
$string['preview'] = 'Preview raw data';
$string['previewrows_help'] = 'Choose the number of rows from the import file that you wish to preview.';
$string['previewrows'] = 'Preview rows';
$string['review'] = 'Review formatted data';
$string['row'] = 'Row';
$string['selectusers_help'] = 'Do you wish to select users for bulk user actions?';
$string['selectusers'] = 'Select for bulk user actions';
$string['sendpassword_help'] = 'Do you wish to send details of the password to each imported user?';
$string['sendpassword'] = 'Send password via email';
$string['sheetrowcount'] = 'File "{$a->filename}" has {$a->sheetcount} sheets and contains {$a->rowcount} rows of data';
$string['upload'] = 'Upload files';
$string['uploadaction_help'] = 'Select the action to be taken for each row of user data in the uploaded file.';
$string['uploadaction'] = 'Upload action';
$string['useradded'] = 'User added';
$string['useraddedtogroup'] = 'User belongs of group';
$string['userenrolled'] = 'User enrolled in course';
$string['usermissing'] = 'User not found';
$string['usernotadded'] = 'User could not be added';
$string['userskipped'] = 'User skipped';
$string['userupdated'] = 'User updated';
$string['worksheettitle'] = 'Worksheet {$a->sheetnum}/{$a->sheetmax}: {$a->title}';
$string['yesnewusers'] = 'Yes - new users only';

