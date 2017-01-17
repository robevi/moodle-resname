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
	$this->add_action_buttons(false,get_string('submit','report_coursecompletion'));
}
}
	
?>
