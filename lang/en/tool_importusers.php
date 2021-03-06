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
$string['addedenrolmethod'] = 'Manual enrol method added to course';
$string['addedgroup'] = 'Group added to course';
$string['addedstudentrole'] = 'Student role added to site';
$string['addedusertogroup'] = 'User added to group';
$string['addedusertosite'] = 'User added to site';
$string['assignedstudentrole'] = 'Student role assigned';
$string['changepassword_help'] = 'Do you wish to force imported users to change their password?';
$string['changepassword'] = 'Force password change';
$string['coursemissing'] = 'Course not found';
$string['datafile_help'] = 'Add the Excel or OpenOffice file that contains data about students you wish to import.';
$string['datafile'] = 'Data file';
$string['defaultdescription'] = 'Hi';
$string['defaultvalues'] = 'Default values';
$string['emptydatafile'] = 'The data file was empty, missing or unreadable.';
$string['emptyxmlfile'] = 'The XML file was empty of missing.';
$string['erroraddingenrolmethod'] = 'Oops - could not add manual enrol method.';
$string['erroraddinggroup'] = 'Oops - group could not be added.';
$string['erroraddingstudentrole'] = 'Oops - Student role could not be created.';
$string['erroraddinguser'] = 'Oops - user could not be added to site.';
$string['erroraddingusertogroup'] = 'Oops - user could not be added to group.';
$string['errorassigningstudentrole'] = 'Oops - student role could not be assigned.';
$string['errorenrollinguser'] = 'Oops - user could not be enrolled in course.';
$string['fixusernames_help'] = 'Do you wish to standard the usernames to use only lowercase letters, numerals, "-", "." and "_"?';
$string['fixusernames'] = 'Standardize usernames';
$string['formatfile_help'] = 'Add the XML file that specifies the format of the contents in the data file.';
$string['formatfile'] = 'Format file';
$string['import'] = 'Import formatted data';
$string['invalidxmlfile'] = 'The content of the XML file is invalid.';
$string['missingcourse'] = 'Course not found';
$string['missingcoursecontext'] = 'Course context not found';
$string['missingusername'] = 'Username not found: "{$a->username}" on sheet "{$a->sheet}" (row {$a->row})';
$string['nousername'] = 'Username could not be determined on sheet "{$a->sheet}" (row {$a->row})';
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
$string['resourcetype_help'] = 'If selected, a new Page or Book resource to show the login details of the imported users will be created in the destination course.';
$string['resourcetype'] = 'Login resource type';
$string['review'] = 'Review formatted data';
$string['row'] = 'Row';
$string['selectusers_help'] = 'Do you wish to select users for bulk user actions?';
$string['selectusers'] = 'Select for bulk user actions';
$string['sendpassword_help'] = 'Do you wish to send details of the password to each imported user?';
$string['sendpassword'] = 'Send password via email';
$string['sheet'] = 'Sheet';
$string['sheetrowcount'] = 'File "{$a->filename}" has {$a->sheetcount} sheets and contains {$a->rowcount} rows of data';
$string['tryagain'] = 'Please go back and try again.';
$string['upload'] = 'Upload files';
$string['uniqueemail_help'] = 'If you select "Yes" here, and a user with that email address already exists, then the username of the pre-existing user will be changed to the username given in the import file.';
$string['uniqueemail'] = 'Force unique email';
$string['uploadaction_help'] = 'Select the action to be taken for each row of user data in the uploaded file.';
$string['uploadaction'] = 'Upload action';
$string['useralreadyenrolled'] = 'User already enrolled in course';
$string['useralreadyingroup'] = 'User already in group';
$string['userenrolled'] = 'User enrolled in course';
$string['userlogindetails'] = 'User login details';
$string['userlogindetailsgroup'] = 'User login details: {$a}';
$string['userskipped'] = 'User skipped';
$string['userupdated'] = 'User updated';
$string['worksheettitle'] = 'Worksheet {$a->sheetnum}/{$a->sheetmax}: {$a->title}';
$string['xmltagmissing'] = 'A required XML tag, "{$a}", is empty or missing.';
$string['yesnewusers'] = 'Yes - new users only';