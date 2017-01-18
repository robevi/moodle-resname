<?php
global $DB;

require (__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('reports.php');
$systemcontext=context_system::instance();
require_capability('report/coursecompletion:viewreport', $systemcontext);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);

$sort    = optional_param('sort', 'userid', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);
admin_externalpage_setup('reportcoursecompletion', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
$mform=new reportform();
$where = '';
$join = '';
$params = [];
$op = ' AND ';
$sqlsort = $sort;
if ($sort=='userid') {
	$sqlsort = 'cc.userid';
}

if ($data=$mform->get_data()) {
	
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
		}
		if ($data->timecomplete == 2) {
			if (count($params)>0) {
				$where.=$op;
				}
			$where.=' cc.timecompleted is null or cc.timecompleted <= 0 ';
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
$mform->display();
echo $OUTPUT->heading(get_string('reportheader', 'report_coursecompletion'));
$orderby = " ORDER BY $sqlsort $dir";
$sql = 'select cc.id,c.fullname,u.firstname,u.lastname,u.email,cc.timestarted,cc.timecompleted from {course_completions} as cc join {user} as u on u.id=cc.userid join {course} as c on c.id=cc.course'.$join.$where.$orderby;
$currentstart = $page * $perpage;
$countsql = 'select count(cc.id) from {course_completions} as cc join {user} as u on u.id=cc.userid join {course} as c on c.id=cc.course'.$join.$where.$orderby;
$user = $DB->get_records_sql($sql,$params,$currentstart,$perpage);
$baseurl = new moodle_url('index.php', array('sort'=>$sort,'dir'=>$dir,'perpage'=>$perpage));
$changescount = $DB->count_records_sql($countsql, $params);
$totalcount = $DB->count_records('course_completions');
$a = new StdClass;
$a->total = $totalcount;
$a->filter = $changescount;
echo get_string('countstring', 'report_coursecompletion',$a); 
echo $OUTPUT->paging_bar($changescount, $page, $perpage, $baseurl);
$columns = array(
'course' => get_string('course', 'report_coursecompletion'),
'firstname' => get_string('firstname', 'report_coursecompletion'),
'lastname' => get_string('lastname', 'report_coursecompletion'),
'email' => get_string('email', 'report_coursecompletion'),
'timestarted' => get_string('timestarted', 'report_coursecompletion'),
'timecomplete' => get_string('timecomplete', 'report_coursecompletion')
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
	if ($value->timecompleted == 0) {
		$value->timecompleted = get_string('uncompleted', 'report_coursecompletion');
	}
	else {
		$value->timecompleted = userdate($value->timecompleted);
	}
	$table->data [] = $value;
}
echo html_writer::table($table);
echo $OUTPUT->footer();
?>
