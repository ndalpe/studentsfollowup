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
 * IOMAD Student Follow-up report
 *
 * @package   report_iomadfollowup
 * @copyright 2018 Bridgeus Kizuna Asia
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->dirroot.'/report/iomadfollowup/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

require_login(0, false);

$systemcontext = context_system::instance();

// Get the user capability
$view_company = has_capability('mod/report_iomadanalytics:view_stats_company', $systemcontext);
$view_country = has_capability('mod/report_iomadanalytics:view_stats_country', $systemcontext);
$view_all     = has_capability('mod/report_iomadanalytics:view_stats_all',     $systemcontext);

$PAGE->set_url('/report/iomadfollowup/index.php');

$PAGE->set_context($systemcontext);

// Set the HTML <title> tag
$PAGE->set_title(get_string('report_page_title', 'report_iomadfollowup'));

// Set the page heading (big title before content)
$PAGE->set_heading(get_string('report_page_title', 'report_iomadfollowup'));


/*************************************************/
/************** Define user's Role ***************/
/*************************************************/
$Capability = new stdClass();

// SuperAdmin
if ($view_all===true) {
	$Capability->view_stats_all = true;
	$Capability->view_stats_country = false;
	$Capability->view_stats_company = false;
	$Capability->adminType = 'SuperAdmin';

// Country Admin
} else if ($view_all===false && $view_country===true) {
	$Capability->view_stats_all = false;
	$Capability->view_stats_country = true;
	$Capability->view_stats_company = false;
	$Capability->adminType = 'CountryAdmin';

// Company Admin
} else if ($view_all===false && $view_country===false && $view_company===true) {
	$Capability->view_stats_all = false;
	$Capability->view_stats_country = false;
	$Capability->view_stats_company = true;
	$Capability->adminType = 'CompanyAdmin';
}

/*********************************************/
/************** URL Param check **************/
/*********************************************/
$paramSelectedCompany = optional_param('c', '', PARAM_INT);
if (empty($paramSelectedCompany)) {
	$paramCompanyId = $USER->company->id;
} else {
	$paramCompanyId = $paramSelectedCompany;
}

/******************************************/
/************** BEGIN REPORT **************/
/******************************************/
$report = new report_iomadfollowup();

// Set the admin company ID
$report->setCompanyId($paramCompanyId);

// Get student's profile info and grades for all quiz
$Students = $report->getStudentsGrades();

// Get a translated, shortened list of course name
$Courses = $report->getCourses();

// Print header.
echo $OUTPUT->header();

// Init JS AMD module
$PAGE->requires->js_call_amd('report_iomadfollowup/iomadfollowup', 'init');

// downloadable file name
$filename = 'followup_report_'.date('y-m-d_h_i_s').'.csv';
?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css">

<!-- Country Menu -->
<?php
if ($Capability->adminType != 'CompanyAdmin') {
	if ($Capability->adminType == 'CountryAdmin') {
		$countries = $report->getCountries($limitToOwnCountry=true);
	} else {
		$countries = $report->getCountries($limitToOwnCountry=false);
	}
?>
<div class="form-group">
	<label for="countries"><?php echo get_string('choose_company', 'report_iomadfollowup'); ?></label>
	<select class="form-control" id="countries">
	<?php
	foreach ($countries as $key => $country) {
		echo '<optgroup label="'.get_string($country->country, 'countries').'">';
		$Companies = $report->getCompaniesInCountry($country->country);
		foreach ($Companies as $companyId => $company) {
			$selected = ($companyId == $paramCompanyId) ? ' selected' : '';
			echo '<option value="'.$companyId.'"'.$selected.'>'.$company->name.'</option>';
		}
		echo '</optgroup>';
	}
	?>
	</select>
</div>
<?php } ?>
<div class="form-group">
<a class="btn btn-primary" href="download/<?php echo $filename; ?>"><?php echo get_string('download_grades', 'report_iomadfollowup'); ?></a>
</div>
<p>&nbsp;</p>

<!--table table-bordered table-condensed table-hover-->
<div class="wrapperFloatTbl dataTables_wrapper">
<?php
// $html will be displayed
// $csv will be downloaded
$html = $csv = '';
$csv_header = $csv_student = array();
$html .= '<table id="reportTbl"><thead class="thead-default"><tr>';

$html .= '<th>'.get_string('col_students_name', 'report_iomadfollowup').'</th>';
$csv_header[] = get_string('col_students_name', 'report_iomadfollowup');

$html .= '<th>'.get_string('col_students_department', 'report_iomadfollowup').'</th>';
$csv_header[] = get_string('col_students_department', 'report_iomadfollowup');

$html .= '<th>'.get_string('col_students_level', 'report_iomadfollowup').'</th>';
$csv_header[] = get_string('col_students_level', 'report_iomadfollowup');

foreach ($Courses as $key => $course) {
	$html .= '<th>'.$course->fullname.'</th>';
	$csv_header[] = $course->fullname;
}

$html .= '</tr></thead><tbody>';

$csv .= implode(',', $csv_header)."\n";

foreach ($Students as $userId => $grades) {
	$i = 0;

	$html .= '<tr><td>'.$grades['profile']['name'].'</td>';
	$csv_student[] = $grades['profile']['name'];

	$html .= '<td>'.$grades['profile']['department'].'</td>';
	$csv_student[] = $grades['profile']['department'];

	$html .= '<td>'.$grades['profile']['level'].'</td>';
	$csv_student[] = $grades['profile']['level'];

	foreach ($grades['grades'] as $grade) {
		// alternate col color
		$colClass = (is_int($i/2) === true) ? 'col1' : 'col2';$i++;
		$html .= '<td class="'.$colClass.'">'.$grade.'</td>';
		$csv_student[] = $grade;
	}
	$html .= "</tr>\n";

	$csv .= implode(',', $csv_student)."\n";
	$csv_student = array();
}
$html .= '</tbody></table>';

// write the html table so it can be downloaded
file_put_contents($CFG->dirroot.'/report/iomadfollowup/download/'.$filename, $csv);

echo $html;
?>
</div>
<?php echo $OUTPUT->footer();