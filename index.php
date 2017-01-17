<?php
global $DB;

require (__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('reports.php');
$sort    = optional_param('sort', 'userid', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);
admin_externalpage_setup('reportcoursecompletion', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
$mform=new reportform();
$where = '';
$params=[];
if ($data=$mform->get_data()) {
	if ($data->firstname) {
	if (count($params)>0) {
	$where.=' AND ';
	}
	$where.=' firstname like :firstname ';
	$params['firstname'] = $data->firstname.'%';
	}
	if ($data->lastname) {
	if (count($params)>0) {
	$where.=' AND ';
	}
	$where.=' lastname like :lastname ';
	$params['lastname'] = $data->lastname.'%';
	}
	if ($data->email) {
	if (count($params)>0) {
	$where.=' AND ';
	}
	$where.=' email like :email ';
	$params['email'] = $data->email.'%';
	}
	if ($where != '') {
	$where=' where' .$where;
	}
}
$mform->display();
echo $OUTPUT->heading(get_string('reportheader', 'report_coursecompletion'));
$orderby = " ORDER BY $sort $dir";
$sql = 'select cc.id,c.fullname,cc.timestarted,u.firstname,u.lastname,u.email from {course_completions} as cc join {user} as u on u.id=cc.userid join {course} as c on c.id=cc.course'.$where.$orderby;
$user = $DB->get_records_sql($sql,$params);
$columns = array(
'course' => get_string('course', 'report_coursecompletion'),
'timestarted' => get_string('timestarted', 'report_coursecompletion'),
'firstname' => get_string('firstname', 'report_coursecompletion'),
'lastname' => get_string('lastname', 'report_coursecompletion'),
'email' => get_string('email', 'report_coursecompletion')
);
$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$table->data= array();
$hcolumns = array();
if (!isset($columns[$sort])) {
    $sort = 'lastname';
}
foreach ($columns as $column=>$strcolumn) {
    if ($sort != $column) {
        $columnicon = '';
        $columndir = 'ASC';
    } else {
        $columndir = $dir == 'ASC' ? 'DESC':'ASC';
        $columnicon = $dir == 'ASC' ? 'down':'up';
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";
    }
    $hcolumns[$column] = "<a href=\"index.php?sort=$column&amp;dir=$columndir\">".$strcolumn."</a>$columnicon";
}
$table -> head=$hcolumns;
foreach ($user as $value) {
	unset($value->id);
	if ($value->timestarted == 0) {
		$value->timestarted = get_string('uncompleted', 'report_coursecompletion');
	}
	else {
		$value->timestarted = userdate($value->timestarted);
	}
	$table->data [] = $value;
}
echo html_writer::table($table);
echo $OUTPUT->footer();
?>
