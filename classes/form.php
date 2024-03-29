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
 * admin/tool/importusers.php
 *
 * @package    tool
 * @subpackage importusers
 * @copyright  2019 Gordon Bateson {@link http://quizport.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * tool_importusers_form
 *
 * @package    tool
 * @subpackage importusers
 * @copyright  2019 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */
class tool_importusers_form extends moodleform {

    const ACTION_ADD_NEW_ONLY    = 1;
    const ACTION_ADD_AND_UPDATE  = 2;
    const ACTION_UPDATE_EXISTING = 3;

    const PASSWORD_CREATE_NEW = 1;
    const PASSWORD_FILE_FIELD = 2;
    const PASSWORD_FORM_FIELD = 3;

    const SELECT_NONE = 0;
    const SELECT_ALL  = 1;
    const SELECT_NEW  = 2;

    const ROW_TYPE_NONE = 0;
    const ROW_TYPE_META = 1;
    const ROW_TYPE_DATA = 2;

    const MODE_DRYRUN = 1;
    const MODE_IMPORT = 2;

    // cache the plugin name
    public $tool = 'tool_importusers';

    protected $numeric   = null;
    protected $lowercase = null;
    protected $uppercase = null;

    protected $formstate = '';
    protected $phpspreadsheet = null;
    protected $punctuation = null;

    /**
     * constructor
     */
    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true) {
        global $CFG;

        $this->numeric   = array_flip(str_split('23456789', 1));
        $this->lowercase = array_flip(str_split('abdeghjmnpqrstuvyz', 1));
        $this->uppercase = array_flip(str_split('ABDEGHJLMNPQRSTUVWXYZ', 1));
        $this->punctuation =array(
            /* 00D7 */ '×' => '*',

            // "General Punctuation"
            // https://0g0.org/category/2000-206F/1/
            /* 2014 */ '—' => '-', '‖' => '|', /* 2016 */
            /* 2018 */ '‘' => "'", '’' => "'", /* 2019 */
            /* 201C */ '“' => '"', '”' => '"', /* 201D */
            /* 2026 */ '…' => '-',

            // "CJK Symbols and Punctuation" 3000-303F
            // https://0g0.org/category/3000-303F/1/
            /* 3000 */ '　' => ' ', '、' => ',', /* 3001 */
            /* 3002 */ '。' => '.', '〃' => '"', /* 3003 */
            /* 3007 */ '〇' => 'O',
            /* 3008 */ '〈' => '<', '〉' => '>', /* 3009 */
            /* 300A */ '《' => '<', '》' => '>', /* 300B */
            /* 300C */ '「' => "'", '」' => "'", /* 300D */
            /* 300E */ '『' => '"', '』' => '"', /* 300F */
            /* 3010 */ '【' => '[', '】' => ']', /* 3011 */
            /* 3014 */ '〔' => '(', '〕' => ')', /* 3015 */
            /* 3016 */ '〖' => '[', '〗' => ']', /* 3017 */
            /* 3018 */ '〘' => '[', '〙' => ']', /* 3019 */
            /* 301A */ '〚' => '[', '〛' => ']', /* 301B */
            /* 301C */ '〜' => '~', '〝' => '"', /* 301D */
            /* 301E */ '〞' => '"', '〟' => '"', /* 301F */

            // "Halfwidth and Fullwidth Forms" FF00-FFEF
            // https://0g0.org/category/FF00-FFEF/1/
            /* FF01 */ '！' => '!', '＂' => '"', /* FF02 */
            /* FF03 */ '＃' => '#', '＄' => '$', /* FF04 */
            /* FF05 */ '％' => '%', '＆' => '&', /* FF06 */
            /* FF07 */ '＇' => "'", '（' => '(', /* FF08 */
            /* FF09 */ '）' => ')', '＊' => '*', /* FF0A */
            /* FF0B */ '＋' => '+', '，' => ',', /* FF0C */
            /* FF0D */ '－' => '-', '．' => '.', /* FF0E */
            /* FF0F */ '／' => '/', '：' => ':', /* FF1A */
            /* FF1B */ '；' => ';', '＜' => '<', /* FF1C */
            /* FF1D */ '＝' => '=', '＞' => '>', /* FF1E */
            /* FF1F */ '？' => '?', '＠' => '@', /* FF20 */
            /* FF3B */ '［' => '[', '＼' => '\\', /* FF3C */
            /* FF3D */ '］' => ']', '＾' => '^', /* FF3E */
            /* FF3F */ '＿' => '_', '｀' => "'", /* FF40 */
            /* FF5B */ '｛' => '{', '｜' => '|', /* FF5C */
            /* FF5D */ '｝' => '}', '～' => '~', /* FF5E */
            /* FF5F */ '｟' => '(', '｠' => ')', /* FF60 */
            /* FF61 */ '｡' => '. ', '｢' => '"',  /* FF62 */
            /* FF63 */ '｣' => '"',  '､' => ',',  /* FF64 */
            /* FF65 */ '･' => '/', '￣' => '~',  /* FFE3 */
            /* FFE1 */ '￡' => '£', '￥' => '¥'  /* FFE5 */
        );

        // Get a valid form state.
        $states = array('upload', 'preview', 'review', 'import');
        $this->formstate = optional_param('formstate', '', PARAM_ALPHA);
        if (in_array($this->formstate, $states)) {
            // form state is valid - do nothing
        } else {
            $this->formstate = reset($states);
        }

        // Detect "Cancel" or "Back" button.
        if (optional_param('cancel', 0, PARAM_RAW)) {
            $i = array_search($this->formstate, $states);
            if ($i >= 2) {
                $this->formstate = $states[$i - 2];
            } else {
                $this->formstate = 'cancelled';
            }
        }

        // check for new PhpExcel (Moodle >= 3.8)
        $this->phpspreadsheet = file_exists($CFG->dirroot.'/lib/phpspreadsheet');

        if (method_exists('moodleform', '__construct')) {
            parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
        } else {
            parent::moodleform($action, $customdata, $method, $target, $attributes, $editable);
        }
    }

    /**
     * definition
     */
    public function definition() {
        global $CFG, $USER;

        $mform = $this->_form;
        $this->set_form_id($mform, get_class($this));

        switch ($this->formstate) {
 
            case 'upload':

                $name = 'formstate';
                unset($_POST[$name]);
                $mform->addElement('hidden', $name, 'preview');
                $mform->setType($name, PARAM_ALPHA);

                $name = 'datafile';
                $label = get_string($name, $this->tool);
                $options = array('accepted_types' => array('.xlsx', '.xls', '.ods'));
                $mform->addElement('filepicker', $name, $label, 'size="10"', $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->addRule($name, null, 'required');

                $name = 'formatfile';
                $label = get_string($name, $this->tool);
                $options = array('accepted_types' => array('.xml'));
                $mform->addElement('filepicker', $name, $label, 'size="10"', $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->addRule($name, null, 'required');

                $name = 'previewrows';
                $label = get_string($name, $this->tool);
                $options = array(10, 20, 100, 1000, 100000);
                $options = array_combine($options, $options);
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name, 10);

                $submit = 'preview';
                $cancel = 'cancel';
                break;

            case 'preview':

                $name = 'formstate';
                unset($_POST[$name]);
                $mform->addElement('hidden', $name, 'review');
                $mform->setType($name, PARAM_ALPHA);

                // transfer values from "upload" form
                $values = array('datafile' => PARAM_INT,
                                'formatfile' => PARAM_INT,
                                'previewrows' => PARAM_INT);
                $this->transfer_incoming_values($mform, $values);

                $this->add_heading($mform, 'settings', 'moodle', true);

                $name = 'uploadaction';
                $label = get_string($name, $this->tool);
                $options = array(self::ACTION_ADD_NEW_ONLY => get_string('actionaddnewonly', $this->tool),
                                 self::ACTION_ADD_AND_UPDATE => get_string('actionaddandupdate', $this->tool),
                                 self::ACTION_UPDATE_EXISTING => get_string('actionupdateexisting', $this->tool));
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name, self::ACTION_ADD_AND_UPDATE);

                $name = 'passwordaction';
                $label = get_string($name, $this->tool);
                $options = array(self::PASSWORD_CREATE_NEW => get_string('passwordcreatenew', $this->tool),
                                 self::PASSWORD_FILE_FIELD => get_string('passwordfilefield', $this->tool),
                                 self::PASSWORD_FORM_FIELD => get_string('passwordformfield', $this->tool));
                $mform->addGroup(array(
                    $mform->createElement('select', $name, '', $options),
                    $mform->createElement('text', $name.'text', 'size="10"'),
                ), $name.'group', $label, array(' '), false);
                $mform->addHelpButton($name.'group', $name, $this->tool);

                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name, self::PASSWORD_FILE_FIELD);

                $mform->setType($name.'text', PARAM_TEXT);
                $mform->setDefault($name.'text', '');
                $mform->disabledIf($name.'text', $name, 'neq', self::PASSWORD_FORM_FIELD);

                // these options are used by several of the following form fields
                $options = array(self::SELECT_NONE => get_string('no'),
                                 self::SELECT_ALL  => get_string('yes'),
                                 self::SELECT_NEW  => get_string('yesnewusers', $this->tool));

                $name = 'sendpassword';
                $label = get_string($name, $this->tool);
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name, self::SELECT_NEW);

                $name = 'changepassword';
                $label = get_string($name, $this->tool);
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name, self::SELECT_NEW);

                $name = 'uniqueemail';
                $label = get_string($name, $this->tool);
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name,  self::SELECT_NEW);

                $name = 'fixusernames';
                $label = get_string($name, $this->tool);
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_INT);
                $mform->setDefault($name, self::SELECT_NEW);

                //$name = 'selectusers';
                //$label = get_string($name, $this->tool);
                //$mform->addElement('select', $name, $label, $options);
                //$mform->addHelpButton($name, $name, $this->tool);
                //$mform->setType($name, PARAM_INT);
                //$mform->setDefault($name, self::SELECT_NONE);

                $this->add_heading($mform, 'defaultvalues', $this->tool, false);

                $name = 'chooseauthmethod';
                $label = get_string($name,'auth');
                $options = array('nologin' => ''); // always available
                foreach (get_enabled_auth_plugins() as $plugin) {
                    if (get_auth_plugin($plugin)->can_be_manually_set()) {
                        $options[$plugin] = get_string('pluginname', "auth_$plugin");
                    }
                }
                $mform->addElement('select', $name, $label, $options);
                $mform->setDefault($name, 'manual'); // always available
                $mform->setType($name, PARAM_ALPHANUM);
                $mform->addHelpButton($name, $name, 'auth');

                $name = 'timezone';
                $label = get_string($name);
                $default = '99';
                if (class_exists('core_date')) {
                    // Moodle >= 2.9
                    $zones = core_date::get_list_of_timezones();
                } else {
                    // Moodle <= 2.8
                    $zones = get_list_of_timezones();
                }
                $zones[$default] = get_string('serverlocaltime');
                if (empty($CFG->forcetimezone) || $CFG->forcetimezone==$default) {
                    $mform->addElement('select', $name, $label, $zones);
                    $mform->setDefault($name, $default);
                } else {
                    $zone = $zones[$CFG->forcetimezone];
                    $mform->addElement('static', 'forcedtimezone', $label, $zone);
                }

                $name = 'lang';
                $label = get_string('preferredlanguage');
                $langs = get_string_manager()->get_list_of_translations();
                $mform->addElement('select', $name, $label, $langs);
                $mform->setType($name, PARAM_ALPHANUM);
                $mform->setDefault($name, $CFG->lang);

                if (file_exists($CFG->dirroot.'/calendar/classes/type_factory.php')) {
                    // Moodle >= 2.6
                    $types = \core_calendar\type_factory::get_list_of_calendar_types();
                } else {
                    // Moodle <= 2.5
                    $types = array();
                }
                $name = 'calendar';
                if (count($types) > 1) {
                    $label = get_string('preferredcalendar', 'calendar');
                    $mform->addElement('select', $name, $label, $types);
                    $mform->setType($name, PARAM_ALPHA);
                    $mform->setDefault($name, $CFG->calendartype);
                } else {
                    $value = (empty($CFG->calendartype) ? '' : $CFG->calendartype);
                    $mform->addElement('hidden', $name, $label);
                    $mform->setType($name, PARAM_ALPHA);
                }

                // description
                $name = 'description';
                $label = get_string('userdescription');
                $mform->addElement('editor', $name, $label);
                $mform->addHelpButton($name, 'userdescription');
                $mform->setType($name, PARAM_CLEANHTML);

                // set default description
                $element = $mform->getElement($name);
                $value = $element->getValue();
                if (is_array($value) && empty($value['text'])) {
                    $value['text'] = get_string('defaultdescription', $this->tool);
                    $element->setValue($value);
                }

                $submit = 'review';
                $cancel = 'back';
                break;

            case 'review':

                $name = 'formstate';
                unset($_POST[$name]);
                $mform->addElement('hidden', $name, 'import');
                $mform->setType($name, PARAM_ALPHA);

                // transfer values from "upload" form
                $values = array('datafile' => PARAM_INT,
                                'formatfile' => PARAM_INT,
                                'previewrows' => PARAM_INT,
                                'uploadaction' => PARAM_INT,
                                'passwordaction' => PARAM_INT,
                                'passwordactiontext' => PARAM_TEXT,
                                'sendpassword' => PARAM_INT,
                                'changepassword' => PARAM_INT,
                                'fixusernames' => PARAM_INT,
                                'uniqueemail' => PARAM_INT,
                                'chooseauthmethod' => PARAM_ALPHANUM,
                                'timezone' => PARAM_TEXT,
                                'lang' => PARAM_ALPHANUM,
                                'calendar' => PARAM_ALPHANUM,
                                'description[text]' => PARAM_TEXT,
                                'description[format]' => PARAM_INT);
                $this->transfer_incoming_values($mform, $values);

                $name = 'resourcetype';
                $label = get_string($name, $this->tool);
                $options = array('' => get_string('none'),
                                 'page' => get_string('pluginname', 'mod_page'),
                                 'book' => get_string('pluginname', 'mod_book'));
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $name, $this->tool);
                $mform->setType($name, PARAM_ALPHA);
                $mform->setDefault($name, 'book');

                $submit = 'import';
                $cancel = 'back';
                break;

            default:
                $submit = '';
                $cancel = '';
                break;
        }

        if ($submit && $cancel) {
            $name = 'buttons';
            $mform->addGroup(array(
                $mform->createElement('submit', 'submit', get_string($submit, $this->tool)),
                $mform->createElement('cancel', 'cancel', get_string($cancel)),
            ), $name, '', array(' '), false);
            $mform->closeHeaderBefore($name);
        }
    }

    /**
     * get_state
     */
    public function get_state() {
        return $this->formstate;
    }

    public function transfer_incoming_values($mform, $values) {
        foreach ($values as $name => $type) {
            if ($type==PARAM_INT) {
                $default = 0;
            } else {
                $default = '';
            }
            $value = optional_param($name, $default, $type);
            $mform->addElement('hidden', $name, $value);
            $mform->setType($name, $type);
        }
    }

    /**
     * validation
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        //if (empty($data['datafile'])) {
        //    $errors['datafile'] = 'Did you forget to upload a data file?';
        //}

        //if (empty($data['formatfile'])) {
        //    $errors['datafile'] = 'Did you forget to upload a format file?';
        //}

        return $errors;
    }

    /**
     * set_form_id
     *
     * @param  object $mform
     * @param  string $id
     * @return mixed default value of setting
     */
    protected function set_form_id($mform, $id) {
        $attributes = $mform->getAttributes();
        $attributes['id'] = $id;
        $mform->setAttributes($attributes);
    }

    /**
     * add_heading
     *
     * @param object $mform
     * @param string $name
     * @param string $plugin
     * @param boolean $expanded
     */
    public function add_heading($mform, $name, $plugin, $expanded) {
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($name, $expanded);
        }
    }

    /**
     * render_user_table
     */
    public function render_user_table() {
        global $CFG, $USER;

        // get the path to main PHPExcel file and object
        if ($this->phpspreadsheet) {
            // Moodle >= 3.8
            $phpexcel_filepath = $CFG->dirroot.'/lib/phpspreadsheet/vendor/autoload.php';
            $phpexcel_iofactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        } else {
            // Moodle 2.5 - 3.7
            $phpexcel_filepath = $CFG->dirroot.'/lib/phpexcel/PHPExcel/IOFactory.php';
            $phpexcel_iofactory = 'PHPExcel_IOFactory';
        }
        require_once($phpexcel_filepath);

        $fs = get_file_storage();
        $context = self::context(CONTEXT_USER, $USER->id);

        list($datafilename, $datafilepath, $datafilecontent) = $this->get_fileinfo(
            $fs, $context, 'datafile', make_temp_directory('forms')
        );
        list($formatfilename, $formatfilepath, $formatfilecontent) = $this->get_fileinfo(
            $fs, $context, 'formatfile'
        );
        $format = $this->parse_format_xml($formatfilecontent);

        if (is_string($format)) {
            $table = $format;
        } else {
            $table = new html_table();
            $table->head = array();
            $table->data = array();

            if ($datafilepath) {
                $reader = $phpexcel_iofactory::createReaderForFile($datafilepath);
                $workbook = $reader->load($datafilepath);

                $table->tablealign = 'center';
                $table->id = $this->tool.'_'.$this->formstate;
                $table->attributes['class'] = 'generaltable '.$this->tool;
                $table->summary = get_string($this->formstate, $this->tool);
                $table->caption = $this->render_caption($datafilename, $workbook);

                $populate_table = 'populate_'.$this->formstate.'_table';
                $this->$populate_table($workbook, $format, $table);
            }

            if (empty($table->data)) {
                // No data found - shouldn't happen!!
                $table = get_string('emptydatafile', $this->tool);
            }
        }

        if (is_object($table)) {
            $table = html_writer::table($table);
            $table = html_writer::tag('div', $table, array('class' => 'flexible-wrap'));
        } else {
            $table = html_writer::tag('p', $table).
                     html_writer::tag('p', get_string('tryagain', $this->tool));
            $table = html_writer::tag('div', $table, array('class' => 'alert alert-warning'));
        }

        if ($datafilepath) {
            unlink($datafilepath);
        }

        return $table;
    }

    /**
     * get_fileinfo
     */
    public function fileinfo($fs, $context, $paramname, $tempdir='') {
        $filename = '';
        $filepath = '';
        $filecontent = '';
        if ($draftid = optional_param($paramname, 0, PARAM_INT)) {
            $file = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false);
            if (count($file)) {
                $file = reset($file);
                $filename = $file->get_filename();
                $filetype = substr($filename, strrpos($filename, '.'));
                if ($tempdir == '') {
                    $filecontent = $file->get_content();
                } else if ($filepath = tempnam($tempdir, 'tempup_')) {
                    rename($filepath, $filepath.$filetype);
                    $filepath .= $filetype;
                    $file->copy_content_to($filepath);
                }
            }
        } else if (array_key_exists($paramname, $_FILES)) {
            $filename = $_FILES[$param]['name'];
            $filepath = $_FILES[$paramname]['tmp_name'];
            if ($tempdir == '') {
                $filecontent = file_get_contents($filepath);
            }
        }
        return array($filename, $filepath, $filecontent);
    }

    /**
     * parse_format_xml
     */
    public function parse_format_xml($formatfilecontent) {
        global $CFG;

        // get XML parsing library
        require_once($CFG->dirroot.'/lib/xmlize.php');

        if (empty($formatfilecontent)) {
            return get_string('emptyxmlfile', $this->tool);
        }

        $xml = xmlize($formatfilecontent);
        if (empty($xml)) {
            return get_string('invalidxmlfile', $this->tool);
        }

        $name = 'importusersfile';
        if (empty($xml[$name]) || empty($xml[$name]['#'])) {
            return get_string('xmltagmissing', $this->tool, $name);
        }

        $name = 'sheets';
        if (empty($xml['importusersfile']['#'][$name])) {
            return get_string('xmltagmissing', $this->tool, $name);
        }

        $name = 'fields';
        if (empty($xml['importusersfile']['#'][$name])) {
            return get_string('xmltagmissing', $this->tool, $name);
        }

        // If $settings are missing, we can still continue.
        $name = 'settings';
        if (empty($xml['importusersfile']['#'][$name])) {
            $xml['importusersfile']['#'][$name] = array();
        }

        // initialize the $format object
        $format = (object)array(
            'type' => '',
            'params' => array(),
            'sheets' => new stdClass(),
            'fields' => new stdClass(),
            'settings' => new stdClass()
        );

        foreach ($xml['importusersfile']['@'] as $name => $value) {
            if ($name=='type') {
                $format->$name = $value;
            } else {
                $format->params[$name] = $value;
            }
        }

        $ss = 0;
        $settings = &$xml['importusersfile']['#']['settings'];
        while (array_key_exists($ss, $settings)) {

            $s = 0;
            $setting = &$settings[$ss]['#']['setting'];
            while (array_key_exists($s, $setting)) {
                $settingname = $setting[$s]['#']['name'][0]['#'];
                $settingvalue = $setting[$s]['#']['value'][0]['#'];
                $format->settings->$settingname = $settingvalue;
                $s++;
            }
            unset($s, $setting);
            $ss++;
        }
        unset($ss, $settings);

        $ss = 0;
        $sheets = &$xml['importusersfile']['#']['sheets'];
        while (array_key_exists($ss, $sheets)) {

            $stype = $sheets[$ss]['@']['type'];
            if (empty($format->sheets->$stype)) {
                $format->sheets->$stype = array();
            }
            $sindex = count($format->sheets->$stype);

            $s = 0;
            $sheet = &$sheets[$ss]['#']['sheet'];
            while (array_key_exists($s, $sheet)) {

                $format->sheets->$stype[$sindex] = (object)array(
                    'sheetstart' => $sheet[$s]['@']['start'],
                    'sheetend' => $sheet[$s]['@']['end'],
                    'rows' => new stdClass()
                );

                $rr = 0;
                $rows = &$sheet[$s]['#']['rows'];
                while (array_key_exists($rr, $rows)) {

                    $rtype = $rows[$rr]['@']['type'];
                    if (empty($format->sheets->$stype[$sindex]->rows->$rtype)) {
                        $format->sheets->$stype[$sindex]->rows->$rtype = array();
                    }
                    $rindex = count($format->sheets->$stype[$sindex]->rows->$rtype);

                    $r = 0;
                    $row = &$rows[$rr]['#']['row'];
                    while (array_key_exists($r, $row)) {

                        $format->sheets->$stype[$sindex]->rows->$rtype[$rindex] = (object)array(
                            'rowstart' => $row[$r]['@']['start'],
                            'rowend' => $row[$r]['@']['end'],
                            'cells' => new stdClass()
                        );

                        $cc = 0;
                        $cells = &$row[$r]['#']['cells'];
                        while (array_key_exists($cc, $cells)) {

                            $ctype = $cells[$cc]['@']['type'];
                            if (empty($format->sheets->$stype[$sindex]->rows->$rtype[$rindex]->cells->$ctype)) {
                                $format->sheets->$stype[$sindex]->rows->$rtype[$rindex]->cells->$ctype = array();
                            }
                            $cindex = count($format->sheets->$stype[$sindex]->rows->$rtype[$rindex]->cells->$ctype);

                            $c = 0;
                            $cell = &$cells[$cc]['#']['cell'];
                            while (array_key_exists($c, $cell)) {
                                $format->sheets->$stype[$sindex]->rows->$rtype[$rindex]->cells->$ctype[$cindex] = $cell[$c]['#'];
                                $cindex++;
                                $c++;
                            }
                            unset($c, $cell);
                            $cc++;
                        }
                        unset($cc, $cells);
                        $rindex++;
                        $r++;
                    }
                    unset($r, $row);
                    $rr++;
                }
                unset($rr, $rows);
                $sindex++;
                $s++;
            }
            unset($s, $sheet);
            $ss++;
        }
        unset($ss, $sheets);

        // specify order of required keys for each field type
        $keys = (object)array(
            'user' => array('username', 'password', 'email', 'firstname', 'lastname'),
            'course' => array('shortname'),
            'groups' => array('name', 'description'),
        );

        // object to store count of each field type
        $count = new stdClass();

        $ff = 0;
        $fields = &$xml['importusersfile']['#']['fields'];
        while (array_key_exists($ff, $fields)) {

            $table = $fields[$ff]['@']['table'];
            if (empty($format->fields->$table)) {
                $format->fields->$table = array();
            }

            if (empty($count->$table)) {
                $count->$table = 1;
            } else {
                $count->$table++;
            }
            if ($table=='user') {
                $prefix = '';
            } else {
                $prefix = $table.$count->$table.'_';
            }

            if (isset($keys->$table)) {
                foreach ($keys->$table as $key) {
                    $format->fields->$table[$prefix.$key] = '';
                }
            }

            $f = 0;
            $field = &$fields[$ff]['#']['field'];
            while (array_key_exists($f, $field)) {
                $fieldname = $field[$f]['#']['name'][0]['#'];
                $fieldvalue = $field[$f]['#']['value'][0]['#'];
                $format->fields->$table[$prefix.$fieldname] = $fieldvalue;
                $f++;
            }
            unset($f, $field);
            $ff++;
        }
        unset($ff, $fields);

        $name = 'passwordaction';
        $defaultpassword = 'abc123';
        switch (optional_param($name, '', PARAM_INT)) {

            case self::PASSWORD_CREATE_NEW:
                $format->fields->user['password'] = 'RANDOM()';
                break;

            case self::PASSWORD_FILE_FIELD:
                if (empty($format->fields->user['password'])) {
                    $format->fields->user['password'] = $defaultpassword;
                }
                break;

            case self::PASSWORD_FORM_FIELD:
                $format->fields->user['password'] = optional_param($name.'text', $defaultpassword, PARAM_TEXT);
                break;
        }

        return $format;
    }

    /**
     * render_caption
     */
    public function render_caption($datafilename, $workbook) {
        $sheetcount = $workbook->getSheetCount();
        $rowcount = 0;
        for ($s = 0; $s < $sheetcount; $s++) {
            $rowcount += $workbook->getSheet($s)->getHighestDataRow();
        }
        $a = (object)array(
            'filename' => $datafilename,
            'sheetcount' => $sheetcount,
            'rowcount' => $rowcount
        );
        return html_writer::tag('p', get_string('sheetrowcount', $this->tool, $a));
    }

    /**
     * populate_preview_table
     */
    public function populate_preview_table($workbook, $format, $table) {

        // override form defaults with settings from $format file
        if (isset($format->settings)) {
            $mform = $this->_form;
            foreach ($format->settings as $name => $value) {
                $mform->setDefault($name, $value);
            }
        }

        $rowcount = 0;
        $previewrows = optional_param('previewrows', 10, PARAM_INT);

        foreach ($format->sheets->data as $sheet) {

            list($smin, $smax) = $this->get_sheet_range($workbook, $sheet);

            for ($s = $smin; $s <= $smax; $s++) {
                $worksheet = $workbook->setActiveSheetIndex($s - 1);

                foreach ($sheet->rows->data as $row) {

                    list($rmin, $rmax) = $this->get_row_range($worksheet, $row);
                    list($cmin, $cmax, $rowtype) = $this->get_cell_range($row);

                    for ($r = $rmin; $r <= $rmax; $r++) {

                        $cells = array();
                        for ( $c = $cmin; $c <= $cmax; $c++) {
                            $cells[] = $this->get_cell_value($worksheet, $c, $r);
                        }
                        switch ($rowtype) {
                            case self::ROW_TYPE_META:
                                $cell = get_string('row', $this->tool);
                                $table->head = array_merge(array($cell), $cells);
                                $table->align = array_merge(array('center'), array_fill(0, $cmax, 'left'));
                                break;
                            case self::ROW_TYPE_DATA:
                                $cell = new html_table_cell($r);
                                $cell->header = true;
                                $table->data[] = array_merge(array($cell), $cells);
                                $rowcount++;
                                break;
                        }
                        if ($rowcount >= $previewrows) {
                            break 4;
                        }
                    }
                }
            }
        }
    }

    /**
     * get_cell_value
     */
    protected function get_cell_value($worksheet, $c, $r) {
        $coffset = ($this->phpspreadsheet ? 1 : 0); // column offset
        return $worksheet->getCellByColumnAndRow($c + $coffset, $r)->getValue();
    }

    /**
     * populate_review_table
     */
    public function populate_review_table($workbook, $format, $table, $dryrun=true) {
        $this->populate_import_table($workbook, $format, $table, self::MODE_DRYRUN);
    }

    /**
     * populate_import_table
     */
    public function populate_import_table($workbook, $format, $table, $mode=self::MODE_IMPORT) {

        $rowcount = 0;
        if ($mode==self::MODE_IMPORT) {
            $previewrows = 0;
        } else {
            $previewrows = optional_param('previewrows', 10, PARAM_INT);
        }

        $filevars = array();
        if ($table->caption && preg_match('/"([^"]+)"/', $table->caption, $match)) {
            $filevars['filename'] = $match[1];
        }
        foreach ($format->params as $name => $value) {
            $filevars[$name] = $value;
        }

        $separator = get_string('labelsep', 'langconfig');
        foreach ($format->sheets->data as $sheet) {

            list($smin, $smax) = $this->get_sheet_range($workbook, $sheet);

            for ($s = $smin; $s <= $smax; $s++) {
                $worksheet = $workbook->setActiveSheetIndex($s - 1);

                $sheetname = $worksheet->getTitle();
                $sheetvars = array('sheet_name' => $sheetname);

                if (empty($sheet->rows)) {
                    $sheet->rows = stdClass();
                }
                if (empty($sheet->rows->meta)) {
                    $sheet->rows->meta = array();
                }
                if (empty($sheet->rows->data)) {
                    $sheet->rows->meta = array();
                }

                foreach ($sheet->rows->meta as $row) {

                    list($rmin, $rmax) = $this->get_row_range($worksheet, $row);
                    list($cmin, $cmax, $rowtype) = $this->get_cell_range($row);

                    if ($rowtype==self::ROW_TYPE_DATA) {
                        for ($r = $rmin; $r <= $rmax; $r++) {
                            foreach ($row->cells->data as $c => $name) {
                                $sheetvars[$name] = $this->get_cell_value($worksheet, $c, $r);
                            }
                        }
                    }
                }

                foreach ($sheet->rows->data as $row) {

                    list($rmin, $rmax) = $this->get_row_range($worksheet, $row);
                    list($cmin, $cmax, $rowtype) = $this->get_cell_range($row);

                    $vars = array();
                    if ($rowtype==self::ROW_TYPE_DATA) {
                        for ($r = $rmin; $r <= $rmax; $r++) {
                            $rowvars = array();
                            foreach ($row->cells->data as $c => $name) {
                                $rowvars[$name] = $this->get_cell_value($worksheet, $c, $r);
                            }
                            if (empty(array_filter($rowvars)) && empty($rowvars['username'])) {
                                // empty row - shouldn't happen !!
                            } else {
                                $vars = array_merge($filevars, $sheetvars, $rowvars);
                                if ($user = $this->format_fields($format->fields, 'user', $vars)) {
                                    $course = $this->format_fields($format->fields, 'course', $vars);
                                    $groups = $this->format_fields($format->fields, 'groups', $vars);
                                    $data = array_merge($user, $course, $groups);
                                    if ($mode == self::MODE_IMPORT) {
                                        $data['status'] = $this->import_user($data, $sheetname, $r);
                                    }
                                    $cell = new html_table_cell($s.$separator.$r);
                                    $cell->header = true;
                                    $table->data[] = array_merge(array('row' => $cell), $data);
                                    $rowcount++;
                                }
                            }
                            if ($mode==self::MODE_DRYRUN && $rowcount >= $previewrows) {
                                break 4;
                            }
                        }
                    }
                }
            }
        }

        // add field name and descriptions to header row
        if (empty($table->head) && count($table->data)) {

            // cache often-used values
            $search = '/^(course|groups)\d+_(.*)/';
            $params = array('style' => 'font-weight: normal;');

            $table->head = array_keys($table->data[0]);
            foreach ($table->head as $i => $head) {
                if ($head == 'row') {
                    $table->head[$i] = get_string('sheet', $this->tool).
                                       $separator.
                                       get_string('row', $this->tool);
                } else {
                    if (preg_match($search, $head, $match)) {
                        if ($match[1] == 'course') {
                            // course fields
                            if ($match[2] == 'selfenrolmentkey') {
                                $head = get_string('course').
                                        get_string('labelsep', 'langconfig').
                                        get_string('password', 'enrol_self');
                            } else {
                                $head = get_string($match[2]);
                            }
                        } else {
                            // group fields
                            if ($match[2] == 'name' || $match[2] == 'description') {
                                $head = get_string('group'.$match[2], 'group');
                            } else {
                                $head = get_string('group').
                                        get_string('labelsep', 'langconfig').
                                        get_string($match[2], 'group');
                            }
                        }
                    } else {
                        // user fields
                        $head = get_string($head);
                    }
                    $head = html_writer::tag('small', $head, $params);
                    $table->head[$i] .= '<br>'.$head;
                }
            }

            $resourcetype = optional_param('resourcetype', '', PARAM_ALPHA);
            if ($resourcetype && $mode == self::MODE_IMPORT) {
                $table->caption .= $this->add_login_resources($resourcetype, $table);
            }
        }
    }

    /**
     * get_sheet_range
     */
    public function get_sheet_range($workbook, $sheet) {
        if (isset($sheet->sheetstart) && is_numeric($sheet->sheetstart)) {
            $smin = $sheet->sheetstart;
        } else {
            $smin = 1;
        }
        if (isset($sheet->sheetend) && is_numeric($sheet->sheetend)) {
            $smax = $sheet->sheetend;
        } else {
            $smax = $workbook->getSheetCount();
        }
        return array($smin, $smax);
    }

    /**
     * get_row_range
     */
    public function get_row_range($worksheet, $row) {
        if (isset($row->rowstart) && is_numeric($row->rowstart)) {
            $rmin = $row->rowstart;
        } else {
            $rmin = 1;
        }
        if (isset($row->rowend) && is_numeric($row->rowend)) {
            $rmax = $row->rowend;
        } else {
            $rmax = $worksheet->getHighestDataRow();
        }
        return array($rmin, $rmax);
    }

    /**
     * get_cell_range
     */
    public function get_cell_range($row) {
        $cmin = 1;
        $cmax = 0;
        switch (true) {
            case isset($row->cells->meta):
                $cmax = max($cmax, count($row->cells->meta));
                $rowtype = self::ROW_TYPE_META;
                break;

            case isset($row->cells->data):
                $cmax = max($cmax, count($row->cells->data));
                $rowtype = self::ROW_TYPE_DATA;
                break;

            default:
                $rowtype = self::ROW_TYPE_NONE;
                break;
        }
        return array($cmin, $cmax, $rowtype);
    }

    /**
     * format_fields
     */
    public function format_fields($fields, $type, &$vars) {
        if (empty($fields->$type)) {
            return array();
        }
        $values = array();
        foreach ($fields->$type as $name => $value) {
            $values[$name] = $this->format_field($value, $vars);
        }
        return $values;
    }

    /**
     * format_field
     */
    public function format_field($value, &$vars) {

        $search = '/LOWERCASE|PROPERCASE|UPPERCASE'.
                   '|EXTRACT|FULLWIDTH|HALFWIDTH'.
                   '|RANDOM|REPLACE|SUBSTRING/u';

        // search and replace function names (starting from the last one)
        if (preg_match_all($search, $value, $matches, PREG_OFFSET_CAPTURE)) {

            for ($m = count($matches[0]); $m > 0; $m--) {
                list($match, $start) = $matches[0][$m - 1];

                $mode = 0;
                // 0: find open parentheses
                // 1: find start argument
                // 2: find end unquoted argument
                // 3: find end quoted argument
                // 4: find comma or closing parenthesis
                // 5: complete
                // 6: error !!

                $args = array();
                $a = -1; // index on $args

                $i = ($start + strlen($match));
                $i_max = strlen($value);
                while ($i < $i_max && $mode < 5) {
                    switch ($mode) {

                        case 0:
                            // expecting opening parenthesis
                            switch ($value[$i]) {

                                // leading white space is ignored
                                case ' ':
                                    break;

                                // opening parenthesis - yay!
                                case '(':
                                    $mode = 1;
                                    break;

                                default:
                                    $args = "No open bracket found for $match";
                                    $mode = 6;
                            }
                            break;

                        case 1:
                            // expecting start of argument
                            switch ($value[$i]) {

                                // leading white space is ignored
                                case ' ':
                                    break;

                                case ',':
                                    $mode = 6;
                                    $args = "Comma not expected parsing $match";
                                    break;

                                // closing parenthesis (i.e. end of arguments)
                                case ')':
                                    $mode = 5;
                                    break;

                                // start of a quoted quoted argument
                                case '"':
                                    $a++;
                                    $args[$a] = '';
                                    $mode = 3;
                                    break;

                                // first char of an unquoted argument
                                default:
                                    $a++;
                                    $args[$a] = $value[$i];
                                    $mode = 2;
                            }
                            break;

                        case 2:
                            // expecting end of unquoted argument
                            switch ($value[$i]) {
                                case ',':
                                    $mode = 1;
                                    break;

                                case ')':
                                    $mode = 5;
                                    break;

                                default:
                                    // next char of an unquoted argument
                                    $args[$a] .= $value[$i];
                            }
                            break;

                        case 3:
                            // expecting end of quoted argument
                            switch ($value[$i]) {
                                case '"':
                                    $mode = 4;
                                    break;

                                default:
                                    // next char of an quoted argument
                                    $args[$a] .= $value[$i];
                            }
                            break;

                        case 4:
                            // expecting comma or closing parenthesis
                            switch ($value[$i]) {
                                case ' ':
                                    break;

                                case ',':
                                    $mode = 1;
                                    break;

                                case ')':
                                    $mode = 5;
                                    break;

                                default:
                                    $mode = 6;
                                    $args = "Character '".$value[$i]."' not expected after quoted string in $match";
                            }
                            break;
                    }
                    $i++;
                }

                if ($mode == 6) {
                    $replace = $args; // error message
                } else {
                    foreach ($args as $a => $arg) {
                        if (array_key_exists($arg, $vars)) {
                            $args[$a] = $vars[$arg];
                        }
                    }
                    $replace = $this->format_function($match, $args);
                }
                $value = substr_replace($value, $replace, $start, ($i - $start));
            }
        }

        // replace any vars in open text (i.e. outside functions)
        return strtr($value, $vars);
    }

    public function format_function($name, $args) {

        switch ($name) {
            case 'LOWERCASE':
                return self::textlib('strtolower', $args[0]);

            case 'PROPERCASE':
                return self::textlib('strtotitle', $args[0]);

            case 'UPPERCASE':
                return self::textlib('strtoupper', $args[0]);

            case 'EXTRACT':
                $args[0] = preg_split('/\s+/u', $args[0]);
                if (array_key_exists(1, $args)) {
                    if ($args[1] > 0) {
                        $args[1]--;
                    }
                    if (array_key_exists(2, $args)) {
                        $args[0] = array_splice($args[0], $args[1], $args[2]);
                    } else {
                        $args[0] = array_splice($args[0], $args[1]);
                    }
                }
                return implode(' ', $args[0]);

            case 'FULLWIDTH':
                $args = mb_convert_kana($args[0], 'AS', 'UTF-8');
                $args = strtr($args, array_flip($this->punctuation));
                return $args;

            case 'HALFWIDTH':
                $args = mb_convert_kana($args[0], 'as', 'UTF-8');
                $args = strtr($args, $this->punctuation);
                return $args;

            case 'RANDOM':
                return $this->create_random((object)array(
                    'countlowercase' => (array_key_exists(0, $args) && is_numeric($args[0]) ? $args[0] : 1),
                    'countuppercase' => (array_key_exists(1, $args) && is_numeric($args[1]) ? $args[1] : 0),
                    'countnumeric'   => (array_key_exists(2, $args) && is_numeric($args[2]) ? $args[2] : 2),
                    'shufflerandom'  => (array_key_exists(3, $args) && is_numeric($args[3]) ? $args[3] : 0)
                ));
                break;

            case 'REPLACE':
                $i = 1;
                $params = array();
                while (array_key_exists($i, $args) && array_key_exists($i + 1, $args)) {
                    $params[$args[$i++]] = $args[$i++];
                }
                if (empty($params)) {
                    return $args[0];
                }
                return strtr($args[0], $params);

            case 'SUBSTRING':
                switch (count($args)) {
                    case 0: $args[0] = ''; // intentional drop through
                    case 1: $args[1] =  1; // intentional drop through
                    case 2: $args[2] =  self::textlib('strlen', $args[0]);
                }
                return self::textlib('substr', $args[0], $args[1] - 1, $args[2]);

            default:
                return implode(',', $args);
        }
    }

    /**
     * import_user
     */
    public function import_user($data, $sheet, $row) {
        global $CFG, $DB;

        $time = time();
        $status = array();

        $changepassword = optional_param('changepassword', 0, PARAM_INT);
        $fixusernames = optional_param('fixusernames', 0, PARAM_INT);
        $uploadaction = optional_param('uploadaction', 0, PARAM_INT);
        $uniqueemail = optional_param('uniqueemail', 0, PARAM_INT);

        if (empty($data['username'])) {
            $a = (object)array('sheet' => $sheet,
                               'row' => $row);
            return $this->format_status('nousername', 'text-warning', null, $a);
        }

        $data['rawusername'] = $data['username'];
        $data['username'] = strtolower($data['username']);
        $data['cleanusername'] = preg_replace('/[^a-z0-9_.-]/u', '', $data['username']);

        // make sure that password is encoded to a minimum level
        if (isset($data['password'])) {
            if (empty($CFG->passwordsaltmain)) {
                $salt = '';
            } else {
                $salt = $CFG->passwordsaltmain;
            }
            $data['password'] = md5($data['password'].$salt);
        }
 
        if ($uniqueemail && ($user = $DB->get_record('user', array('username' => $data['email'])))) {
            $user->username = $data['username']; // Ensure new username.
        } else if ($user = $DB->get_record('user', array('username' => $data['rawusername']))) {
            $user->username = $data['username']; // Ensure lower case username.
        } else {
            $user = $DB->get_record('user', array('username' => $data['username']));
        }

        if ($user) {
            if ($uploadaction == self::ACTION_ADD_NEW_ONLY) {
                $status[] = $this->format_status_user('userskipped', 'text-warning', null, $user->id);
                $update = false;
            } else {
                if ($fixusernames == self::SELECT_ALL) {
                    $data['username'] = $data['cleanusername'];
                }
                $update = true;
            }
        } else {
            if ($uploadaction == self::ACTION_UPDATE_EXISTING) {
                $a = (object)array('sheet' => $sheet,
                                   'row' => $row,
                                   'username' => $data['username']);
                return $this->format_status('missingusername', 'text-warning', null, $data['username']);
            }
            if ($fixusernames == self::SELECT_NEW) {
                $data['username'] = $data['cleanusername'];
            }
            $update = true;
            $user = (object)array(
                'username'  => '',
                'password'  => '',
                'auth'      => '',
                'confirmed' => 1,
                'policyagreed' => 1,
                'deleted'   => 0,
                'suspended' => 0,
                'mnethostid' => $CFG->mnet_localhost_id,
                'idnumber'  => '',
                'firstname' => '',
                'lastname'  => '',
                'email'     => '',
                'emailstop' => 0,
                'icq'       => '',
                'skype'     => '',
                'yahoo'     => '',
                'aim'       => '',
                'msn'       => '',
                'phone1'    => '',
                'phone2'    => '',
                'institution' => '',
                'department'  => '',
                'address'   => '',
                'city'      => '',
                'country'   => '',
                'lang'      => '',
                'theme'     => '',
                'timezone'  => '',
                'firstaccess'   => 0,
                'lastaccess'    => 0,
                'lastlogin'     => 0,
                'currentlogin'  => 0,
                'lastip'        => '',
                'secret'        => '',
                'picture'       => 0,
                'url'           => '',
                'description'   => '',
                'descriptionformat' => 1,
                'mailformat'    => 1,
                'maildigest'    => 0,
                'maildisplay'   => 2,
                'autosubscribe' => 1,
                'trackforums'   => 0,
                'timecreated'   => $time,
                'timemodified'  => 0,
                'trustbitmask'  => 0,
                'imagealt'      => '',
                'lastnamephonetic'  => '',
                'firstnamephonetic' => '',
                'middlename'    => '',
                'alternatename' => '',
                'calendartype'  => ''
            );
        }

        if ($update) {

            // these fields can be set from $data
            $names = array('username', 'password', 'email',
                           'firstname', 'middlename', 'lastname',
                           'firstnamephonetic', 'lastnamephonetic', 'alternatename',
                           'icq', 'skype', 'yahoo', 'aim', 'msn', 'phone1', 'phone2',
                           'institution', 'department', 'address', 'city', 'country',
                           'description', 'descriptionformat',
                           'lang', 'theme', 'timezone');

            // update $user using values from $data
            foreach ($data as $name => $value) {
                if (in_array($name, $names)) {
                    $user->$name = $value;
                }
            }

            // use default values from form
            $names = array('timezone'          => PARAM_TEXT,
                           'lang'              => PARAM_ALPHANUM,
                           'calendar'          => PARAM_ALPHANUM,
                           'description[text]' => PARAM_TEXT,
                           'description[format]' => PARAM_INT);

            foreach ($names as $name => $type) {
                $fieldname = strtr($name, array('[' => '', ']' => ''));
                if (empty($user->$name)) {
                    $user->$fieldname = optional_param($name, '', $type);
                }
            }

            if (empty($user->auth)) {
                $user->auth = optional_param('chooseauthmethod', 'manual', PARAM_ALPHANUM);
            }
        }

        if (empty($user->id)) {
            if (! $user->id = $DB->insert_record('user', $user)) {
                return $this->format_status('erroraddinguser', 'text-danger');
            }
            $status[] = $this->format_status_user('addedusertosite', 'text-success', $user->id);
        } else if ($update) {
            $DB->update_record('user', $user);
            $status[] = $this->format_status_user('userupdated', 'text-info', $user->id);
        }

        $courseids = array();

        $search = '/^course\d+_shortname$/';
        $shortnames = preg_grep($search, array_keys($data));
        foreach ($shortnames as $shortname) {

            // fetch course id
            $params = array('shortname' => $data[$shortname]);
            if (! $courseid = $DB->get_records('course', $params, 'id', 'id,shortname')) {
                $status[] = $this->format_status('missingcourse', 'text-danger');
                continue;
            }
            $courseid = reset($courseid)->id;
            $courseids[$courseid] = 1;

            // fetch course context
            if (! $context = self::context(CONTEXT_COURSE, $courseid)) {
                $status[] = $this->format_status('missingcoursecontext', 'text-danger');
                continue;
            }

            // add student role
            if (! $role = $this->get_role('student')) {
                $status[] = $this->format_status('erroraddingstudentrole', 'text-danger');
                continue;
            }
            if (isset($role->added)) {
                $status[] = $this->format_status('addedstudentrole', 'text-danger');
            }

            // assign student role
            if (! $role_assignment = $this->get_role_assignment($context->id, $role->id, $user->id, $time)) {
                $status[] = $this->format_status('errorassigningstudentrole', 'text-danger');
                continue;
            }
            if (isset($role_assignment->added)) {
                $status[] = $this->format_status('assignedstudentrole', 'text-success');
            }

            // process groups
            $i = substr($shortname, 6, -10);
            $search = '/^groups'.$i.'_name$/';
            $names = preg_grep($search, array_keys($data));
            foreach ($names as $name) {
                $name = $data[$name];
                $description = 'groups'.$i.'_description';
                if (empty($data[$description])) {
                    $description = '';
                } else {
                    $description = $data[$description];
                }
                // fetch group
                if (! $group = $this->get_group($courseid, $name, $description, $time)) {
                    $status[] = $this->format_status('erroraddinggroup', 'text-danger');
                    continue;
                }
                if (isset($group->added)) {
                    $status[] = $this->format_status_group('addedgroup', 'text-success', $courseid, $group->id);
                }
                // add user to group
                if (! $member = $this->get_group_member($group->id, $user->id, $time)) {
                    $status[] = $this->format_status_group('erroraddingusertogroup', 'text-danger', $courseid, $group->id);
                    continue;
                }
                if (isset($member->added)) {
                    $status[] = $this->format_status_group('addedusertogroup', 'text-success', $courseid, $group->id);
                } else {
                    $status[] = $this->format_status_group('useralreadyingroup', 'text-info', $courseid, $group->id);
                }
            }

            if (method_exists($context, 'mark_dirty')) {
                // Moodle >= 2.2
                $context->mark_dirty();
            } else {
                // Moodle <= 2.1
                mark_context_dirty($context->path);
            }

            // get manual enrolment instance for this course
            if (! $enrol = $this->get_enrol($courseid, $role->id, $user->id, $time, $user->auth)) {
                $status[] = $this->format_status('erroraddingenrolmethod', 'text-danger');
                continue;
            }
            if (isset($enrol->added)) {
                $status[] = $this->format_status_course('addedenrolmethod', 'text-success', $courseid);
            }

            // enrol the user in this course
            if (! $user_enrolment = $this->get_user_enrolment($enrol->id, $user->id, $time)) {
                $status[] = $this->format_status('errorenrollinguser', 'text-danger');
                continue;
            }
            if (isset($user_enrolment->added)) {
                $status[] = $this->format_status_course('userenrolled', 'text-success', $courseid);
            } else {
                $status[] = $this->format_status_course('useralreadyenrolled', 'text-info', $courseid);
            }
        }

        // update group cache on Moodle >= 3.0
        if (function_exists('groups_cache_groupdata')) {
            foreach ($courseids as $courseid) {
                groups_cache_groupdata($courseid);
            }
        }

        return html_writer::alist($status, array('class' => 'list-status-messages'));
    }

    /**
     * format_status_user
     */
    public function format_status_user($stringname, $class, $userid) {    
        $url = new moodle_url('/user/profile.php', array('id' => $userid));
        return $this->format_status($stringname, $class, $url);
    }

    /**
     * format_status_course
     */
    public function format_status_course($stringname, $class, $courseid) {    
        $url = new moodle_url('/course/view.php', array('id' => $courseid));
        return $this->format_status($stringname, $class, $url);
    }

    /**
     * format_status_group
     */
    public function format_status_group($stringname, $class, $courseid, $groupid) {    
        $url = new moodle_url('/group/group.php', array('id' => $groupid, 'courseid' => $courseid));
        return $this->format_status($stringname, $class, $url);
    }

    /**
     * format_status
     */
    public function format_status($stringname, $class, $url=null, $a=null) {    
        $status = get_string($stringname, $this->tool, $a);
        $status = html_writer::tag('small', $status, array('class' => $class));
        if ($url) {
            $params = array('onclick' => "this.target='importusers'");
            $status = html_writer::link($url, $status, $params);
        }
        return $status;
    }

    /**
     * create_random
     *
     * @param integer $data
     * @param integer $name
     */
    public function create_random($data) {
        $chars = array();
        for ($i=0; $i<$data->countlowercase; $i++) {
            $chars[] = array_rand($this->lowercase);
        }
        for ($i=0; $i<$data->countuppercase; $i++) {
            $chars[] = array_rand($this->uppercase);
        }
        for ($i=0; $i<$data->countnumeric; $i++) {
            $chars[] = array_rand($this->numeric);
        }
        if ($data->shufflerandom) {
            shuffle($chars);
        }
        return implode('', $chars);
    }

    /**
     * get_role
     *
     * @param string $name
     * @return object or boolean (FALSE)
     */
    public function get_role($name) {
        $table = 'role';
        $params = array('shortname' => $name);
        $values = array('name'        => $name,
                        'shortname'   => $name,
                        'description' => $name,
                        'sortorder'   => 0,
                        'archetype'   => $name);
        return $this->get_record($table, $params, $values);
    }

    /**
     * get_enrol
     *
     * @param integer $courseid
     * @param integer $roleid
     * @param integer $userid modifierid for new enrol record
     * @param integer $time
     * @return object or boolean (FALSE)
     */
    public function get_enrol($courseid, $roleid, $userid, $time, $authmethod='manual') {
        $table = 'enrol';
        $params = array('enrol' => $authmethod,
                        'roleid' => $roleid,
                        'courseid' => $courseid);
        $values = array('enrol'        => $authmethod,
                        'roleid'       => $roleid,
                        'courseid'     => $courseid,
                        'modifierid'   => $userid,
                        'timecreated'  => $time,
                        'timemodified' => $time);
        return $this->get_record($table, $params, $values);
    }

    /**
     * get_role_assignment
     *
     * @param integer $contextid
     * @param integer $roleid
     * @param integer $userid to be assigned a role
     * @param integer $time
     * @return boolean TRUE  if a new role_assignment was created, FALSE otherwise
     */
    public function get_role_assignment($contextid, $roleid, $userid, $time) {
        global $USER;
        $table = 'role_assignments';
        $params = array('roleid' => $roleid,
                        'userid' => $userid,
                        'contextid' => $contextid);
        $values = array('roleid'       => $roleid,
                        'contextid'    => $contextid,
                        'userid'       => $userid,
                        'modifierid'   => $USER->id,
                        'timemodified' => $time);
        return $this->get_record($table, $params, $values);
    }

    /**
     * get_user_enrolment
     *
     * @param integer $enrolid
     * @param integer $userid to be enrolled
     * @param integer $time
     * @return mixed  object if user enrolment was found or created; otherwise FALSE
     */
    public function get_user_enrolment($enrolid, $userid, $time) {
        global $USER;
        $table = 'user_enrolments';
        $params = array('userid' => $userid,
                        'enrolid' => $enrolid);
        $values = array('userid'       => $userid,
                        'enrolid'      => $enrolid,
                        'modifierid'   => $USER->id,
                        'timestart'    => $time,
                        'timeend'      => 0,
                        'timecreated'  => $time,
                        'timemodified' => $time);
        $update = array('timestart' => $time,
                        'timeend' => 0);
        return $this->get_record($table, $params, $values, $update);
    }

    /**
     * get_group
     *
     * @param integer $courseid
     * @param string  $name
     * @param string  $description
     * @param integer $time
     * @return mixed  object if group was found or created; otherwise FALSE
     */
    public function get_group($courseid, $name, $description, $time) {
        $table = 'groups';
        $params = array('name' => $name,
                        'courseid' => $courseid);
        $values = array('name'         => $name,
                        'courseid'     => $courseid,
                        'description'  => $description,
                        'descriptionformat' => FORMAT_MOODLE,
                        'enrolmentkey' => '',
                        'timecreated'  => $time,
                        'timemodified' => $time);
        return $this->get_record($table, $params, $values);
    }

    /**
     * get_group_member
     *
     * @param integer $groupid
     * @param integer $userid
     * @param integer $time
     * @return mixed  object if group member was found or created; otherwise FALSE
     */
    public function get_group_member($groupid, $userid, $time) {
        $table = 'groups_members';
        $params = array('groupid' => $groupid,
                        'userid' => $userid);
        $values = array('groupid'  => $groupid,
                        'userid'   => $userid,
                        'timeadded' => $time);
        return $this->get_record($table, $params, $values);
    }

    /**
     * get_record
     *
     * @param string $table
     * @param array  $params
     * @param array  $values
     * @param array  $update (optional, default=array())
     * @return mixed  object if record was created; otherwise FALSE
     */
    public function get_record($table, $params, $values, $update=array()) {
        global $DB;

        // fetch existing record, if it exists
        if ($record = $DB->get_record($table, $params)) {
            foreach ($update as $name => $value) {
                $record->$name = $value;
            }
            return $record;
        }

        // update sortorder (not usually necessary)
        if (key_exists('sortorder', $values)) {
            if ($sortorder = $DB->get_field($table, 'MAX(sortorder)', array())) {
                $values['sortorder'] = ($sortorder + 1);
            } else {
                $values['sortorder'] = 1;
            }
        }

        // add new record
        if ($values['id'] = $DB->insert_record($table, $values)) {
            $values['added'] = true;
            return (object)$values;
        }

        // could not add new record - shoudn't happen !!
        return false;
    }

    /**
     * add_login_resources
     *
     * @param string $resourcetype
     * @param object $table
     */
    public function add_login_resources($resourcetype, $table) {
        global $DB;

        $courses = array();

        foreach ($table->data as $data) {
            if (empty($data['username'])) {
                continue;
            }
            $userid = $DB->get_field('user', 'id', array('username' => $data['username']));
            if (empty($userid)) {
                continue;
            }
            $user = (object)array('username' => $data['username'],
                                  'password' => $data['password'],
                                  'firstname' => $data['firstname'],
                                  'lastname' => $data['lastname']);
            
            $search = '/^course\d+_shortname$/';
            $coursecolumns = preg_grep($search, array_keys($data));
            foreach ($coursecolumns as $coursecolumn) {

                $coursename = $data[$coursecolumn];
                if (! array_key_exists($coursename, $courses)) {
                    $params = array('shortname' => $coursename);
                    if ($course = $DB->get_records('course', $params, 'id', 'id,shortname')) {
                        $course = reset($course);
                        $course->users = array();
                        $course->groups = array();
                    }
                    $courses[$coursename] = $course;
                }

                if ($course = &$courses[$coursename]) {
                    $course->users[$userid] = $user;

                    $i = substr($coursecolumn, 6, -10);
                    $search = '/^groups'.$i.'_name$/';
                    $groupcolumns = preg_grep($search, array_keys($data));
                    foreach ($groupcolumns as $groupcolumn) {

                        $groupname = $data[$groupcolumn];
                        if (! array_key_exists($groupname, $course->groups)) {
                            $params = array('courseid' => $course->id, 'name' => $groupname);
                            if ($group = $DB->get_records('groups', $params, 'id', 'id,name')) {
                                $group = reset($group);
                                $group->users = array();
                            }
                            $course->groups[$groupname] = $group;
                        }
                        if ($group = &$course->groups[$groupname]) {
                            $group->users[$userid] = $user;
                        }
                        unset($group);
                    }
                }
                unset($course);
            }
        }

        $linkparams = array('target' => 'importusers');

        $resourcelinks = array();
        foreach ($courses as $shortname => $course) {

            $params = array('id' => $course->id);
            $courselink = new moodle_url('/course/view.php', $params);
            $courselink = html_writer::link($courselink, $shortname, $linkparams);

            foreach ($course->groups as $groupname => $group) {

                $params = array('id' => $course->id, 'group' => $group->id);
                $grouplink = new moodle_url('/group/index.php', $params);
                $grouplink = html_writer::link($grouplink, $groupname, $linkparams);

                $table = new html_table();
                $table->attributes['style'] = 'max-width: 100%; '.
                                              'width: max-content;';
                foreach ($group->users as $userid => $user) {

                    $params = array('course' => $course->id, 'id' => $userid);
                    $userlink = new moodle_url('/user/view.php', $params);
                    $userlink = html_writer::link($userlink, $user->username, $linkparams);

                    $row = new html_table_row();
                    $row->cells[] = new html_table_cell($userlink);
                    $row->cells[] = new html_table_cell($user->password);
                    $row->cells[] = new html_table_cell($user->firstname);
                    $row->cells[] = new html_table_cell($user->lastname);
                    $row->cells[] = new html_table_cell($courselink);
                    $row->cells[] = new html_table_cell($grouplink);
                    $table->data[] = $row;
                }
                if (count($table->data)) {
                    $table->head = array(
                        get_string('username'),
                        get_string('password'),
                        get_string('firstname'),
                        get_string('lastname'),
                        get_string('course'),
                        get_string('group'),
                    );
                    $table = html_writer::table($table);
                    if ($cm = $this->add_login_resource($resourcetype, $course->id, $table, $groupname)) {
                        $resourcelink = new moodle_url("/mod/$resourcetype/view.php", array('id' => $cm->id));
                        $resourcelink = html_writer::link($resourcelink, $cm->name, $linkparams);
                        $resourcelinks[$cm->id] = $resourcelink;
                    }
                }
            }

        }

        if (empty($resourcelinks)) {
            $resourcelinks = '';
        } else {
            $resourcelinks = html_writer::alist($resourcelinks, array('class' => 'resourcelinks'));
        }
        return $resourcelinks;
    }

    /**
     * add_login_resource
     *
     * @param  string  $resourcetype
     * @param  object  $course
     * @param  string  $table
     * @param  string  $groupname (optional, default='')
     * @param  integer $sectionnum (optional, default=0)
     * @return object  $cm course_module record of newly added/updated page resource
     */
    public function add_login_resource($resourcetype, $courseid, $table, $groupname='', $sectionnum=0) {
        global $DB, $USER;

        static $moduleid = null;
        if ($moduleid === null) {
            $moduleid = $DB->get_field('modules', 'id', array('name' => $resourcetype));
        }

        if ($resourcetype == 'page' && $groupname) {
            $name = get_string('userlogindetailsgroup', 'tool_importusers', $groupname);
        } else {
            $name = get_string('userlogindetails', 'tool_importusers');
        }

        $select = 'cm.*, ? AS modname, ? AS modulename, r.name AS name';
        $from   = '{course_modules} cm '.
                  'JOIN {'.$resourcetype.'} r ON cm.module = ? AND cm.instance = r.id';
        $where  = 'r.course = ? AND r.name = ? AND deletioninprogress = ?';
        $params = array($resourcetype, $resourcetype, $moduleid, $courseid, $name, 0);
        $order  = 'cm.visible DESC, cm.added DESC'; // newest, visible cm first

        if ($cm = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params, 0, 1)) {
            $cm  = reset($cm);
        } else {
            $cm = array(
                // standard fields for adding a new cm
                'course'          => $courseid,
                'section'         => $sectionnum,
                'module'          => $moduleid,
                'modname'         => $resourcetype,
                'modulename'      => $resourcetype,
                'add'             => $resourcetype,
                'update'          => 0,
                'return'          => 0,
                'cmidnumber'      => '',
                'visible'         => 0,
                'groupmode'       => 0,
                'MAX_FILE_SIZE'   => 0,

                // common fields for all resource types
                'name'            => $name,
                'intro'           => ' ',
                'introformat'     => FORMAT_HTML
            );

            switch ($resourcetype) {
                case 'book':
                    $cm = array_merge($cm, array(
                        // see BOOK constants in "mod/book/locallib.php"
                        'numbering'       => 3, // BOOK_NUM_INDENTED
                        'navstyle'        => 1, // BOOK_LINK_IMAGE
                        'customtitles'    => 0,
                        'revision'        => 0,
                        'timecreated'     => time(),
                        'timemodified'    => time(),
                    ));
                    break;

                case 'page':
                    $cm = array_merge($cm, array(
                        'content'         => $table,
                        'contentformat'   => FORMAT_HTML,
                        'tobemigrated'    => 0,
                        'legacyfiles'     => 0,
                        'legacyfileslast' => 0,
                        'display'         => 0,
                        'displayoptions'  => '',
                        'revision'        => 0,
                        'timemodified'    => time(),
                    ));
                    break;
            }

            $cm = (object)$cm;

            if (! $cm->instance = $DB->insert_record($resourcetype, $cm)) {
                return false;
            }
            if (! $cm->id = add_course_module($cm) ) {
                throw new moodle_exception('Could not add a new course module');
            }
            $cm->coursemodule = $cm->id;
            if (function_exists('course_add_cm_to_section')) {
                // Moodle >= 2.4
                $sectionid = course_add_cm_to_section($courseid, $cm->id, $sectionnum);
            } else {
                // Moodle <= 2.3
                $sectionid = add_mod_to_section($cm);
            }
            if ($sectionid===false) {
                throw new moodle_exception('Could not add new course module to section: '.$sectionnum);
            }
            if (! $DB->set_field('course_modules', 'section',  $sectionid, array('id' => $cm->id))) {
                throw new moodle_exception('Could not update the course module with the correct section');
            }

            // if the section is hidden, we should also hide the new resource
            if (! isset($cm->visible)) {
                $cm->visible = $DB->get_field('course_sections', 'visible', array('id' => $sectionid));
            }
            set_coursemodule_visible($cm->id, $cm->visible);

            // Trigger mod_created event with information about this page resource.
            if (class_exists('\\core\\event\\course_module_created')) {
                // Moodle >= 2.6
                \core\event\course_module_created::create_from_cm($cm)->trigger();
            } else {
                $event = (object)array(
                    'cmid'       => $cm->id,
                    'courseid'   => $cm->course,
                    'modulename' => $cm->modulename,
                    'name'       => $cm->name,
                    'userid'     => $USER->id
                );
                if (function_exists('events_trigger_legacy')) {
                    // Moodle 2.6 - 3.0 ... so not used here anymore
                    events_trigger_legacy('mod_created', $event);
                } else {
                    // Moodle <= 2.5
                    events_trigger('mod_created', $event);
                }
            }
        }

        switch ($resourcetype) {

            case 'page':
                $DB->set_field('page', 'content', $table, array('id' => $cm->instance));
                break;

            case 'book':

                $separator = get_string('labelsep', 'langconfig');
                $strlen = self::textlib('strlen', $separator);
                if ($groupname && ($pos = self::textlib('strpos', $groupname, $separator))) {
                    $prefix = $separator.self::textlib('substr', $groupname, 0, $pos - 1);
                    $groupname = self::textlib('substr', $groupname, $pos + $strlen);
                } else {
                    $prefix = '';
                }

                if ($chapter = $DB->get_record('book_chapters', array('bookid' => $cm->instance, 'title' => $groupname))) {
                    $DB->set_field('book_chapters', 'content', $table, array('id' => $chapter->id));
                } else {
                    if ($pagenum = $DB->get_field('book_chapters', 'MAX(pagenum)', array('bookid' => $cm->instance))) {
                        $pagenum = $pagenum + 1;
                    } else {
                        // add "Group logins" title page
                        $pagenum = 1;
                        $chapter = (object)array('bookid' => $cm->instance,
                                                 'pagenum' => $pagenum++,
                                                 'subchapter' => 0,
                                                 'title' => get_string('userlogindetails', 'tool_importusers').$prefix,
                                                 'content' => '',
                                                 'contentformat' => FORMAT_HTML,
                                                 'timecreated' => time(),
                                                 'timemodified' => time());
                        $chapter->id = $DB->insert_record('book_chapters', $chapter);
                    }
                    $chapter = (object)array('bookid' => $cm->instance,
                                             'pagenum' => $pagenum,
                                             'subchapter' => 1,
                                             'title' => $groupname,
                                             'content' => $table,
                                             'contentformat' => FORMAT_HTML,
                                             'timecreated' => time(),
                                             'timemodified' => time());
                    $chapter->id = $DB->insert_record('book_chapters', $chapter);
                }
                break;
        }

        // Trigger mod_updated event with information about this page resource.
        if (class_exists('\\core\\event\\course_module_updated')) {
            // Moodle >= 2.6
            \core\event\course_module_updated::create_from_cm($cm)->trigger();
        } else {
            $event = (object)array(
                'cmid'       => $cm->id,
                'courseid'   => $cm->course,
                'modulename' => $cm->modulename,
                'name'       => $cm->name,
                'userid'     => $USER->id
            );
            if (function_exists('events_trigger_legacy')) {
                // Moodle 2.6 - 3.0 ... so not used here anymore
                events_trigger_legacy('mod_updated', $event);
            } else {
                // Moodle <= 2.5
                events_trigger('mod_updated', $event);
            }
        }

        // rebuild_course_cache (needed for Moodle 2.0)
        rebuild_course_cache($courseid, true);

        return $cm;
    }

    /**
     * textlib
     *
     * a wrapper method to offer consistent API for textlib class
     * in Moodle 2.0 and 2.1, $textlib is first initiated, then called
     * in Moodle 2.2 - 2.5, we use only static methods of the "textlib" class
     * in Moodle >= 2.6, we use only static methods of the "core_text" class
     *
     * @param string $method
     * @param mixed any extra params that are required by the textlib $method
     * @return result from the textlib $method
     * @todo Finish documenting this function
     */
    static public function textlib() {
        if (class_exists('core_text')) {
            // Moodle >= 2.6
            $textlib = 'core_text';
        } else if (method_exists('textlib', 'textlib')) {
            // Moodle 2.0 - 2.1
            $textlib = textlib_get_instance();
        } else {
            // Moodle 2.2 - 2.5
            $textlib = 'textlib';
        }
        $args = func_get_args();
        $method = array_shift($args);
        $callback = array($textlib, $method);
        return call_user_func_array($callback, $args);
    }

    /**
     * context
     *
     * a wrapper method to offer consistent API to get contexts
     * in Moodle 2.0 and 2.1, we use context() function
     * in Moodle >= 2.2, we use static context_xxx::instance() method
     *
     * @param integer $contextlevel
     * @param integer $instanceid (optional, default=0)
     * @param int $strictness (optional, default=0 i.e. IGNORE_MISSING)
     * @return required context
     * @todo Finish documenting this function
     */
    static public function context($contextlevel, $instanceid=0, $strictness=0) {
        if (class_exists('context_helper')) {
            // use call_user_func() to prevent syntax error in PHP 5.2.x
            // return $classname::instance($instanceid, $strictness);
            $class = context_helper::get_class_for_level($contextlevel);
            return call_user_func(array($class, 'instance'), $instanceid, $strictness);
        } else {
            return get_context_instance($contextlevel, $instanceid);
        }
    }
}
