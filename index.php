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
 * Performance overview report
 *
 * @package   report_performance
 * @copyright 2013 Rajesh Taneja
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->dirroot.'/report/studentsfollowup/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

require_login();


$report = new report_followup();

$Cohorts = $report->report_followup_get_cohorts();

// Param for the selected cohort
$paramSelectedCohort = optional_param('c', '', PARAM_ALPHANUMEXT);

// Set the selected cohort id
$report->report_followup_set_cohort_id($paramSelectedCohort);


// Param for the selected display mode
$paramSelectedMode = optional_param('m', '', PARAM_ALPHANUMEXT);

// Set the selected cohort id
$report->report_followup_set_mode_id($paramSelectedMode);

// build report
$data = $report->report_followup_report();

// Print the header.
// admin_externalpage_setup('reportstudentsfollowup', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'report_studentsfollowup'));

$PAGE->requires->js_call_amd('report_studentsfollowup/studentsfollowup', 'init');

?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css">

<div class="form-group">
<label for="cohorts"><?php echo get_string('choose_cie', 'report_studentsfollowup'); ?></label>
<select class="form-control yadcf-filter" id="cohorts">
	<option value="all"><?php echo get_string('all_companies', 'report_studentsfollowup'); ?></option>
<?php
foreach ($Cohorts as $key => $cohort) {
	if ($report->cohort_id == $cohort->id) {
		$s = ' selected';
	}
	echo '<option value="'.$cohort->id.'"'.$s.'>'.$cohort->name.' ('.$cohort->cntstudent.' '.get_string('students', 'report_studentsfollowup').')</option>';
	$s = '';
}
?>
</select>
</div>

<!-- <div class="form-group">
	<div class="col-sm-10">
		<div class="checkbox">
			<label>
				<input type="checkbox" class="hide_not_started"> <?php echo get_string('hide_not_started', 'report_studentsfollowup'); ?>
			</label>
		</div>
	</div>
</div> -->


<div class="form-group hidden">
<label for="modes"><?php echo get_string('choose_mode', 'report_studentsfollowup'); ?></label>
<select class="form-control" id="modes">
<?php
foreach ($report->display_mode as $key => $mode) {
	if ($report->mode_id == $key) {
		$s = ' selected';
	}
	echo '<option value="'.$key.'"'.$s.'>'.get_string('choose_mode_'.$key, 'report_studentsfollowup').'</option>';
	$s = '';
}
?>
</select>
</div>
<p>&nbsp;</p>

<!--table table-bordered table-condensed table-hover-->
<div class="wrapperFloatTbl dataTables_wrapper">
<table id="reportTbl">
	<thead class="thead-default">
		<tr>
			<th rowspan="2">Username</th>
			<th rowspan="2">Email</th>
			<th rowspan="2">Organization</th>
			<th colspan="2" class="text-center">Day 1</th>
			<th colspan="2" class="text-center">Day 2</th>
			<th colspan="2" class="text-center">Day 3</th>
			<th colspan="2" class="text-center">Day 4</th>
			<th colspan="2" class="text-center">Day 5</th>
			<th colspan="2" class="text-center">Day 6</th>
			<th colspan="2" class="text-center">Day 7</th>
			<th colspan="2" class="text-center">Day 8</th>
			<th colspan="2" class="text-center">Day 9</th>
			<th colspan="2" class="text-center">Day 10</th>
			<th colspan="2" class="text-center">Final Test</th>
		</tr>
		<tr>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
			<th class="text-center">Completed</th><th class="text-center">Time</th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ($data as $key => $student) {
	if (empty($student['cohort_name'])) {
		$cohort_name = 'n/a';
	} else {
		$cohort_name = $student['cohort_name'];
	}
	echo '<tr>';
	echo '<td>'.$student['username'].'</td>';
	echo '<td>'.$student['email'].'</td>';
	echo '<td>'.$cohort_name.'</td>';
	$i = 0;
	if (isset($student['grades'])) {
		foreach ($student['grades'] as $grade) {
			if ($i%2) {
				$c = ' class="text-center"';
			} else {
				$c = ' class="text-center"'; //report_odd_col
			}
			echo '<td'.$c.'>'.$grade->percent.'%</td>';
			echo '<td'.$c.'>'.$grade->td.'</td>';
			$i++;
		}
	}
	if ($i < 11) {
		echo str_repeat('<td>&nbsp;</td><td>&nbsp;</td>', 11-$i);
	}
	echo '</tr>';
}
?>
	</tbody>
</table>
</div>

<?php
echo $OUTPUT->footer();