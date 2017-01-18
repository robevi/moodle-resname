<?php 
defined('MOODLE_INTERNAL') or die;
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/formslib.php');
class reportform extends moodleform {
	public function definition() {
	$mform = $this->_form;
	$mform->addElement('text', 'firstname', get_string('firstname', 'report_coursecompletion')); 
	$mform->addElement('text', 'lastname', get_string('lastname', 'report_coursecompletion'));
	$mform->addElement('text', 'email', get_string('email', 'report_coursecompletion'));
	$mform->setType('firstname',PARAM_ALPHA);
	$mform->setType('lastname',PARAM_ALPHA);
	$mform->setType('email',PARAM_ALPHA);

	$mform->addElement('advcheckbox', 'userdeleted', get_string('userdeleted', 'report_coursecompletion'));
	$mform->addElement('advcheckbox', 'usersuspended', get_string('usersuspended', 'report_coursecompletion'));

	$mform->addElement('header', 'cohortsection', get_string('cohortsection', 'report_coursecompletion'));
	$cohorts = $this->get_course_cohorts();
	$mform->addElement('select', 'cohorts', get_string('cohorts', 'report_coursecompletion'), $cohorts);

	$mform->addElement('header', 'timecompletedsection', get_string('timecompletedsection', 'report_coursecompletion'));
	$mform->addElement('advcheckbox', 'filterbytimecompleted', get_string('filterbytimecompleted', 'report_coursecompletion'));
	$mform->addElement('date_selector', 'timecompletedafter', get_string('timecompletedafter', 'report_coursecompletion'));
	$mform->addElement('date_selector', 'timecompletedbefore', get_string('timecompletedbefore', 'report_coursecompletion'));
	$mform->addElement('header', 'timestartedsection', get_string('timestartedsection', 'report_coursecompletion'));
	$mform->addElement('advcheckbox', 'filterbytimestarted', get_string('filterbytimestarted', 'report_coursecompletion'));
	$mform->addElement('date_selector', 'timestartedafter', get_string('timestartedafter', 'report_coursecompletion'));
	$mform->addElement('date_selector', 'timestartedbefore', get_string('timestartedbefore', 'report_coursecompletion'));
	$mform->closeHeaderBefore('header');
        
	$mform->addElement('header', 'coursecatssection', get_string('coursecatssection', 'report_coursecompletion'));
	$radioarray=array();
	$radioarray[]=$mform->createElement('radio','operator','',get_string('or', 'report_coursecompletion'),1);
	$radioarray[]=$mform->createElement('static', 'space', '', '<br>');
	$radioarray[]=$mform->createElement('radio','operator','',get_string('and', 'report_coursecompletion'),0);
	$mform->addGroup($radioarray,'radioar',get_string('operatorheader', 'report_coursecompletion'),array(''),false);
	$mform->setDefault('operator',0);
	$categories = $this->get_course_categories();
	$mform->addElement('select', 'coursecats', get_string('coursecats', 'report_coursecompletion'), $categories);
	$mform->setDefault('coursecats', 0);
	
	$mform->addElement('header', 'completionsection', get_string('completionsection', 'report_coursecompletion'));
	$completions = array('0'=>get_string('any', 'report_coursecompletion'), '1' => get_string('complete', 'report_coursecompletion'), '2' => get_string('uncomplete', 'report_coursecompletion'));
	$mform->addElement('select', 'timecomplete', get_string('timecomplete', 'report_coursecompletion'), $completions);
	$mform->closeHeaderBefore(' ');

	$this->add_action_buttons(false,get_string('submit','report_coursecompletion'));
}
private function get_course_categories() {
	global $DB;
	$finalcats = array();
	$allcats = $DB->get_records('course_categories');
	foreach($allcats as $cat) {
		$finalcats[$cat->id]=$cat->name;
	}
	$finalcats[0]=get_string('any_cat','report_coursecompletion');
	return $finalcats;
}

private function get_course_cohorts() {
	global $DB;
	$finalcohorts = array();
	$allcohorts = $DB->get_records('cohort');
	foreach($allcohorts as $cohort) {
		$finalcohorts[$cohort->id]=$cohort->name;
	}
	$finalcohorts[0]=get_string('any_cohort', 'report_coursecompletion');
	return $finalcohorts;
}
}	
?>
