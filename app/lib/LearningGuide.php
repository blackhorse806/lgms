<?php

class Learning_Guide {

	private $lg_id = null;
	private $db = null;
	private $attribute = [];

	public function __construct($lg_id = null, $db = null) {
		$this->lg_id = $lg_id;
		$this->db = $db;
		if ($this->lg_id != null) {
			$this->create_attributes();
		}
	}

	public function create_attributes() {
		$this->attribute["core"] = new LG_Attribute_Core("lg", $this->lg_id, $this, $this->db);
		$this->attribute["feedback"] = new LG_Attribute_Feedback("lg_feedback", $this->lg_id, $this, $this->db);
		$this->attribute["outcomes"] = new LG_Attribute_Outcomes("lg_outcomes", $this->lg_id, $this, $this->db);
		$this->attribute["staff"] = new LG_Attribute_Staff("lg_staff", $this->lg_id, $this, $this->db);
		$this->attribute["modes_of_delivery"] = new LG_Attribute_ModesOfDelivery("lg_delivery", $this->lg_id, $this, $this->db);
		$this->attribute["approaches"] = new LG_Attribute_Approaches("lg_delivery", $this->lg_id, $this, $this->db);
		$this->attribute["readings"] = new LG_Attribute_Readings("lg_readings", $this->lg_id, $this, $this->db);
		$this->attribute["assessments"] = new LG_Attribute_Assessments("lg_assessments", $this->lg_id, $this, $this->db);
		$this->attribute["assessment_summary"] = new LG_Attribute_AssessmentSummary("lg_assessments", $this->lg_id, $this, $this->db);
		$this->attribute["marking_criteria"] = new LG_Attribute_MarkingCriteria("lg_marking_criteria", $this->lg_id, $this, $this->db);
		$this->attribute["courses"] = new LG_Attribute_UnitCourse("v_clo_table_course", $this->lg_id, $this, $this->db);
		$this->attribute["clos"] = new LG_Attribute_UnitClos("lg_clo_table", $this->lg_id, $this, $this->db);
		$this->attribute["session_weeks"] = new LG_Attribute_SessionWeeks("session_dates", $this->lg_id, $this, $this->db);
		$this->attribute["activity_data"] = new LG_Attribute_ActivityData("function-defined", $this->lg_id, $this, $this->db);
		$this->attribute["activity_columns"] = new LG_Attribute_ActivityColumns("lg_activity_col", $this->lg_id, $this, $this->db);
		$this->attribute["activity_assessments"] = new LG_Attribute_ActivityAssessments("function-defined", $this->lg_id, $this, $this->db);
		$this->attribute["checklist"] = new LG_Attribute_Checklist("lg_checklist", $this->lg_id, $this, $this->db);
		$this->attribute["all_courses"] = new LG_Attribute_AllCourses("course", $this->lg_id, $this, $this->db);
	}

	public function attribute($name) {
		return $this->attribute[$name];
	}

	public function get_lg_id() {
		return $this->lg_id;
	}

	public function get_lg_details() {
		$details = $this->attribute['core']->get();
		return $details;
	}

	public function create($unit_code) {
		$stmt = $this->db->prepare("INSERT INTO lg(unit_code) VALUES(?)");
		$stmt->bindParam(1, $unit_code);
		$stmt->execute();
		if ($stmt === false) {
			echo "FAILED: Could not create new learning guide";
			return false;
		}
		$this->lg_id = $this->db->lastInsertId();
		$this->create_attributes();
		return true;
	}

	public function delete() {
		echo "<br>feedback: ";
		$this->attribute('feedback')->delete_all();
		echo "<br>outcomes: ";
		$this->attribute('outcomes')->delete_all();
		echo "<br>staff: ";
		$this->attribute('staff')->delete_all();
		echo "<br>modes_of_delivery: ";
		$this->attribute('modes_of_delivery')->delete_all();
		echo "<br>approaches: ";
		$this->attribute('approaches')->delete_all();
		echo "<br>readings: ";
		$this->attribute('readings')->delete_all();
		echo "<br>activity_columns: ";
		$this->attribute('activity_columns')->delete_all();
		echo "<br>assessments: ";
		$this->attribute('assessments')->delete_all();
		echo "<br>clos: ";
		$this->attribute('clos')->delete_all();
		echo "<br>checklist: ";
		$this->attribute('checklist')->delete_all();
		echo "<br>core: ";
		$this->attribute('core')->delete_all();
	}

	public function generate() {
		// Check if it needs to be generated again
		$core = $this->attribute('core')->get();
		$regenerate = $core['regenerate'];
		if ($regenerate === '1') {
			// Generate
			$doc = new LearningGuideGenerate($this);
			// Reset regenerate flag
			$this->attribute('core')->save_attribute('regenerate', '0');
		}
	}

	// Copy from another learning guide in the LGMS database, keep approved data
	public function merge_copy($lg_id) {
		$lg = new Learning_Guide($lg_id, $this->db);



	}

	// Import from an old learning guide in lg database
	public function merge_copy_old() {

		// Connect to databse
		// Yes I know this is in a bad place and doesn't use PDO
		$servername = "localhost";
		$username = "root";
		$password = "";
		$dbname = "lg";
		$conn = new mysqli($servername, $username, $password, $dbname);
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		mysqli_set_charset($conn, "utf8");

		//
		// Get data from old database
		//

		// Don't need readings, outcomes, content, or checklist table data

		// Use unit code to get data
		$core = $this->attribute('core')->get();
		$unit_code = $core['unit_code'];

		// Core
		$old_core = null;
		$sql = "SELECT * FROM unit WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$old_core = $result->fetch_assoc();
		}

		// Feedback
		$old_feedback = [];
		$sql = "SELECT * FROM unit_feedback WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_feedback[] = $row;
			}
		}

		// Staff
		$old_staff = [];
		$sql = "SELECT * FROM unit_staff WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_staff[] = $row;
			}
		}

		// Delivery
		$old_delivery = [];
		$sql = "SELECT * FROM unit_delivery WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_delivery[] = $row;
			}
		}

		// Assessments
		$old_assessments = [];
		$sql = "SELECT * FROM unit_assessments WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_assessments[] = $row;
			}
		}

		// Marking criteria
		if ($old_assessments != null) {
			foreach ($old_assessments as $key => $value) {
				$marking_criteria = [];
				$sql = "SELECT * FROM unit_marking_criteria WHERE ass_id = " . $value['id'];
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
					while($row = $result->fetch_assoc()) {
						$marking_criteria[] = $row;
					}
				}
				$old_assessments[$key]['table'] = $marking_criteria;
			}
		}

		// CLO tables
		$old_clo_table = [];
		$sql = "SELECT * FROM unit_clo_table WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_clo_table[] = $row;
			}
		}

		// CLO to ULO
		if ($old_clo_table != null) {
			foreach ($old_clo_table as $key => $value) {
				$contribution = [];
				$sql = "SELECT * FROM unit_clo_ulo WHERE clo_table_id = " . $value['id'];
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
					while($row = $result->fetch_assoc()) {
						$contribution[] = $row;
					}
				}
				$old_clo_table[$key]['contributions'] = $contribution;
			}
		}

		//
		// Format data for new database
		//

		// Feedback
		$formatted_feedback = [];
		if ($old_feedback != null) {
			foreach ($old_feedback as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['feedback_item'] = $value['feedback_item'];
				$formatted_feedback[] = $item;
			}
		}

		// Staff
		$formatted_staff = [];
		if ($old_staff != null) {
			foreach ($old_staff as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['name'] = $value['staff_name'];
				$item['type'] = $value['staff_type'];
				$item['location'] = $value['location'];
				$item['phone'] = $value['phone'];
				$item['email'] = $value['email'];
				$item['consultation'] = $value['consultation'];
				$formatted_staff[] = $item;
			}
		}

		// Delivery
		$formatted_delivery = [];
		if ($old_delivery != null) {
			foreach ($old_delivery as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['hours'] = $value['hours'];
				$item['mode'] = $value['mode'];
				$item['approach'] = $value['learning_approach'];
				$formatted_delivery[] = $item;
			}
		}

		// Assessments
		$formatted_assessments = [];
		if ($old_assessments != null) {
			foreach ($old_assessments as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['name'] = $value['assessment_name'];
				$item['weight'] = $value['weight'];
				$item['ulos'] = $value['ulos_assessed'];
				$item['length'] = $value['length'];
				$item['mode'] = $value['mode'];
				$item['due_date'] = $value['due_date'];
				$item['submission'] = $value['submission_type'];
				$item['collaboration'] = $value['collaboration_type'];
				$item['instructions'] = $value['instructions'];
				$item['format'] = $value['format'];
				$item['resources'] = $value['resources'];
				$item['exemplar'] = $value['include_examplar'];
				$item['exemplar_text'] = $value['exemplar'];
				$item['threshold'] = $value['threshold'];
				$item['threshold_text'] = $value['threshold_detail'];
				$item['marking_criteria_type'] = $value['marking_criteria_type'];
				$item['marking_criteria_plain'] = $value['marking_criteria_plain'];
				$item['marking_criteria_rich'] = $value['marking_criteria_rich'];
				// Marking criteria
				$marking_criteria = [];
				if ($value['table'] != null) {
					foreach ($value['table'] as $key => $value) {
						$row = [];
						$row['id'] = '';
						$row['ass_id'] = '';
						$row['criteria'] = $value['criteria'];
						$row['hd'] = $value['HD'];
						$row['d'] = $value['D'];
						$row['c'] = $value['C'];
						$row['p'] = $value['P'];
						$row['f'] = $value['F'];
						$marking_criteria[] = $row;
					}
				}
				$item['table'] = $marking_criteria;
				$formatted_assessments[] = $item;
			}
		}

		// CLO Tables
		$formatted_clo_table = [];
		if ($old_clo_table != null) {
			foreach ($old_clo_table as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['course_code'] = $value['course_code'];
				$item['intro'] = $value['intro'];
				// Contributions
				$contributions = [];
				if ($value['contributions'] != null) {
					foreach ($value['contributions'] as $key => $value) {
						$row = [];
						$row['id'] = '';
						$row['clo_num'] = $value['clo_num'];
						$row['ulo_num'] = $value['ulo_num'];
						$row['contribution'] = $value['contribution'];
						$contributions[] = $row;
					}
				}
				$item['contributions'] = $contributions;
				$formatted_clo_table[] = $item;
			}
		}



		//
		// Merge data with current approved data
		//

		// Feedback - Can be deleted and replaced
		$this->attribute('feedback')->delete_all();

		// Staff - Can be deleted and replaced
		$this->attribute('staff')->delete_all();

		// Delivery - set approach based on mode and hours matching
		$existing_approaches = $this->attribute('approaches')->get();
		if ($existing_approaches != null) {
			foreach ($existing_approaches as $key => $existing) {
				foreach ($formatted_delivery as $key => $old) {
					if (strtolower($existing['mode']) == strtolower($old['mode']) && $existing['hours'] == $old['hours']) {
						$existing_approaches[$key]['approach'] = $old['approach'];
					}
				}
			}
		}

		// Assessments - set approach based on name matching
		$existing_assessments = $this->attribute('assessments')->get();
		if ($existing_assessments != null) {
			foreach ($existing_assessments as $key => $existing) {
				foreach ($formatted_assessments as $key => $old) {
					if (strtolower($existing['name']) == strtolower($old['name'])) {
						$existing_assessments[$key]['mode'] = $old['mode'];
						$existing_assessments[$key]['due_date'] = $old['due_date'];
						$existing_assessments[$key]['submission'] = $old['submission'];
						$existing_assessments[$key]['instructions'] = $old['instructions'];
						$existing_assessments[$key]['format'] = $old['format'];
						$existing_assessments[$key]['resources'] = $old['resources'];
						$existing_assessments[$key]['exemplar'] = $old['exemplar'];
						$existing_assessments[$key]['exemplar_text'] = $old['exemplar_text'];
						$existing_assessments[$key]['threshold_text'] = $old['threshold_text'];
						$existing_assessments[$key]['marking_criteria_type'] = $old['marking_criteria_type'];
						$existing_assessments[$key]['marking_criteria_plain'] = $old['marking_criteria_plain'];
						$existing_assessments[$key]['marking_criteria_rich'] = $old['marking_criteria_rich'];
						$existing_assessments[$key]['table'] = $old['table'];
					}
				}
			}
		}
		// echo "<plaintext>";
		// var_dump($existing_assessments);

		// CLO Tables - Can be deleted and replaced
		$this->attribute('clos')->delete_all();


		//
		// Store modified attributes
		//
		$this->attribute('feedback')->save($formatted_feedback);
		$this->attribute('staff')->save($formatted_staff);
		$this->attribute('approaches')->save($existing_approaches);
		$this->attribute('assessments')->save($existing_assessments);
		$this->attribute('marking_criteria')->save($existing_assessments);
		// Add each table to get the new id
		foreach ($formatted_clo_table as $key => $value) {
			$this->attribute('clos')->add_clo_table($value);
		}
		// Get the new ids
		$new_clos = $this->attribute('clos')->get();
		// Update the ids
		if ($new_clos != null) {
			foreach ($new_clos as $key => $value) {
				$formatted_clo_table[$key]['id'] = $value['id'];
				$formatted_clo_table[$key]['course_code'] = $value['course_code'];
				$formatted_clo_table[$key]['course_intro'] = $value['course_intro'];
				if ($formatted_clo_table[$key]['contributions'] != null) {
					foreach ($formatted_clo_table[$key]['contributions'] as $k => $v) {
						$formatted_clo_table[$key]['contributions'][$k]['clo_table_id'] = $formatted_clo_table[$key]['id'];
					}
				}
			}
			// Insert contributions
			$this->attribute('clos')->save($formatted_clo_table);
		}
		// Update all core values
		$this->attribute('core')->save_attribute('learning_outcomes_intro', $old_core['learning_outcomes_intro']);
		$this->attribute('core')->save_attribute('approach_to_learning', $old_core['approach_to_learning']);
		$this->attribute('core')->save_attribute('pass_criteria', $old_core['pass_criteria']);
		$this->attribute('core')->save_attribute('assessment_feedback', $old_core['assessment_feedback']);
		$this->attribute('core')->save_attribute('reviewer', $old_core['reviewer']);
		// Set learning guide as merged so it cannot be done again
		$this->attribute('core')->save_attribute('lg_merged', "1");
		// Mark it for regeneration
		$this->attribute('core')->save_attribute('regenerate', "1");
	}

	// Import LG data from a previous LG
	public function import_from_old() {

		// Connect to the old LG databse
		// Yes I know this is in a bad place and doesn't use PDO
		$servername = "localhost";
		$username = "root";
		$password = "";
		$dbname = "lgms_old";
		$conn = new mysqli($servername, $username, $password, $dbname);
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		mysqli_set_charset($conn, "utf8");

		// Don't need readings, outcomes, content, or checklist table data

		// Use unit code to get data
		$core = $this->attribute('core')->get();
		$unit_code = $core['unit_code'];

		// Core
		$old_core = null;
		$sql = "SELECT * FROM lg WHERE unit_code = " . $unit_code;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$old_core = $result->fetch_assoc();
			$old_lg_id = $old_core['lg_id'];
		}

		// Feedback
		$old_feedback = [];
		$sql = "SELECT * FROM lg_feedback WHERE lg_id = " . $old_lg_id;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_feedback[] = $row;
			}
		}

		// Staff
		$old_staff = [];
		$sql = "SELECT * FROM lg_staff WHERE lg_id = " . $old_lg_id;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_staff[] = $row;
			}
		}

		// Delivery
		$old_delivery = [];
		$sql = "SELECT * FROM lg_delivery WHERE lg_id = " . $old_lg_id;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_delivery[] = $row;
			}
		}

		// Assessments
		$old_assessments = [];
		$sql = "SELECT * FROM lg_assessments WHERE lg_id = " . $old_lg_id;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_assessments[] = $row;
			}
		}

		// Marking criteria
		if ($old_assessments != null) {
			foreach ($old_assessments as $key => $value) {
				$marking_criteria = [];
				$sql = "SELECT * FROM lg_marking_criteria WHERE ass_id = " . $value['id'];
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
					while($row = $result->fetch_assoc()) {
						$marking_criteria[] = $row;
					}
				}
				$old_assessments[$key]['table'] = $marking_criteria;
			}
		}

		// CLO tables
		$old_clo_table = [];
		$sql = "SELECT * FROM lg_clo_table WHERE lg_id = " . $old_lg_id;
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$old_clo_table[] = $row;
			}
		}

		// CLO to ULO
		if ($old_clo_table != null) {
			foreach ($old_clo_table as $key => $value) {
				$contribution = [];
				$sql = "SELECT * FROM lg_clo_ulo WHERE clo_table_id = " . $value['id'];
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
					while($row = $result->fetch_assoc()) {
						$contribution[] = $row;
					}
				}
				$old_clo_table[$key]['contributions'] = $contribution;
			}
		}

		//
		// Format data for new database
		//

		// Feedback
		$formatted_feedback = [];
		if ($old_feedback != null) {
			foreach ($old_feedback as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['feedback_item'] = $value['feedback_item'];
				$formatted_feedback[] = $item;
			}
		}

		// Staff
		$formatted_staff = [];
		if ($old_staff != null) {
			foreach ($old_staff as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['name'] = $value['name'];
				$item['type'] = $value['type'];
				$item['location'] = $value['location'];
				$item['phone'] = $value['phone'];
				$item['email'] = $value['email'];
				$item['consultation'] = $value['consultation'];
				$formatted_staff[] = $item;
			}
		}

		// Delivery
		$formatted_delivery = [];
		if ($old_delivery != null) {
			foreach ($old_delivery as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['hours'] = $value['hours'];
				$item['mode'] = $value['mode'];
				$item['approach'] = $value['approach'];
				$formatted_delivery[] = $item;
			}
		}

		// Assessments
		$formatted_assessments = [];
		if ($old_assessments != null) {
			foreach ($old_assessments as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['name'] = $value['name'];
				$item['weight'] = $value['weight'];
				$item['ulos'] = $value['ulos'];
				$item['length'] = $value['length'];
				$item['mode'] = $value['mode'];
				$item['due_date'] = $value['due_date'];
				$item['submission'] = $value['submission'];
				$item['collaboration'] = $value['collaboration'];
				$item['instructions'] = $value['instructions'];
				$item['format'] = $value['format'];
				$item['resources'] = $value['resources'];
				$item['exemplar'] = $value['exemplar'];
				$item['exemplar_text'] = $value['exemplar_text'];
				$item['threshold'] = $value['threshold'];
				$item['threshold_text'] = $value['threshold_text'];
				$item['marking_criteria_type'] = $value['marking_criteria_type'];
				$item['marking_criteria_plain'] = $value['marking_criteria_plain'];
				$item['marking_criteria_rich'] = $value['marking_criteria_rich'];
				// Marking criteria
				$marking_criteria = [];
				if ($value['table'] != null) {
					foreach ($value['table'] as $key => $value) {
						$row = [];
						$row['id'] = '';
						$row['ass_id'] = '';
						$row['criteria'] = $value['criteria'];
						$row['hd'] = $value['hd'];
						$row['d'] = $value['d'];
						$row['c'] = $value['c'];
						$row['p'] = $value['p'];
						$row['f'] = $value['f'];
						$marking_criteria[] = $row;
					}
				}
				$item['table'] = $marking_criteria;
				$formatted_assessments[] = $item;
			}
		}

		// CLO Tables
		$formatted_clo_table = [];
		if ($old_clo_table != null) {
			foreach ($old_clo_table as $key => $value) {
				$item = [];
				$item['id'] = '';
				$item['course_code'] = $value['course_code'];
				$item['intro'] = $value['intro'];
				// Contributions
				$contributions = [];
				if ($value['contributions'] != null) {
					foreach ($value['contributions'] as $key => $value) {
						$row = [];
						$row['id'] = '';
						$row['clo_num'] = $value['clo_num'];
						$row['ulo_num'] = $value['ulo_num'];
						$row['contribution'] = $value['contribution'];
						$contributions[] = $row;
					}
				}
				$item['contributions'] = $contributions;
				$formatted_clo_table[] = $item;
			}
		}



		//
		// Merge data with current approved data
		//

		// Feedback - Can be deleted and replaced
		$this->attribute('feedback')->delete_all();

		// Staff - Can be deleted and replaced
		$this->attribute('staff')->delete_all();

		// Delivery - set approach based on mode and hours matching
		$existing_approaches = $this->attribute('approaches')->get();
		if ($existing_approaches != null) {
			foreach ($existing_approaches as $key => $existing) {
				foreach ($formatted_delivery as $key => $old) {
					if (strtolower($existing['mode']) == strtolower($old['mode']) && $existing['hours'] == $old['hours']) {
						$existing_approaches[$key]['approach'] = $old['approach'];
					}
				}
			}
		}

		// Assessments - set approach based on name matching
		$existing_assessments = $this->attribute('assessments')->get();
		if ($existing_assessments != null) {
			foreach ($existing_assessments as $key => $existing) {
				foreach ($formatted_assessments as $key => $old) {
					if (strtolower($existing['name']) == strtolower($old['name'])) {
						$existing_assessments[$key]['mode'] = $old['mode'];
						$existing_assessments[$key]['due_date'] = $old['due_date'];
						$existing_assessments[$key]['submission'] = $old['submission'];
						$existing_assessments[$key]['instructions'] = $old['instructions'];
						$existing_assessments[$key]['format'] = $old['format'];
						$existing_assessments[$key]['resources'] = $old['resources'];
						$existing_assessments[$key]['exemplar'] = $old['exemplar'];
						$existing_assessments[$key]['exemplar_text'] = $old['exemplar_text'];
						$existing_assessments[$key]['threshold_text'] = $old['threshold_text'];
						$existing_assessments[$key]['marking_criteria_type'] = $old['marking_criteria_type'];
						$existing_assessments[$key]['marking_criteria_plain'] = $old['marking_criteria_plain'];
						$existing_assessments[$key]['marking_criteria_rich'] = $old['marking_criteria_rich'];
						$existing_assessments[$key]['table'] = $old['table'];
					}
				}
			}
		}
		// echo "<plaintext>";
		// var_dump($existing_assessments);

		// CLO Tables - Can be deleted and replaced
		$this->attribute('clos')->delete_all();


		//
		// Store modified attributes
		//
		$this->attribute('feedback')->save($formatted_feedback);
		$this->attribute('staff')->save($formatted_staff);
		$this->attribute('approaches')->save($existing_approaches);
		$this->attribute('assessments')->save($existing_assessments);
		$this->attribute('marking_criteria')->save($existing_assessments);
		// Add each table to get the new id
		// echo "<pre>";
		// print_r($formatted_clo_table);
		// echo "</pre>";
		foreach ($formatted_clo_table as $key => $value) {
			$this->attribute('clos')->add_clo_table($value);
		}
		//var_dump($formatted_clo_table);
		// Get the new ids
		$new_clos = $this->attribute('clos')->get();
		// echo "<pre>";
		// print_r($new_clos);
		// echo "</pre>";
		// Update the ids
		if ($new_clos != null) {
			foreach ($new_clos as $key => $value) {
				$formatted_clo_table[$key]['id'] = $value['id'];
				//echo "Parent ID: " . $value['id'];
				$formatted_clo_table[$key]['course_code'] = $value['course_code'];
				$formatted_clo_table[$key]['course_intro'] = $value['course_intro'];

				//print_r($formatted_clo_table[0]['contributions']);
				if ($formatted_clo_table[$key]['contributions'] != null) {
					foreach ($formatted_clo_table[$key]['contributions'] as $k => $v) {
						$formatted_clo_table[$key]['contributions'][$k]['clo_table_id'] = $formatted_clo_table[$key]['id'];
						//echo "Firs ID: " . $formatted_clo_table[$key]['contributions'][$k]['clo_table_id'];
						//echo "Second ID: " . $formatted_clo_table[$key]['id'];
					}
				}
			}
			// echo "<pre>";
			// print_r($formatted_clo_table);
			// echo "</pre>";
			// Insert contributions
			$this->attribute('clos')->save($formatted_clo_table);
		}
		// Update all core values
		$this->attribute('core')->save_attribute('learning_outcomes_intro', $old_core['learning_outcomes_intro']);
		$this->attribute('core')->save_attribute('approach_to_learning', $old_core['approach_to_learning']);
		$this->attribute('core')->save_attribute('pass_criteria', $old_core['pass_criteria']);
		$this->attribute('core')->save_attribute('assessment_feedback', $old_core['assessment_feedback']);
		$this->attribute('core')->save_attribute('reviewer', $old_core['reviewer']);
		// Set learning guide as merged so it cannot be done again
		$this->attribute('core')->save_attribute('lg_merged', "1");
		// Mark it for regeneration
		$this->attribute('core')->save_attribute('regenerate', "1");
	}
}



class Attribute {
	protected $lg_id = null;
	protected $db = null;
	protected $data = null;
	protected $table = null;
	protected $lg = null;

	public function __construct($table = null, $lg_id = null, $lg = null, $db = null) {
		$this->table = $table;
		$this->lg_id = $lg_id;
		$this->lg = $lg;
		$this->db = $db;
	}

	public function has_data() {
        if ($this->data == null) {
            return false;
        }
        return true;
    }

	public function clear_data() {
        $this->data = null;
    }

	public function set_lg_id($lg_id) {
        $this->lg_id = $lg_id;
    }

	public function save($data) {}

	public function save_self() {
        $this->save($this->data);
    }

	public function remove_ids() {
        if ($this->data == null) {
            return;
        }
        // Check if data is an associative array
        if (isset($this->data['id'])) {
            $this->data['id'] = null;
        }
        if (isset($this->data['unit_id'])) {
            $this->data['unit_id'] = null;
        }
        // Check of data is an array of associative arrays
        if (isset($this->data[0])) {
            foreach ($this->data as $key => $value) {
                if (isset($value['id'])) {
                    $this->data[$key]['id'] = null;
                }
                if (isset($value['unit_id'])) {
                    $this->data[$key]['unit_id'] = null;
                }
            }
        }
    }

	public function delete($delete) {
		if ($delete == null) {
			return true;
		}
		// Delete from table ids in array $delete
		if ($delete != null) {
			foreach ($delete as $d) {
				$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE lg_id=? AND id=?");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $d);
				if ($stmt->execute() === false) {
					return false;
				}
			}
		}
		return true;
	}

	public function delete_all() {
        $stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE lg_id=?");
        $stmt->bindParam(1, $this->lg_id);
        if ($stmt->execute() === false) {
            echo "Failed Delete\n";
            return false;
        }
		return true;
	}

	public function get() {
		if ($this->data == null) {
			$this->retrieve();
			if ($this->data == null) {
				//echo "Could not get any data.";
				return null;
			} else {
				return $this->data;
			}
		} else {
			return $this->data;
		}
	}

	public function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "lg_id = " . $this->lg_id);
	}

	protected function retrieve_data($attr, $table, $filter) {
		// Return value
		$data = null;
		// Query database
		$stmt = $this->db->query("SELECT " . $attr . " FROM " . $table . " WHERE " . $filter);
		if ($stmt != false) {
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		return $data;
	}

	// private function retrieve_array($table, $attr, $filter) {
	// 	// Return value
	// 	$data = null;
	// 	// Prepare attributes for query
	// 	$attributes = implode(",", $attr);
	// 	// Query database
	// 	$sql = "SELECT " . $attributes . " FROM " . $table . " WHERE " . $filter;
	// 	$result = $this->conn->query($sql);
	// 	if ($result->num_rows > 0) {
	// 		// Put results in array
	// 		$data = array();
	// 		while($row = $result->fetch_assoc()) {
	// 			$r = array();
	// 			foreach ($attr as $a) {
	// 				array_push($r, $row[$a]);
	// 			}
	// 			array_push($data, $r);
	// 		}
	// 	}
	// 	return $data;
	// }

}


class LG_Attribute_Core extends Attribute {

	public function retrieve() {
		$temp = $this->retrieve_data("*", $this->table, "lg_id = " . $this->lg_id);
		if (count($temp) > 0) {
			$this->data = $temp[0];
		}
	}

	public function save_attribute($attribute, $value) {
		if ($attribute == null) {
			return true;
		}
		if ($value == "") {
			$value = null;
		}
        if ($attribute == "lg_id") {
            return true;
        }
		$stmt = $this->db->prepare("UPDATE " . $this->table . " SET " . $attribute . "=? WHERE lg_id=?");
		$stmt->bindParam(1, $value);
		$stmt->bindParam(2, $this->lg_id);

		if ($stmt->execute() === false) {
			echo "FAILED: Core attribute - " . $attribute;
			return false;
		}
		return true;
	}

    public function save_self() {
        if ($this->has_data() == false) {
            return;
        }
        foreach ($this->data as $key => $value) {
            $this->save_attribute($key, $value);
        }
    }
}

class LG_Attribute_Outcomes extends Attribute {
    public function save($data) {
		if ($data == null) {
			return true;
		}
		// Add all feedback to database from data
		foreach ($data as $item) {
			$stmt = null;
			if ($item['id'] == null || $item['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (lg_id, outcome, number) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $item['outcome']);
                $stmt->bindParam(3, $item['number']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET outcome=?, number=? WHERE id=?");
				$stmt->bindParam(1, $item['outcome']);
                $stmt->bindParam(2, $item['number']);
				$stmt->bindParam(3, $item['id']);
			}
			if ($stmt->execute() === false) {
                echo "FAILED";
				return false;
			}
		}
		return true;
	}
}

class LG_Attribute_Feedback extends Attribute {
	public function save($data) {
		if ($data == null) {
			return true;
		}
		// Add all feedback to database from data
		foreach ($data as $f) {
			$stmt = null;
			if ($f['id'] == null || $f['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (lg_id, feedback_item) VALUES (?, ?)");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $f['feedback_item']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET lg_id=?, feedback_item=? WHERE id=?");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $f['feedback_item']);
				$stmt->bindParam(3, $f['id']);
			}
			if ($stmt->execute() === false) {
				echo "Failed to save feedback";
				return false;
			}
		}
		return true;
	}
}

class LG_Attribute_Staff extends Attribute {
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $staff) {
			$stmt = null;
			if ($staff['id'] == null || $staff['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (lg_id, name, location, phone, email, type, consultation) VALUES (?, ?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $staff['name']);
				$stmt->bindParam(3, $staff['location']);
				$stmt->bindParam(4, $staff['phone']);
				$stmt->bindParam(5, $staff['email']);
				$stmt->bindParam(6, $staff['type']);
				$stmt->bindParam(7, $staff['consultation']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET name=?, location=?, phone=?, email=?, type=?, consultation=? WHERE id=?");
				$stmt->bindParam(1, $staff['name']);
				$stmt->bindParam(2, $staff['location']);
				$stmt->bindParam(3, $staff['phone']);
				$stmt->bindParam(4, $staff['email']);
				$stmt->bindParam(5, $staff['type']);
				$stmt->bindParam(6, $staff['consultation']);
				$stmt->bindParam(7, $staff['id']);
			}
			if ($stmt->execute() === false) {
				echo "Staff failed";
				return false;
			}
		}
		return true;
	}
}

class LG_Attribute_Approaches extends Attribute {
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $apporach) {
			$stmt = $this->db->prepare("UPDATE " . $this->table . " SET approach=? WHERE id=?");
			$stmt->bindParam(1, $apporach['approach']);
			$stmt->bindParam(2, $apporach['id']);
			if ($stmt->execute() === false) {
				echo "Failed to save";
				return false;
			}
		}
		return true;
	}
}




class LG_Attribute_ModesOfDelivery extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data("id, mode, hours", $this->table, "lg_id = " . $this->lg_id . " AND mode <> 'vUWS'");
	}

	public function save_approved($data) {
		if ($data == null) {
			return true;
		}
		$data[] = [
			'mode' => 'vUWS',
			'hours' => 0
		];
		foreach ($data as $ass) {
			if (!isset($ass['mode'])) {
				$ass['mode'] = null;
			}
			if (!isset($ass['hours'])) {
				$ass['hours'] = null;
			}
			$stmt = $this->db->prepare("INSERT INTO " . $this->table . "(lg_id, mode, hours) VALUES(?, ?, ?)");
			$stmt->bindParam(1, $this->lg_id);
			$stmt->bindParam(2, $ass['mode']);
			$stmt->bindParam(3, $ass['hours']);
			if ($stmt->execute() === false) {
				echo "Failed";
				return false;
			}
		}
		return true;
	}
}

class LG_Attribute_AssessmentSummary extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data("id, name, weight, due_date, ulos, threshold", $this->table, "lg_id = " . $this->lg_id);
	}
}

class LG_Attribute_Assessments extends Attribute {
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $ass) {
			$include_exemplar = $ass['exemplar'];
			if ($include_exemplar == "0") {
				$include_exemplar = 0;
			} else {
				$include_exemplar = 1;
			}
			$stmt = $this->db->prepare("UPDATE " . $this->table . " SET mode=?, due_date=?, submission=?, collaboration=?, instructions=?, format=?, resources=?, exemplar=?, exemplar_text=?, threshold_text=? WHERE id=?");
			$stmt->bindParam(1, $ass['mode']);
			$stmt->bindParam(2, $ass['due_date']);
			$stmt->bindParam(3, $ass['submission']);
			$stmt->bindParam(4, $ass['collaboration']);
			$stmt->bindParam(5, $ass['instructions']);
			$stmt->bindParam(6, $ass['format']);
			$stmt->bindParam(7, $ass['resources']);
			$stmt->bindParam(8, $include_exemplar);
			$stmt->bindParam(9, $ass['exemplar_text']);
			$stmt->bindParam(10, $ass['threshold_detail']);
			$stmt->bindParam(11, $ass['id']);
			if ($stmt->execute() === false) {
				echo "Failed to save assessments";
				print_r($stmt->errorInfo());
				return false;
			}
		}
		return true;
	}

	public function save_approved($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $ass) {
			if (!isset($ass['name'])) {
				$ass['name'] = null;
			}
			if (!isset($ass['weight'])) {
				$ass['weight'] = null;
			}
			if (!isset($ass['ulos'])) {
				$ass['ulos'] = null;
			}
			if (!isset($ass['length'])) {
				$ass['length'] = null;
			}
			if (!isset($ass['gi'])) {
				$ass['gi'] = null;
			}
			if (!isset($ass['threshold'])) {
				$ass['threshold'] = null;
			}
			$stmt = $this->db->prepare("INSERT INTO " . $this->table . "(lg_id, name, weight, ulos, length, collaboration, threshold) VALUES(?, ?, ?, ?, ?, ?, ?)");
			$stmt->bindParam(1, $this->lg_id);
			$stmt->bindParam(2, $ass['name']);
			$stmt->bindParam(3, $ass['weight']);
			$stmt->bindParam(4, $ass['ulos']);
			$stmt->bindParam(5, $ass['length']);
			$stmt->bindParam(6, $ass['collaboration']);
			$stmt->bindParam(7, $ass['threshold']);
			if ($stmt->execute() === false) {
				echo "Failed";
				return false;
			}
		}
		return true;
	}

}



class LG_Attribute_MarkingCriteria extends Attribute {
	public function retrieve() {
		$assessments = $this->lg->attribute("assessments")->get();
		if ($assessments == null) {
			return null;
		}
		$data = [];
		foreach ($assessments as $ass) {
			$result = $this->retrieve_data("*", $this->table, "ass_id = " . $ass["id"]);
			$ass["table"] = $result;
			array_push($data, $ass);
		}
		$this->data = $data;
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		// Save assessment data first
		foreach ($data as $ass) {
			$stmt = null;
			$stmt = $this->db->prepare("UPDATE lg_assessments SET marking_criteria_type=?, marking_criteria_rich=?, marking_criteria_plain=? WHERE id=?");
			$stmt->bindParam(1, $ass['marking_criteria_type']);
			$stmt->bindParam(2, $ass['marking_criteria_rich']);
			$stmt->bindParam(3, $ass['marking_criteria_plain']);
			$stmt->bindParam(4, $ass['id']);
			if ($stmt->execute() === false) {
				return false;
			}
			try {
				$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE ass_id=?");
				$stmt->bindParam(1, $ass['id']);
				$stmt->execute();
			} catch (Exception $e) {
				echo "Could not delete: " . $e;
				return false;
			}
		}

		// Save marking criteria table
		foreach ($data as $ass) {
            if (isset($ass["table"]) && $ass["table"] != null) {
    			foreach ($ass["table"] as $row) {
    				$stmt = null;
    				try {
    					if ($row["id"] != null && $row["id"] != "") {
    						$stmt = $this->db->prepare("INSERT INTO " . $this->table . "(id, criteria, hd, d, c, p, f, ass_id) VALUE(?, ?, ?, ?, ?, ?, ?, ?)");
    						$stmt->bindParam(1, $row['id']);
    						$stmt->bindParam(2, $row['criteria']);
    						$stmt->bindParam(3, $row['hd']);
    						$stmt->bindParam(4, $row['d']);
    						$stmt->bindParam(5, $row['c']);
    						$stmt->bindParam(6, $row['p']);
    						$stmt->bindParam(7, $row['f']);
    						$stmt->bindParam(8, $ass['id']);
    					} else {
    						$stmt = $this->db->prepare("INSERT INTO " . $this->table . "(criteria, hd, d, c, p, f, ass_id) VALUE(?, ?, ?, ?, ?, ?, ?)");
    						$stmt->bindParam(1, $row['criteria']);
    						$stmt->bindParam(2, $row['hd']);
    						$stmt->bindParam(3, $row['d']);
    						$stmt->bindParam(4, $row['c']);
    						$stmt->bindParam(5, $row['p']);
    						$stmt->bindParam(6, $row['f']);
    						$stmt->bindParam(7, $ass['id']);
    					}
    					$stmt->execute();
    				} catch (Exception $e) {
    					echo "Could not insert: " . $e;
    					return false;
    				}
    			}
            }
		}
		return true;
	}

}

class LG_Attribute_UnitCourse extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "lg_id = " . $this->lg_id);
	}
}

class LG_Attribute_UnitClos extends Attribute {
	public function retrieve() {
		// Prepare the array
		$clos = [];
		// Get the courses for the unit
		$courses = $this->lg->attribute("courses")->get();
		if ($courses == null) {
			return;
		}
		// Get unit learning outcomes
		$ulos = $this->lg->attribute("outcomes")->get();
		if ($ulos == null) {
			return;
		}
		$num_ulos = 0;

		foreach ($ulos as $outcome) {
			if ($outcome["number"] != "" && $outcome["number"] != null) {
				$num_ulos++;
			}
		}
		// get clo table ids
		$clo_table_ids = $this->retrieve_data("id, course_code", "v_clo_table_course", "lg_id = " . $this->lg_id );
		if ($clo_table_ids == null) {
			return;
		}

		// Get data for each course contribution table
		foreach ($courses as $course) {
			// find clo table id
			$table_id = null;
			foreach ($clo_table_ids as $row) {
				if ($row["course_code"] == $course["course_code"]) {
					$table_id = $row["id"];
					break;
				}
			}
			if ($table_id == null) {
				break;
			}
			// Set table attributes
			$table["num_ulos"] = $num_ulos;
			$table["id"] = $course["id"];
			$table["course_code"] = $course["course_code"];
			$table["course_name"] = $course["name"];
			$table["course_intro"] = $course["intro"];
			$table["course_outcomes"] = $this->retrieve_data("*", "course_clo", "course_code = " . $course["course_code"] . " ORDER BY num ASC");
			$table["contributions"] = [];
			// Get the contribution between course and unit outcomes
			$contributions = $this->retrieve_data("*", "lg_clo_ulo", "clo_table_id = " . $table_id);
			for ($r=0; $r < count($table["course_outcomes"]); $r++) {
				$row = [];
				for ($col=0; $col < $num_ulos; $col++) {
					$cell = null;
					// Find if any contribution matches the cell position
					foreach ($contributions as $contribution) {
						if ($contribution["ulo_num"] - 1 === $col  && $contribution["clo_num"] - 1 === $r) {
							$cell = $contribution;
							break;
						}
					}
					// Add cell to row
					array_push($row, $cell);
				}
				// Add row to table
				array_push($table["contributions"], $row);
			}
			// Add course and its table to clos
			array_push($clos, $table);
		}
		$this->data = $clos;
	}


	public function save_contribution($data) {
		if ($data == null) {
			return true;
		}

		foreach ($data as $cont) {
			$stmt = null;

			// echo "<pre>";
			// print_r($cont);
			// echo "</pre>";
			if (empty($cont['id'])) {
				try {
					$stmt = $this->db->prepare("INSERT INTO lg_clo_ulo (clo_table_id, clo_num, ulo_num, contribution) VALUES (?, ?, ?, ?)");
					$stmt->bindParam(1, $cont['clo_table_id']);
					$stmt->bindParam(2, $cont['clo_num']);
					$stmt->bindParam(3, $cont['ulo_num']);
					$stmt->bindParam(4, $cont['contribution']);
				} catch (Exception $e) {
					echo "Could not insert: " . $e;
					return;
				}
			} else {

				try {
					$stmt = $this->db->prepare("UPDATE lg_clo_ulo SET clo_table_id=?, clo_num=?, ulo_num=?, contribution=? WHERE id=?");
					$stmt->bindParam(1, $cont['clo_table_id']);
					$stmt->bindParam(2, $cont['clo_num']);
					$stmt->bindParam(3, $cont['ulo_num']);
					$stmt->bindParam(4, $cont['contribution']);
					$stmt->bindParam(5, $cont['id']);
				} catch (Exception $e) {
					echo "Could not update: " . $e;
					return;
				}
			}
			if ($stmt == null || $stmt->execute() === false) {
				echo "saving the contributions failed";
				return false;
			}
			//echo $cont['test'];
		}
		return true;
	}

	public function save_clo_table($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $table) {
			$stmt = null;
			if (isset($table['deleted'])) {
				try {
					$stmt = $this->db->prepare("DELETE FROM lg_clo_table WHERE id=?");
					$stmt->bindParam(1, $table['id']);
					$stmt->execute();
				} catch (Exception $e) {
					echo "Could not delete: " . $e;
					return;
				}
			} else {
				if (empty($table['id'])) {
					try {
						$stmt = $this->db->prepare("INSERT INTO lg_clo_table (lg_id, course_code, intro) VALUES (?, ?, ?)");
						$stmt->bindParam(1, $this->lg_id);
						$stmt->bindParam(2, $table['course_code']);
						$stmt->bindParam(3, $table['course_intro']);
					} catch (Exception $e) {
						echo "Could not insert: " . $e;
						return;
					}
				} else {
					try {
						$stmt = $this->db->prepare("UPDATE lg_clo_table SET course_code=?, intro=? WHERE id=?");
						$stmt->bindParam(1, $table['course_code']);
						$stmt->bindParam(2, $table['course_intro']);
						$stmt->bindParam(3, $table['id']);
					} catch (Exception $e) {
						echo "Could not update: " . $e;
						return;
					}
				}
				if ($stmt == null || $stmt->execute() === false) {
					echo "Could not update course table";
					return false;
				}
			}
		}
		return true;
	}


	public function add_clo_table($data) {
		if ($data == null) {
			return true;
		}
		$stmt = null;
		try {
			$stmt = $this->db->prepare("INSERT INTO lg_clo_table (lg_id, course_code, intro) VALUES (?, ?, ?)");
			$stmt->bindParam(1, $this->lg_id);
			$stmt->bindParam(2, $data['course_code']);
			$stmt->bindParam(3, $data['course_intro']);
			if ($stmt->execute() === false) {
				echo "Add failed";
				return false;
			}
		} catch (Exception $e) {
			echo "Could not insert: " . $e;
			return false;
		}
		return true;
	}

	public function save($data) {
		$contribution = [];

		// Seperate data into their respective tables
		foreach ($data as $course) {
			foreach ($course["contributions"] as $row) {
				if ($row != null) {
					$contribution[] = $row;
				}
			}
		}
		// echo "<pre>";
		// print_r($contribution);
		// echo "</pre>";
		$this->save_clo_table($data);
		$this->save_contribution($contribution);
		return true;
	}
}


class LG_Attribute_SessionWeeks extends Attribute {
	public function retrieve() {
		$details = $this->lg->attribute('core')->get();
		$this->data = $this->retrieve_data(
			"*",
			$this->table,
			"session = " . $this->db->quote($details["session"]) . " AND year = " . $this->db->quote($details["year"])
		);
	}
}


class LG_Attribute_ActivityData extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data(
			"d.id, c.id as col_id, c.col_name, d.data, a.week",
			"`lg_activity_data` d join `session_dates` a on d.date_id = a.id join `lg_activity_col`c on d.col_id = c.id",
			"c.lg_id=" . $this->lg_id . " order by c.col_name, a.week asc"
		);
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$col_id = $data["id"];
		foreach ($data["weeks"] as $cell) {
			$stmt = null;
			if ($cell['id'] == null || $cell['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO lg_activity_data (col_id, date_id, data) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $col_id);
				$stmt->bindParam(2, $cell['date_id']);
				$stmt->bindParam(3, $cell['data']);
			} else {
				$stmt = $this->db->prepare("UPDATE lg_activity_data SET data=? WHERE id=?");
				$stmt->bindParam(1, $cell['data']);
				$stmt->bindParam(2, $cell['id']);
			}
			if ($stmt->execute() === false) {
				echo "Fail";
				return false;
			}
		}
		return true;
	}
}




class LG_Attribute_ActivityColumns extends Attribute {

	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $col) {
			$stmt = null;
			if ($col['id'] == null || $col['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (lg_id, col_name) VALUES (?, ?)");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $col['name']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET col_name=? WHERE id=?");
				$stmt->bindParam(1, $col['name']);
				$stmt->bindParam(2, $col['id']);
			}
			if ($stmt->execute() === false) {
				echo "Fail";
				return false;
			} else {
				if ($col['id'] == null || $col['id'] == "") {
					$col['id'] = $this->db->lastInsertId();
				}
				$this->lg->attribute("activity_data")->save($col);
			}
		}
		return true;
	}

	public function delete($data) {
		foreach ($data as $col_id) {
			try {
				$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id=?");
				$stmt->bindParam(1, $col_id);
				$stmt->execute();
			} catch (Exception $e) {
				echo "Could not delete: " . $e;
				return;
			}
		}
	}
}


class LG_Attribute_ActivityAssessments extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data(
			"a.id, d.id as date_id, d.week, a.ass_id, ass.name, a.isChecked",
			"`lg_activity_ass` a join `session_dates` d on a.date_id = d.id join `lg_assessments` ass on a.ass_id = ass.id",
			"ass.lg_id=" . $this->lg_id //. " and a.isChecked"
		);
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $ass) {
			$stmt = null;
			if ($ass['id'] == null || $ass['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO lg_activity_ass (ass_id, date_id, isChecked) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $ass['ass_id']);
				$stmt->bindParam(2, $ass['date_id']);
				$stmt->bindParam(3, $ass['isChecked']);
			} else {
				$stmt = $this->db->prepare("UPDATE lg_activity_ass SET isChecked=? WHERE id=?");
				$stmt->bindParam(1, $ass['isChecked']);
				$stmt->bindParam(2, $ass['id']);
			}
			if ($stmt->execute() === false) {
				echo "Fail";
				return false;
			}
		}
		return true;
	}
}

class LG_Attribute_Readings extends Attribute {
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $reading) {
			$stmt = null;
			if (isset($reading['deleted'])) {
				try {
					$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id=?");
					$stmt->bindParam(1, $reading['id']);
					$stmt->execute();
				} catch (Exception $e) {
					echo "Could not delete: " . $e;
					return;
				}
			} else {
				if ($reading['id'] == null || $reading['id'] == "") {
					$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (lg_id, resource_type, reference) VALUES (?, ?, ?)");
					$stmt->bindParam(1, $this->lg_id);
					$stmt->bindParam(2, $reading['resource_type']);
					$stmt->bindParam(3, $reading['reference']);
				} else {
					$stmt = $this->db->prepare("UPDATE " . $this->table . " SET resource_type=?, reference=? WHERE id=?");
					$stmt->bindParam(1, $reading['resource_type']);
					$stmt->bindParam(2, $reading['reference']);
					$stmt->bindParam(3, $reading['id']);
				}
				if ($stmt->execute() === false) {
					return false;
				}
			}
		}
		return true;
	}
}

class LG_Attribute_Checklist extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "lg_id = " . $this->lg_id);
		if ($this->data != null) {
			$this->data = $this->data[0];
		}
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$stmt = null;
		try {
			if ($data['id'] == null || $data['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (lg_id,comments,x1,x2,x3,x4,x5,x6,x7,x8,x9,x10,x11,x12,x13,x14,x15,x16,x17,x18,x19,x20,x21,x22) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
				$stmt->bindParam(1, $this->lg_id);
				$stmt->bindParam(2, $data['comments']);
				for ($i=1; $i < 23; $i++) {
					$stmt->bindParam($i + 2, $data['x' . $i]);
				}
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET comments=?,x1=?,x2=?,x3=?,x4=?,x5=?,x6=?,x7=?,x8=?,x9=?,x10=?,x11=?,x12=?,x13=?,x14=?,x15=?,x16=?,x17=?,x18=?,x19=?,x20=?,x21=?,x22=? WHERE id=?");
				$stmt->bindParam(1, $data['comments']);
				for ($i=1; $i < 23; $i++) {
					$stmt->bindParam($i + 1, $data['x' . $i]);
				}
				$stmt->bindParam(24, $data['id']);
			}
			if ($stmt->execute() === false) {
				echo "Fail";
				return false;
			}
		} catch (Exception $e) {
			echo $e;
		}



		return true;
	}
}

class LG_Attribute_AllCourses extends Attribute {
	public function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "1");
	}
}
