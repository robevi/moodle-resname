<?php
global $DB;

//files
require (__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('reports.php');

//variables
$systemcontext=context_system::instance();
require_capability('report/coursecompletion:viewreport', $systemcontext);

$data = null;

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);

$export = optional_param('export', 0, PARAM_INT);

$sort    = optional_param('sort', 'userid', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);
admin_externalpage_setup('reportcoursecompletion', '', null, '', array('pagelayout'=>'report'));
$mform=new reportform();
$where = '';
$join = '';
$params = [];
$op = ' AND ';
$sqlsort = $sort;
if ($sort=='userid') {
	$sqlsort = 'cc.userid';
}


$data = null;
$data = $mform->get_data();

if(!$data && isset($USER->session) && isset($USER->session['report_coursecompletion'])) {
    $data = $USER->session['report_coursecompletion'];
    $mform->set_data($data);
}
if($data) {
    $USER->session['report_coursecompletion'] = $data;
	if ($data->operator) {
	$op = ' OR ';
	}	
	if ($data->cohorts) {
		if (count($params)>0) {
			$where.=$op;
		}
	$join = ' join {cohort_members} as cm on cm.cohortid = :cohortid and cm.userid = cc.userid ';
	$params ['cohortid'] = $data->cohorts;
	$where.= ' cm.id is not null ';
	}
	if ($data->timecomplete) {
		if ($data->timecomplete == 1) {
			if (count($params)>0) {
			$where.=$op;
			}
			$where.=' cc.timecompleted is not null and cc.timecompleted > 0 ';
			$params ['dummy'] = 'dummy';
		}
		if ($data->timecomplete == 2) {
			if (count($params)>0) {
				$where.=$op;
				}
			$where.=' cc.timecompleted is null or cc.timecompleted <= 0 ';
			$params ['dummy'] = 'dummy';
			}
		}
	if ($data->firstname) {
		if (count($params)>0) {
			$where.=$op;
			}
		$where.=' firstname like :firstname ';
		$params['firstname'] = $data->firstname.'%';
		}
	if ($data->lastname) {
		if (count($params)>0) {
			$where.=$op;
			}
		$where.=' lastname like :lastname ';
		$params['lastname'] = $data->lastname.'%';
		}
	if ($data->email) {
		if (count($params)>0) {
			$where.=$op;
			}
		$where.=' email like :email ';
		$params['email'] = $data->email.'%';
		}
	if ($data->userdeleted) {
		if (count($params)>0) {
			$where.=$op;
			}
		$where.=' u.deleted = 0 ';		
		}
	if ($data->usersuspended) {
		if (count($params)>0) {
			$where.=$op;
			}
		$where.=' u.suspended = 0 ';
		}
	if ($data->filterbytimecompleted) {
		if ($data->timecompletedafter) {
			if (count($params)>0) {
				$where.=$op;
				}		
			$where.=' timecompleted > :timecompletedafter ';
			$params['timecompletedafter'] = $data->timecompletedafter;	
			}	
		if ($data->timecompletedbefore) {
			if (count($params)>0) {
				$where.=$op;
				}
			$where.=' timecompleted > :timecompletedbefore ';
			$params['timecompletedbefore'] = $data->timecompletedbefore;
			}
		}
	if ($data->filterbytimestarted) {
		if ($data->timestartedafter) {
			if (count($params)>0) {
				$where.=$op;
				}
			$where.=' timestarted > :timestartedafter ';
			$params['timestartedafter'] = $data->timestartedafter;
			}
		if($data->timestartedbefore) {
			if (count($params)>0) {
				$where.=$op;
				}
			$where.=' timestarted > :timestartedbefore ';
			$params['timestartedbefore'] = $data->timestartedbefore;
			}	
		}
	if ($data->coursecats) {
		$where.=' category = :category ';
		$params['category'] = $data->coursecats;
		}
		if ($where != '') {
			$where=' where' .$where;
			}
	}
$orderby = " ORDER BY $sqlsort $dir";
$sql = 'select cc.id,c.fullname,u.firstname,u.lastname,u.email,cc.timestarted,cc.timecompleted, cc.userid, cc.course from {course_completions} as cc join {user} as u on u.id=cc.userid join {course} as c on c.id=cc.course'.$join.$where.$orderby;
$currentstart = $page * $perpage;
$countsql = 'select count(cc.id) from {course_completions} as cc join {user} as u on u.id=cc.userid join {course} as c on c.id=cc.course'.$join.$where.$orderby;

//columns of table
$columns = array(
'course' => get_string('course', 'report_coursecompletion'),
'firstname' => get_string('name', 'report_coursecompletion'),
'email' => get_string('email', 'report_coursecompletion'),
'timestarted' => get_string('timestarted', 'report_coursecompletion'),
'timecomplete' => get_string('timecomplete', 'report_coursecompletion')
);

//content of csv
if($export) {
	$records = $DB->get_recordset_sql($sql, $params);
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=data.csv');
	$output = fopen('php://output', 'w');
	fputcsv($output, array_values($columns));
    	foreach($records as $record) {
        	$record->timestarted = userdate($record->timestarted);
        	$record->timecompleted = userdate($record->timecompleted);
        	unset($record->id);
        	unset($record->userid);
        	unset($record->course);
        	fputcsv($output, (array)$record);
    	}
  	$records->close();
  	die;
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->heading(get_string('reportheader', 'report_coursecompletion'));
$user = $DB->get_records_sql($sql,$params,$currentstart,$perpage);
$baseurl = new moodle_url('index.php', array('sort'=>$sort,'dir'=>$dir,'perpage'=>$perpage));
$changescount = $DB->count_records_sql($countsql, $params);
$totalcount = $DB->count_records('course_completions');
$a = new StdClass;
$a->total = $totalcount;
$a->filter = $changescount;
echo get_string('countstring', 'report_coursecompletion',$a); 
echo $OUTPUT->paging_bar($changescount, $page, $perpage, $baseurl);
$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$table->data= array();

//ascending and descending code of columns
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
    $hcolumns[$column] = "<a href=\"index.php?sort=$column&amp;dir=$columndir#table\">".$strcolumn."</a>$columnicon";
}

//table code
$table -> head=$hcolumns;
foreach ($user as $value) {
	$userurl = html_writer::link(new moodle_url('/user/view.php', array('id'=>$value->userid)), $value->firstname.' '.$value->lastname);
	$value->firstname = $userurl;
	$courseurl = html_writer::link(new moodle_url('/course/view.php', array('id'=>$value->course)), $value->fullname);
	$value->fullname = $courseurl;
	unset($value->id);
	unset($value->userid);
	unset($value->course);
	unset($value->lastname);
	if ($value->timestarted == 0) {
		$value->timestarted = get_string('uncompleted', 'report_coursecompletion');
	}
	else {
		$value->timestarted = userdate($value->timestarted);
	}
	if ($value->timecompleted == 0) {
		$value->timecompleted = get_string('uncompleted', 'report_coursecompletion');
	}
	else {
		$value->timecompleted = userdate($value->timecompleted);
	}
	$table->data [] = $value;
}
echo '<a name="table"></a>';
echo html_writer::table($table);

//export button
$buttonurl = new moodle_url("index.php", array("export" => 1, "sort" => $sort, "dir" => $dir));
$buttonstring = get_string('exportbutton', 'report_coursecompletion');
echo $OUTPUT->single_button($buttonurl, $buttonstring);

//footer
echo $OUTPUT->footer();
?>
