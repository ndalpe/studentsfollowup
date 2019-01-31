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
 * This file contains classes for report_iomadfollowup
 *
 * @package   report_iomadfollowup
 * @copyright 2018 Bridgeus Kizuna Aisa
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class report_iomadfollowup {

	/**
	*** Modle Database Query Object
	**/
	public $DB;

	/**
	*** The company ID to get the student from
	**/
	public $companyId;

	/**
	*** The Course ID for the KP Essential course category
	**/
	public $coursesId = array(7,9,10,11,12,13,14,15,16,17,18);

	/**
	*** The Quiz ID for the KP Essential course category
	**/
	public $quizId   = array(2,3,4,5,6,7,8,9,10,11,12);

	public function __construct(){
		if (!isset($DB)) {
			global $DB;
		}

		$this->DB = $DB;
	}

	/**
	 * Get a list of all countries where there are KP company
	 *
	 * str $limitToOwnCountry  : The 2 letter country code to limit the country list by (for Country Admin)
	 * bool $limitToOwnCountry : If false, return all countries (for Super Admin)
	 *
	 * Return list of country
	*/
	public function getCountries($limitToOwnCountry=false) {

		/////////////////////////
		// Super admin
		if ($limitToOwnCountry === false) {
			$Countries = $this->DB->get_records_sql('
				SELECT count(id),country
				FROM mdl_company
				WHERE suspended = :suspended AND parentid != :parentid
				GROUP BY country
				ORDER BY country ASC;',
				array('parentid'=>'0','suspended'=>'0')
			);

		/////////////////////////
		// Country Admin
		} else {

			// Get the company's country code
			$Company = $this->DB->get_record('company', array('id'=>$this->companyId), 'country');

			// Get all the companies in the specified country
			$Countries = $this->DB->get_records_sql('
				SELECT count(id),country
				FROM mdl_company
				WHERE suspended = :suspended AND parentid != :parentid AND country = :country
				GROUP BY country
				ORDER BY country ASC;',
				array('parentid'=>'0','suspended'=>'0', 'country'=>$Company->country)
			);
		}
		return $Countries;
	}

	/**
	 * Set the company ID to get the setudent from
	 *
	 * int $id : The company ID
	*/
	public function setCompanyId($id) {
		$this->companyId = $id;
	}

	/**
	 * Get a list of all countries where there are KP company
	 *
	*/
	public function getCompanies() {
		$Companies = $this->DB->get_records_sql(
			'SELECT * FROM mdl_company WHERE suspended = :suspended AND parentid != :parentid ORDER BY country ASC;', array('parentid'=>'0','suspended'=>'0'), $limitfrom=0, $limitnum=0
		);
		return $Companies;
	}


	/**
	 * Get a list of all companies in a given country
	 *
	 * str $country : The 2 letters country code to retrieve the companies from
	*/
	public function getCompaniesInCountry($country) {
		$Companies = $this->DB->get_records_sql(
			'SELECT id, name, shortname FROM mdl_company WHERE country = :country AND suspended = :suspended AND parentid != :parentid ORDER BY name, country ASC;', array('country'=>$country, 'parentid'=>'0','suspended'=>'0'), $limitfrom=0, $limitnum=0
		);
		return $Companies;
	}

	/**
	 * Get a list of all courses specified in $this->courseId property
	 *
	*/
	public function getCourses()
	{
		$coursesId = implode(',', $this->coursesId);
		$Courses = $this->DB->get_records_sql("SELECT id, fullname FROM mdl_course WHERE id IN(".$coursesId.") ORDER BY sortorder", null, $limitfrom=0, $limitnum=0);

		// format course title to current language
		// and remove the " - course content" part of the fullname
		foreach ($Courses as $courseId => $course) {
			$tcf = format_string($course->fullname, true, 1);
			$a = explode('-', $tcf);
			if ($course->id == '18') {
				$Courses[$courseId]->fullname = $a[1];
			} else {
				$Courses[$courseId]->fullname = $a[0];
			}
		}
		return $Courses;
	}

	/**
	 * Get a list of all quiz in a given course
	 *
	 * int $courseId : The course id to retrieve the quiz from
	*/
	public function getQuizInCourse($courseId) {
		$Quiz = $this->DB->get_record('quiz', array('course'=>$courseId));
		return $Quiz;
	}

	public function getStudentsGrades() {

		// Static course id array
		$Courses = $this->coursesId;

		// Get the students from the current company
		$Users = $this->DB->get_records_sql("
			SELECT mdl_user.id, mdl_user.firstname, mdl_user.lastname, mdl_company_users.companyid
			FROM mdl_company_users
			INNER JOIN mdl_user ON mdl_company_users.userid = mdl_user.id
			WHERE companyid={$this->companyId}
			ORDER BY mdl_user.firstname, mdl_user.lastname;"
		);

		///////////////////////////
		// Add grades information
		foreach ($Courses as $key => $courseId) {

			// Get quiz ID
			$Quiz = $this->getQuizInCourse($courseId);

			// Get students grades for the current course/quiz
			$grades = grade_get_grades($courseId, 'mod', 'quiz', $Quiz->id, array_keys($Users));
			foreach ($grades->items[0]->grades as $userId => $userGrade) {

				// remove the annoying number of question ie. from "50 % (1)" to "50 %"
				if ($userGrade->str_grade != '-') {
					$a = explode('%', $userGrade->str_grade);
					$userGrade->str_grade = $a[0].' %';
				}
				$grid[$userId]['grades'][$Quiz->id] = $userGrade->str_grade;
			}
		}

		////////////////////////////////////
		// Add student profile information
		foreach ($grid as $userId => $grades) {

			// Add first + last name
			$grid[$userId]['profile']['name'] = $Users[$userId]->firstname.' '.$Users[$userId]->lastname;

			// Get department
			$grid[$userId]['profile']['department'] = $this->DB->get_field(
				'user_info_data', 'data', array('userid'=>$userId, 'fieldid'=>'7')
			);

			// Get level
			$grid[$userId]['profile']['level'] = $this->DB->get_field(
				'user_info_data', 'data', array('userid'=>$userId, 'fieldid'=>'12')
			);
		}

		return $grid;
	}
}
