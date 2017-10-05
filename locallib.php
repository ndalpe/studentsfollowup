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
 * This file contains classes for report_performance
 *
 * @package   report_performance
 * @copyright 2013 Rajesh Taneja
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This contains functions to get list of student progress by cohort
 *
 * @package   report_followup
 * @copyright 2017 Bridgeus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_followup {

	public $DB;

	// The selected cohort from the choose company <select>
	public $cohort_id;

	// The available display mode
	// 1 - Not started the training
	// 2 - Started but not completed
	// 3 - Completed the training
	public $display_mode;

	// The selected display mode
	public $mode_id;

	// Exclude the test user id from the report
	public $banned_id;

	public function report_followup(){
		// Get database object
		if (!isset($DB)) {
			global $DB;
		}
		$this->DB = $DB;

		// Display all cohort initially
		$this->cohort_id = false;

		// admin and test user id
		$this->banned_id = array(1,2,3,7,8,9,33);

		// set display mode
		$this->display_mode = array(
			'1' => 'not_started',
			'2' => 'not_completed',
			'3' => 'completed'
		);
	}

	// Set the cohort ID
	public function report_followup_set_cohort_id($cohort_id){
		if (empty($cohort_id) && !is_numeric($cohort_id)) {
			$this->cohort_id = false;
		} else {
			$c = $this->report_followup_cohort_exists($cohort_id);
			if ($c) {
				$this->cohort_id = $cohort_id;
			} else {
				$this->cohort_id = false;
			}
		}
	}

	// Set the display mode
	// $mode_id int Display mode id
	// Default mode is 2
	public function report_followup_set_mode_id($mode_id){
		if (array_key_exists($mode_id, $this->display_mode)) {
			$this->mode_id = $mode_id;
		} else {
			$this->mode_id = 2;
		}
	}

	// return a list of active cohort along with the student count per cohort
	public function report_followup_get_cohorts() {
		$Cohorts = $this->DB->get_records_sql(
			'SELECT c.id, c.name, c.idnumber, (select count(*) from mdl_cohort_members as m where c.id = m.cohortid) AS cntStudent FROM mdl_cohort as c WHERE visible = 1 ORDER BY idnumber ASC;', null, $limitfrom=0, $limitnum=0
		);
		return $Cohorts;
	}

	// check if a given cohort exists
	// $cohort_id int The cohort's database ID
	public function report_followup_cohort_exists($cohort_id) {
		// return false if the $cohort_id is not an int or equal zero
		if (!is_numeric($cohort_id) && $cohort_id == 0) {
			return false;
		}
		return $this->DB->record_exists("cohort", array('id'=>$cohort_id));
	}

	// get the list of topics or singleactivity courses
	public function report_followup_get_courses() {
		$courses = $this->DB->get_records_sql("SELECT id, fullname FROM mdl_course WHERE format = 'topics' OR format = 'singleactivity' ORDER BY sortorder", null, $limitfrom=0, $limitnum=0);
		return $courses;
	}

	// Get the list of quiz contained within a course
	public function report_followup_get_quiz($courseid) {
		$quizs = $this->DB->get_records_sql("SELECT id, name, grade FROM mdl_quiz WHERE course = :courseid", array('courseid'=>$courseid), $limitfrom=0, $limitnum=0);
		return $quizs;
	}

	// Generate the follow up report
	public function report_followup_report() {

		$cohorts = $this->report_followup_get_cohorts();

		// if we have a cohort_id, get the student from this cohort only
		// otherwise get student from all cohort
		if ($this->cohort_id !== false) {
			$students = $this->getStudentPerCohort($this->cohort_id);
		} else {
			foreach ($cohorts as $key => $cohort) {
				$a[] = $cohort->id;
			}
			$students = $this->getStudentPerCohort($a);
		}

		// remove unused keys from user array
		// and set the user as the array key
		$students = $this->report_followup_clean_students($students);

		$Courses = $this->report_followup_get_courses();
		foreach ($Courses as $key => $course) {
			$Quiz = $this->report_followup_get_quiz($course->id);
			foreach ($Quiz as $key => $quiz) {
				$grades = $this->report_followup_grades_per_quiz($course->id, $quiz->id, $students);
				foreach ($grades as $key => $grade) {
					if (array_key_exists($grade->userid, $students)) {

						// add cohort name
						$students[$grade->userid]['cohort_name'] = $cohorts[$students[$grade->userid]['cohortid']]->name;

						// add quiz name
						$grade->name = $quiz->name;

						// format grade as %
						$grade->percent = round((intval($grade->sumgrades) / intval($quiz->grade)) * 100);

						// add the quiz grade to the student array
						$students[$grade->userid]['grades'][$grade->quiz] = $grade;
					}
				}
			}
		}
		// var_dump($students);
		// exit();
		return $students;
	}

	public function report_followup_grades_per_quiz($courseid, $quizid, $students){
		//$grading_info = grade_get_grades($courseid, 'mod', 'quiz', $quizid, array_keys($students));

			//FROM_UNIXTIME(timestart, '%Y-%m-%d %H:%i:%s') as ts,
			//FROM_UNIXTIME(timefinish, '%Y-%m-%d %H:%i:%s') as tf,
		$grading_info = $this->DB->get_records_sql(
			"SELECT id, quiz, userid, MAX(attempt) as m, sumgrades,
			SEC_TO_TIME(TIMESTAMPDIFF(second, FROM_UNIXTIME(timestart, '%Y-%m-%d %H:%i:%s'),FROM_UNIXTIME(timefinish, '%Y-%m-%d %H:%i:%s'))) as td
			FROM (SELECT * FROM mdl_quiz_attempts order by userid ASC, attempt DESC) AS t
			where quiz = {$quizid} AND state='finished'
			group by userid
			order by userid;",
			array(), $limitfrom=0, $limitnum=0
		);

		return $grading_info;
	}

	public function getStudentPerCohort($cohortid) {

		// set the where clause for cohort
		if (is_array($cohortid)) {
			foreach ($cohortid as $key => $cohort) {
				$where_parts[] = 'mdl_cohort_members.cohortid='.$cohort;
			}
			$where = implode(' OR ', $where_parts);
		} else {
			$where = 'mdl_cohort_members.cohortid='.$cohortid;
		}

		// exclude the banned test user id
		foreach ($this->banned_id as $id) {
			$ids[] = 'mdl_user.id != '.$id;
		}
		$exclude = implode(' AND ', $ids);

		$Cohorts = $this->DB->get_records_sql(
			'SELECT
				mdl_cohort_members.id, mdl_cohort_members.cohortid, mdl_cohort_members.userid,
				mdl_user.username, mdl_user.email
			FROM mdl_cohort_members
			JOIN mdl_user ON mdl_cohort_members.userid = mdl_user.id
			WHERE ('.$where.') AND ('.$exclude.') AND mdl_user.suspended=0 AND mdl_user.deleted=0;',
			array(), $limitfrom=0, $limitnum=0
		);
		return $Cohorts;
	}

	// remove unused keys from user array
	// and set the user id as the array key
	function report_followup_clean_students($students){
		if (is_array($students)) {
			$a = array();
			foreach ($students as $student) {
				$a[$student->userid] = array(
					'cohortid' => $student->cohortid,
					'userid'   => $student->userid,
					'username' => $student->username,
					'email'    => $student->email
				);
			}
		} else {
			return false;
		}
		return $a;
	}

}
