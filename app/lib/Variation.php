<?php

class Variation {
	protected $variation_id = null;
	protected $db = null;
	protected $unit_code = null;
	protected $attribute = [];
    protected $exists = false;

	public function __construct($variation_id = null, $db = null) {
        if ($db != null) {
            $this->db = $db;
        }
		if ($variation_id != null && $db != null) {
			$this->variation_id = $variation_id;
			// Check variation id exists
			if ($this->exists()) {
				$this->attribute["core"] = new Variation_Attribute_Core($this->variation_id, $this->db, "unit");
				$this->attribute["outcomes"] = new Variation_Attribute_Outcomes($this->variation_id, $this->db, "unit_outcomes");
				$this->attribute["assessments"] = new Variation_Attribute_Assessments($this->variation_id, $this->db, null);
				$this->attribute["courses"] = new Variation_Attribute_Courses($this->variation_id, $this->db, "unit_courses");
				$this->attribute["prerequisite"] = new Variation_Attribute_Prerequisite($this->variation_id, $this->db, "unit_related_units");
				$this->attribute["corequisite"] = new Variation_Attribute_Corequisite($this->variation_id, $this->db, "unit_related_units");
				$this->attribute["equivalent"] = new Variation_Attribute_Equivalent($this->variation_id, $this->db, "unit_related_units");
				$this->attribute["incompatible"] = new Variation_Attribute_Incompatible($this->variation_id, $this->db, "unit_related_units");
				$this->attribute["work_integrated_learning"] = new Variation_Attribute_WIL($this->variation_id, $this->db, "unit_work_integrated_learning");
				$this->attribute["accreditation"] = new Variation_Attribute_Accreditation($this->variation_id, $this->db, "unit_accreditation");
				$this->attribute["discontinuation"] = new Variation_Attribute_Discontinuation($this->variation_id, $this->db, "unit_discontinuation");
				$this->attribute["discontinuation_arrangements"] = new Variation_Attribute_Discontinuation_Arrangements($this->variation_id, $this->db, "unit_discontinuation_arrangements");
				$this->attribute["offerings"] = new Variation_Attribute_Offerings($this->variation_id, $this->db, "unit_offerings");
				$this->attribute["timetabling"] = new Variation_Attribute_Timetabling($this->variation_id, $this->db, "unit_timetabling");
				$this->attribute["readings"] = new Variation_Attribute_Readings($this->variation_id, $this->db, "unit_readings");
				$this->attribute["responsible_schools"] = new Variation_Attribute_Schools($this->variation_id, $this->db, "unit_responsible_schools");
				$this->attribute["editors"] = new Variation_Attribute_Editors($this->variation_id, $this->db, "unit_editors");
            }
		}
	}

    public function set_variation_id($variation_id) {
        $this->variation_id = $variation_id;
        foreach ($this->attribute as $value) {
            if ($value->has_data() == true) {
                $value->set_variation_id($this->variation_id);
            }
        }
    }

	public function exists() {
        if ($this->exists) {
            return true;
        }
        if ($this->variation_id == null || $this->db == null) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT unit_code FROM unit WHERE unit_id=?');
        $stmt->bindParam(1, $this->variation_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$row) {
            return false;
        }
        $this->exists = true;
        return true;
	}

	public function create($user) {
		// Check it can be created
		if ($this->db == null) {
			echo "No database connection to create unit.";
			return false;
		}
		// Create unit record
		$unit_stmt = $this->db->prepare("INSERT INTO unit (proposal_type, state, date_started, approved, current) VALUES ('New Variation', 'Editing', CURDATE(), false, false)");
        $result = $unit_stmt->execute();
		if ($result == false) {
			echo "Failed to create unit record";
			return false;
		}
        $this->variation_id = $this->db->lastInsertId();

		// Create the work_integrated_learning record
		$wil_stmt = $this->db->prepare("INSERT INTO unit_work_integrated_learning (unit_id) VALUES (?)");
		$wil_stmt->bindParam(1, $this->variation_id);
		// Execute statement
		if ($wil_stmt->execute() == false) {
			echo "Failed to create work_integrated_learning record";
			return false;
		}

		// Add current user to editors
		if ($this->attribute == null || $this->attribute["editors"] == null) {
			$this->attribute["editors"] = new Variation_Attribute_Editors($this->variation_id, $this->db, "unit_editors");
		}
		$this->attribute("editors")->set_variation_id($this->variation_id);
		$editors = [];
		$editors[] = array("id" => "", "userid" => $user['userid'], "name" => $user['name']);
		$this->attribute("editors")->save($editors);

		return $this->variation_id;
	}

	public function attribute($attribute) {
		if (isset($this->attribute[$attribute])) {
			return $this->attribute[$attribute];
		} else {
			echo "Could not get attribute: " . $attribute;
			return null;
		}
	}

    public function retrieve_all_attributes() {
        foreach ($this->attribute as $value) {
            $value->get();
        }
    }

    public function save_all_attributes() {
        foreach ($this->attribute as $value) {
            $value->save_self();
        }
    }

    public function remove_all_ids() {
        foreach ($this->attribute as $value) {
            $value->remove_ids();
        }
    }

    public function delete() {
        foreach ($this->attribute as $value) {
            if ($value != $this->attribute('core')) {
                $value->delete_all();
            }
        }
        $this->attribute('core')->delete_all();
    }

    public function duplicate($type, $user) {
        // Only duplicate if there is an id and the variation is current
        if ($this->attribute('core')->get()['current'] != "1") {
            return false;
        }
		$previous = $this->variation_id;
        $this->retrieve_all_attributes();
        $this->remove_all_ids();
		$this->attribute("editors")->clear_data();
        $this->create($user);
        $this->set_variation_id($this->variation_id);
        $this->save_all_attributes();

		$this->attribute("core")->save_attribute("previous", $previous);
        $this->attribute("core")->save_attribute("current", "0");
        $this->attribute("core")->save_attribute("approved", "0");
        $this->attribute("core")->save_attribute("State", "Editing");
        $this->attribute("core")->save_attribute("date_started", $this->get_current_date());
		$this->attribute("core")->save_attribute('date_finished', '');
		// Wipe variation specific fields
		$this->attribute("core")->save_attribute('new_unit_rationale','');
		$this->attribute("core")->save_attribute('variation_changes','');
		$this->attribute("core")->save_attribute('variation_rationale','');
		$this->attribute("core")->save_attribute('variation_impact','');
		$this->attribute("core")->save_attribute('implementation_session','');
		$this->attribute("core")->save_attribute('implementation_year','');
        // Wipe checklist
		$this->attribute("core")->save_attribute('checklist1', '');
		$this->attribute("core")->save_attribute('checklist2', '');
		$this->attribute("core")->save_attribute('checklist3', '');
		$this->attribute("core")->save_attribute('checklist4', '');
		$this->attribute("core")->save_attribute('checklist5', '');
		$this->attribute("core")->save_attribute('checklist6', '');
		$this->attribute("core")->save_attribute('checklist7', '');
		$this->attribute("core")->save_attribute('checklist8', '');
		// Wipe approval
        $this->attribute("core")->save_attribute('deans_name', '');
        $this->attribute("core")->save_attribute('deans_endorsement_date', '');
        $this->attribute("core")->save_attribute('deans_trim_ref', '');
        $this->attribute("core")->save_attribute('sac_name', '');
        $this->attribute("core")->save_attribute('sac_meeting_date', '');
        $this->attribute("core")->save_attribute('sac_trim_ref', '');
        $this->attribute("core")->save_attribute('apcac_name', '');
        $this->attribute("core")->save_attribute('apcac_meeting_date', '');
        $this->attribute("core")->save_attribute('apcac_trim_ref', '');

		if ($type == "unit") {
			$type = "Variation";
		}
		$this->attribute("core")->save_attribute("proposal_type", ucfirst($type));



        return $this->variation_id;
    }

    public function get_current_date() {
        $datetime = new DateTime(date("Y-m-d H:i:s"));
        $datetime->setTimezone(new DateTimeZone('Australia/Sydney'));
        return $datetime->format('Y-m-d H:i:s');
    }

    public function approve() {
        // Set all other unit variations to not current
        $unit_code = $this->attribute('core')->get()['unit_code'];
		$stmt = $this->db->prepare("UPDATE unit SET current = false WHERE unit_code = ?");
        $stmt->bindParam(1, $unit_code);
        $stmt->execute();

        // Set this variation to current
        $this->attribute("core")->save_attribute("current", "1");

        // Set this variation to approved
        $this->attribute("core")->save_attribute("approved", "1");

         // Set state to complete
        $this->attribute("core")->save_attribute("state", "Complete");

        // Set finished date
        $this->attribute("core")->save_attribute("date_finished", $this->get_current_date());
    }

    public function revert() {
         // Set state to editing
        $this->attribute("core")->save_attribute("state", "Editing");
    }

    public function start_review() {
        // Set variation to review
        $this->attribute("core")->save_attribute("state", "Review");
    }

    public function get_sessions() {
        $data = $this->attribute["offerings"]->get();
        if ($data == null) {
            return false;
        }
        $sessions = [];
        // Find all unique sessions
        foreach ($data as $value) {
            if (array_search($value['session'], $sessions) === false) {
                $sessions[] = $value['session'];
            }
        }
        return $sessions;
    }

    public function get_modes_of_delivery($session) {
		$core = $this->attribute('core')->get();
        $data = $this->attribute["timetabling"]->get();
        if ($data == null) {
            return false;
        }
        $modes = [];

		if ($core['proposal_type'] == "Import") {
			// Results are unique
        	foreach ($data as $value) {
                $modes[] = ['mode' => $value['activity_type'], 'hours' => $value['activity_hours']];
	        }
			return $modes;
		} else {
			// Find all unique sessions
			foreach ($data as $value) {
				if ($value['session'] == $session) {
					$modes[] = ['mode' => $value['activity_type'], 'hours' => $value['activity_hours'], 'location' => $value['location']];
				}
			}

			$final = [];
			if ($modes != null) {
				foreach ($modes as $value) {
					if ($value['location'] == $modes[0]['location']) {
						$final[] = ['mode' => $value['mode'], 'hours' => $value['hours']];
					}
				}
			}

			return $final;
		}

    }

	public function is_editor($userid) {
		$editors = $this->attribute('editors')->get();
		if ($editors == null) {
			return false;
		}
		foreach ($editors as $key => $value) {
			if ($userid == $value['userid']) {
				return true;
			}
		}
		return false;
	}

}




class Variation_Attribute {
	protected $variation_id = null;
	protected $db = null;
	protected $table = null;
	protected $data = null;

	public function __construct($variation_id = null, $db = null, $table = null) {
		$this->variation_id = $variation_id;
		$this->db = $db;
		$this->table = $table;
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

    public function set_variation_id($variation_id) {
        $this->variation_id = $variation_id;
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

	protected function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "unit_id = " . $this->variation_id);
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

    public function compare($data) {}

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
				$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE unit_id=? AND id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $d);
				if ($stmt->execute() === false) {
					return false;
				}
			}
		}
		return true;
	}

	public function delete_all() {
        $stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE unit_id=?");
        $stmt->bindParam(1, $this->variation_id);
        if ($stmt->execute() === false) {
            echo "Failed Delete\n";
            return false;
        }
		return true;
	}
}

class Variation_Attribute_Core extends Variation_Attribute{

    public function compare_attribute($attribute, $value) {
        $this->get();
        if ($this->data[$attribute] != $value) {
            return "\y" . $value;
        }
        return $value;
    }

	protected function retrieve() {
		$temp = $this->retrieve_data("*", $this->table, "unit_id = " . $this->variation_id);
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
        if ($attribute == "unit_id") {
            return true;
        }
		$stmt = $this->db->prepare("UPDATE " . $this->table . " SET " . $attribute . "=? WHERE unit_id=?");
		$stmt->bindParam(1, $value);
		$stmt->bindParam(2, $this->variation_id);

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


class Variation_Attribute_Courses extends Variation_Attribute{

	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $course) {
			$stmt = null;
			if (!isset($course['id']) || $course['id'] == null || $course['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, course_code, course_title, role) VALUES (?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $course['course_code']);
				$stmt->bindParam(3, $course['course_title']);
				$stmt->bindParam(4, $course['role']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, course_code=?, course_title=?, role=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $course['course_code']);
				$stmt->bindParam(3, $course['course_title']);
				$stmt->bindParam(4, $course['role']);
				$stmt->bindParam(5, $course['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Courses";
				return false;
			}
		}
		return true;
	}


}


class Variation_Attribute_Assessments extends Variation_Attribute{

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
				// Remove asssessment ids
				if (isset($this->data[0]['assessments'])) {
		            foreach ($this->data[0]['assessments'] as $key => $value) {
		                if (isset($value['id'])) {
		                    $this->data[0]['assessments'][$key]['id'] = null;
		                }
		                if (isset($value['unit_id'])) {
		                    $this->data[0]['assessments'][$key]['unit_id'] = null;
		                }
		            }
		        }
            }
        }
    }

	public function delete($delete) {
		if ($delete == null) {
			return true;
		}
		// Delete from table ids in array $delete
		if ($delete['assessments'] != null) {
			foreach ($delete['assessments'] as $d) {
				$stmt = $this->db->prepare("DELETE FROM unit_assessments WHERE id=?");
				$stmt->bindParam(1, $d);
				if ($stmt->execute() === false) {
                    echo "delete ";
					return false;
				}
			}
		}

        // Delete from table ids in array $delete
		if ($delete['groups'] != null) {
			foreach ($delete['groups'] as $d) {
				$stmt = $this->db->prepare("DELETE FROM unit_assessment_group WHERE unit_id=? AND id=?");
                $stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $d);
				if ($stmt->execute() === false) {
                    echo "delete ";
					return false;
				}
			}
		}
		return true;
	}

	public function delete_all() {
        $stmt = $this->db->prepare("DELETE FROM unit_assessment_group WHERE unit_id=?");
        $stmt->bindParam(1, $this->variation_id);
        if ($stmt->execute() === false) {
            echo "Failed Delete\n";
            return false;
        }
		return true;
	}

	protected function retrieve() {
		$this->data = $this->retrieve_data("*", "unit_assessment_group", "unit_id = " . $this->variation_id);
        if ($this->data != null) {
            foreach ($this->data as $key => $value) {
                $this->data[$key]['assessments'] = $this->retrieve_data("*", "unit_assessments", "group_id = " . $value['id']);
            }
        }
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$position = 1;
        foreach ($data as $group) {
            $group_id = null;
            if (!isset($group['id'])) {
                $group['id'] = null;
            }
            if (!isset($group['type'])) {
                $group['type'] = null;
            }
            if (!isset($group['session'])) {
                $group['session'] = null;
            }
            if (!isset($group['mode'])) {
                $group['mode'] = null;
            }
            if (!isset($group['rationale'])) {
                $group['rationale'] = null;
            }
            if ($group['id'] == null || $group['id'] == "") {
                $stmt = $this->db->prepare("INSERT INTO unit_assessment_group (unit_id, type, session, mode, rationale) VALUES (?, ?, ?, ?, ?)");
                $stmt->bindParam(1, $this->variation_id);
                $stmt->bindParam(2, $group['type']);
                $stmt->bindParam(3, $group['session']);
                $stmt->bindParam(4, $group['mode']);
                $stmt->bindParam(5, $group['rationale']);
            } else {
                $stmt = $this->db->prepare("UPDATE unit_assessment_group SET unit_id=?, type=?, session=?, mode=?, rationale=? WHERE id=?");
                $stmt->bindParam(1, $this->variation_id);
                $stmt->bindParam(2, $group['type']);
                $stmt->bindParam(3, $group['session']);
                $stmt->bindParam(4, $group['mode']);
                $stmt->bindParam(5, $group['rationale']);
                $stmt->bindParam(6, $group['id']);
            }
            if ($stmt->execute() === false) {
                echo "FAILED: Assessment Groups";
                return false;
            } else {
                // Set group id to new record id
                if ($group['id'] == null || $group['id'] == "") {
                    $group_id = $this->db->lastInsertId();
                }
            }
            // Set group id if not a new record
            if ($group_id == null) {
                $group_id = $group['id'];
            }
            // Insert or update assessments in the group
            if (isset($group['assessments']) && $group['assessments'] != null) {
                foreach ($group['assessments'] as $ass) {
                    $stmt = null;
                    if (!isset($ass['name'])) {
                        $ass['name'] = null;
                    }
                    if (!isset($ass['type'])) {
                        $ass['type'] = null;
                    }
                    if (!isset($ass['length'])) {
                        $ass['length'] = null;
                    }
                    if (!isset($ass['ulos'])) {
                        $ass['ulos'] = null;
                    }
                    if (!isset($ass['weight'])) {
                        $ass['weight'] = null;
                    }
                    if (!isset($ass['threshold'])) {
                        $ass['threshold'] = null;
                    }
					if (!isset($ass['gi'])) {
                        $ass['gi'] = null;
                    }
                    if (!isset($ass['id']) || $ass['id'] == null || $ass['id'] == "") {
                        $stmt = $this->db->prepare("INSERT INTO unit_assessments (group_id, name, type, length, ulos, weight, threshold, position, gi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bindParam(1, $group_id);
                        $stmt->bindParam(2, $ass['name']);
                        $stmt->bindParam(3, $ass['type']);
                        $stmt->bindParam(4, $ass['length']);
                        $stmt->bindParam(5, $ass['ulos']);
                        $stmt->bindParam(6, $ass['weight']);
                        $stmt->bindParam(7, $ass['threshold']);
                        $stmt->bindParam(8, $position);
						$stmt->bindParam(9, $ass['gi']);
                    } else {
                        $stmt = $this->db->prepare("UPDATE unit_assessments SET name=?, type=?, length=?, ulos=?, weight=?, threshold=?, position=?, gi=? WHERE id=?");
                        $stmt->bindParam(1, $ass['name']);
                        $stmt->bindParam(2, $ass['type']);
                        $stmt->bindParam(3, $ass['length']);
                        $stmt->bindParam(4, $ass['ulos']);
                        $stmt->bindParam(5, $ass['weight']);
                        $stmt->bindParam(6, $ass['threshold']);
                        $stmt->bindParam(7, $position);
						$stmt->bindParam(8, $ass['gi']);
                        $stmt->bindParam(9, $ass['id']);
                    }
                    if ($stmt->execute() === false) {
                        echo "FAILED: Assessment Items";
                        return false;
                    }
                    $position++;
                }
            }
        }
		return true;
	}

	public function get_assessment_group($session, $mode) {
		$result = [];
		$assessments = null;
		$groups = $this->get();
		if ($groups == null || count($groups) == 0) {
			return null;
		}

		if (count($groups) == 1) {
			$assessments = $groups[0]['assessments'];
		} else {
			// Find best match
			foreach ($groups as $group) {
				if ($group['session'] == $session && $group['mode'] == $mode) {
					$assessments = $group['assessments'];
					break;
				}
			}
			// Find a single match
			if ($assessments == null) {
				foreach ($groups as $group) {
					if ($group['session'] == $session || $group['mode'] == $mode) {
						$assessments = $group['assessments'];
						break;
					}
				}
			}
			// No match means core assessments
			if ($assessments == null) {
				$assessments = $groups[0]['assessments'];
			}
		}
		// If nothing found return null
		if ($assessments == null) {
			echo "No assessments :/ ";
			return null;
		}
		// // Collect assessments into a correctly formatted array
		// foreach ($assessments as $key => $value) {
		// 	$result[] = array(
		// 		'id' => null,
		// 		'name' => $value['name'],
		// 		'weight' => $value['weight'],
		// 		'ulos' => $value['ulos'],
		// 		'length' => $value['length'],
		// 		'collaboration' => $value['gi'],
		// 		'threshold' =>$value['threshold']
		// 	);
		// }
		return $assessments;
	}
}


class Variation_Attribute_Outcomes extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $outcome) {
			$stmt = null;
			if (!isset($outcome['id']) || $outcome['id'] == null || $outcome['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, number, outcome) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $outcome['number']);
				$stmt->bindParam(3, $outcome['outcome']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, number=?, outcome=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $outcome['number']);
				$stmt->bindParam(3, $outcome['outcome']);
				$stmt->bindParam(4, $outcome['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Outcomes";
				return false;
			}
		}
		return true;
	}
}

class Variation_Attribute_Prerequisite extends Variation_Attribute{

	protected function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "relationship = 'prerequisite' AND unit_id = " . $this->variation_id);
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$relationship = 'prerequisite';
		foreach ($data as $pre) {
			$stmt = null;
			if (!isset($pre['id']) || $pre['id'] == null || $pre['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, unit_code, unit_name, justification, relationship) VALUES (?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['justification']);
				$stmt->bindParam(5, $relationship);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, unit_code=?, unit_name=?, justification=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['justification']);
				$stmt->bindParam(5, $pre['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Prerequisites";
				return false;
			}
		}
		return true;
	}

}


class Variation_Attribute_Corequisite extends Variation_Attribute{
	protected function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "relationship = 'corequisite' AND unit_id = " . $this->variation_id);
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$relationship = 'corequisite';
		foreach ($data as $pre) {
			$stmt = null;
			if (!isset($pre['id']) || $pre['id'] == null || $pre['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, unit_code, unit_name, justification, relationship) VALUES (?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['justification']);
				$stmt->bindParam(5, $relationship);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, unit_code=?, unit_name=?, justification=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['justification']);
				$stmt->bindParam(5, $pre['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Corequisites";
				return false;
			}
		}
		return true;
	}

}

class Variation_Attribute_Equivalent extends Variation_Attribute{
	protected function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "relationship = 'equivalent' AND unit_id = " . $this->variation_id);
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$relationship = 'equivalent';
		foreach ($data as $pre) {
			$stmt = null;
			if (!isset($pre['id']) || $pre['id'] == null || $pre['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, unit_code, unit_name, college_unit, relationship) VALUES (?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['college_unit']);
				$stmt->bindParam(5, $relationship);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, unit_code=?, unit_name=?, college_unit=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['college_unit']);
				$stmt->bindParam(5, $pre['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Equivalent";
				return false;
			}
		}
		return true;
	}
}

class Variation_Attribute_Incompatible extends Variation_Attribute{
	protected function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "relationship = 'incompatible' AND unit_id = " . $this->variation_id);
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		$relationship = 'incompatible';
		foreach ($data as $pre) {
			$stmt = null;
			if (!isset($pre['id']) || $pre['id'] == null || $pre['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, unit_code, unit_name, relationship) VALUES (?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $relationship);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, unit_code=?, unit_name=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $pre['unit_code']);
				$stmt->bindParam(3, $pre['unit_name']);
				$stmt->bindParam(4, $pre['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Incompatible";
				return false;
			}
		}
		return true;
	}
}

class Variation_Attribute_WIL extends Variation_Attribute{
	protected function retrieve() {
		$temp = $this->retrieve_data("*", $this->table, "unit_id = " . $this->variation_id);
		if (count($temp) > 0) {
			$this->data = $temp[0];
		}
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $key => $value) {
			if ($value == "") {
				$value = "0";
			}
		}

		$stmt = null;
//		if (!isset($data['id']) || $data['id'] == null || $data['id'] == "") {
//			$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id,service_learning,industry_projects,placement_observational,placement_experiential,structured_practicum,preparatory_components,supervision_quality,capture_placement,wei_include_experience,wei_course_relevant,wei_hours_week,wei_total_hours,wei_student_input_contact,wei_student_oversight,wei_content_objectives,wei_student_performance,wei_performance_management,wei_site_interaction,wei_placement_organisation,wei_student_monitoring,wei_student_assessment) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
//			$stmt->bindParam(1, $this->variation_id);
//			$stmt->bindParam(2, $data['service_learning']);
//			$stmt->bindParam(3, $data['industry_projects']);
//			$stmt->bindParam(4, $data['placement_observational']);
//			$stmt->bindParam(5, $data['placement_experiential']);
//			$stmt->bindParam(6, $data['structured_practicum']);
//			$stmt->bindParam(7, $data['preparatory_components']);
//			$stmt->bindParam(8, $data['supervision_quality']);
//			$stmt->bindParam(9, $data['capture_placement']);
//			$stmt->bindParam(10, $data['wei_include_experience']);
//			$stmt->bindParam(11, $data['wei_course_relevant']);
//			$stmt->bindParam(12, $data['wei_hours_week']);
//			$stmt->bindParam(13, $data['wei_total_hours']);
//			$stmt->bindParam(14, $data['wei_student_input_contact']);
//			$stmt->bindParam(15, $data['wei_student_oversight']);
//			$stmt->bindParam(16, $data['wei_content_objectives']);
//			$stmt->bindParam(17, $data['wei_student_performance']);
//			$stmt->bindParam(18, $data['wei_performance_management']);
//			$stmt->bindParam(19, $data['wei_site_interaction']);
//			$stmt->bindParam(20, $data['wei_placement_organisation']);
//			$stmt->bindParam(21, $data['wei_student_monitoring']);
//			$stmt->bindParam(22, $data['wei_student_assessment']);
//		} else {
			$stmt = $this->db->prepare("UPDATE " . $this->table . " SET service_learning=?,industry_projects=?,placement_observational=?,placement_experiential=?,structured_practicum=?,preparatory_components=?,supervision_quality=?,capture_placement=?,wei_include_experience=?,wei_course_relevant=?,wei_hours_week=?,wei_total_hours=?,wei_student_input_contact=?,wei_student_oversight=?,wei_content_objectives=?,wei_student_performance=?,wei_performance_management=?,wei_site_interaction=?,wei_placement_organisation=?,wei_student_monitoring=?,wei_student_assessment=? WHERE unit_id=?");
			$stmt->bindParam(1, $data['service_learning']);
			$stmt->bindParam(2, $data['industry_projects']);
			$stmt->bindParam(3, $data['placement_observational']);
			$stmt->bindParam(4, $data['placement_experiential']);
			$stmt->bindParam(5, $data['structured_practicum']);
			$stmt->bindParam(6, $data['preparatory_components']);
			$stmt->bindParam(7, $data['supervision_quality']);
			$stmt->bindParam(8, $data['capture_placement']);
			$stmt->bindParam(9, $data['wei_include_experience']);
			$stmt->bindParam(10, $data['wei_course_relevant']);
			$stmt->bindParam(11, $data['wei_hours_week']);
			$stmt->bindParam(12, $data['wei_total_hours']);
			$stmt->bindParam(13, $data['wei_student_input_contact']);
			$stmt->bindParam(14, $data['wei_student_oversight']);
			$stmt->bindParam(15, $data['wei_content_objectives']);
			$stmt->bindParam(16, $data['wei_student_performance']);
			$stmt->bindParam(17, $data['wei_performance_management']);
			$stmt->bindParam(18, $data['wei_site_interaction']);
			$stmt->bindParam(19, $data['wei_placement_organisation']);
			$stmt->bindParam(20, $data['wei_student_monitoring']);
			$stmt->bindParam(21, $data['wei_student_assessment']);
			$stmt->bindParam(22, $this->variation_id);
//		}
		if ($stmt->execute() === false) {
			echo "FAILED: WIL";
			return false;
		}
		return true;
	}


// unit_id
// service_learning
// industry_projects
// placement_observational
// placement_experiential
// structured_practicum
// preparatory_components
// supervision_quality
// capture_placement
// wei_include_experience
// wei_course_relevant
// wei_hours_week
// wei_total_hours
// wei_student_input_contact
// wei_student_oversight
// wei_content_objectives
// wei_student_performance
// wei_performance_management
// wei_site_interaction
// wei_placement_organisation
// wei_student_monitoring
// wei_student_assessment

}


class Variation_Attribute_Accreditation extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $accred) {
			$stmt = null;
			if (!isset($accred['id']) || $accred['id'] == null || $accred['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, code, accreditor, start_date, end_date, review_date) VALUES (?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $accred['code']);
				$stmt->bindParam(3, $accred['accreditor']);
				$stmt->bindParam(4, $accred['start_date']);
				$stmt->bindParam(5, $accred['end_date']);
				$stmt->bindParam(6, $accred['review_date']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, code=?, accreditor=?, start_date=?, end_date=?, review_date=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $accred['code']);
				$stmt->bindParam(3, $accred['accreditor']);
				$stmt->bindParam(4, $accred['start_date']);
				$stmt->bindParam(5, $accred['end_date']);
				$stmt->bindParam(6, $accred['review_date']);
				$stmt->bindParam(7, $accred['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Accreditation";
				return false;
			}
		}
		return true;
	}
}


class Variation_Attribute_Discontinuation extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $dis) {
			$stmt = null;
			if (!isset($dis['id']) || $dis['id'] == null || $dis['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, unit_code, unit_name, last_year, last_session) VALUES (?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $dis['unit_code']);
				$stmt->bindParam(3, $dis['unit_name']);
				$stmt->bindParam(4, $dis['last_year']);
				$stmt->bindParam(5, $dis['last_session']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, unit_code=?, unit_name=?, last_year=?, last_session=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $dis['unit_code']);
				$stmt->bindParam(3, $dis['unit_name']);
				$stmt->bindParam(4, $dis['last_year']);
				$stmt->bindParam(5, $dis['last_session']);
				$stmt->bindParam(6, $dis['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Discontinuation";
				return false;
			}
		}
		return true;
	}
}

class Variation_Attribute_Discontinuation_Arrangements extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $dis) {
			$stmt = null;
			if (!isset($dis['id']) || $dis['id'] == null || $dis['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, code, title, num_students, transition_arrangements) VALUES (?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $dis['code']);
				$stmt->bindParam(3, $dis['title']);
				$stmt->bindParam(4, $dis['num_students']);
				$stmt->bindParam(5, $dis['transition_arrangements']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, code=?, title=?, num_students=?, transition_arrangements=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $dis['code']);
				$stmt->bindParam(3, $dis['title']);
				$stmt->bindParam(4, $dis['num_students']);
				$stmt->bindParam(5, $dis['transition_arrangements']);
				$stmt->bindParam(6, $dis['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Discontinuation Arrangements";
				return false;
			}
		}
		return true;
	}
}


class Variation_Attribute_Offerings extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $dis) {
			$stmt = null;
			if (!isset($dis['id']) || $dis['id'] == null || $dis['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, session, location, unit_class, est_num_students, quota) VALUES (?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $dis['session']);
				$stmt->bindParam(3, $dis['location']);
				$stmt->bindParam(4, $dis['unit_class']);
				$stmt->bindParam(5, $dis['est_num_students']);
				$stmt->bindParam(6, $dis['quota']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, session=?, location=?, unit_class=?, est_num_students=?, quota=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $dis['session']);
				$stmt->bindParam(3, $dis['location']);
				$stmt->bindParam(4, $dis['unit_class']);
				$stmt->bindParam(5, $dis['est_num_students']);
				$stmt->bindParam(6, $dis['quota']);
				$stmt->bindParam(7, $dis['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Offerings";
				return false;
			}
		}
		return true;
	}
}



class Variation_Attribute_Timetabling extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $tt) {
			$stmt = null;
			if (!isset($tt['id']) || $tt['id'] == null || $tt['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, session, location, activity_type, space_type, activity_hours, total_session_hours, activity_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $tt['session']);
				$stmt->bindParam(3, $tt['location']);
				$stmt->bindParam(4, $tt['activity_type']);
				$stmt->bindParam(5, $tt['space_type']);
				$stmt->bindParam(6, $tt['activity_hours']);
				$stmt->bindParam(7, $tt['total_session_hours']);
				$stmt->bindParam(8, $tt['activity_size']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, session=?, location=?, activity_type=?, space_type=?, activity_hours=?, total_session_hours=?, activity_size=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $tt['session']);
				$stmt->bindParam(3, $tt['location']);
				$stmt->bindParam(4, $tt['activity_type']);
				$stmt->bindParam(5, $tt['space_type']);
				$stmt->bindParam(6, $tt['activity_hours']);
				$stmt->bindParam(7, $tt['total_session_hours']);
				$stmt->bindParam(8, $tt['activity_size']);
				$stmt->bindParam(9, $tt['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Timetabling";
				return false;
			}
		}
		return true;
	}
}



class Variation_Attribute_Schools extends Variation_Attribute{
	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $tt) {
			$stmt = null;
			if (!isset($tt['id']) || $tt['id'] == null || $tt['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, school, percent, contact, extension, email) VALUES (?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $tt['school']);
				$stmt->bindParam(3, $tt['percent']);
				$stmt->bindParam(4, $tt['contact']);
				$stmt->bindParam(5, $tt['extension']);
				$stmt->bindParam(6, $tt['email']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, school=?, percent=?, contact=?, extension=?, email=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $tt['school']);
				$stmt->bindParam(3, $tt['percent']);
				$stmt->bindParam(4, $tt['contact']);
				$stmt->bindParam(5, $tt['extension']);
				$stmt->bindParam(6, $tt['email']);
				$stmt->bindParam(7, $tt['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Schools";
				return false;
			}
		}
		return true;
	}
}



class Variation_Attribute_Readings extends Variation_Attribute{
	protected function retrieve() {
		$this->data = $this->retrieve_data("*", $this->table, "unit_id = " . $this->variation_id . " ORDER BY resource_type DESC, reference ASC");
	}

	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $tt) {
			$stmt = null;
			if (!isset($tt['id']) || $tt['id'] == null || $tt['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, resource_type, reference) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $tt['resource_type']);
				$stmt->bindParam(3, $tt['reference']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, resource_type=?, reference=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $tt['resource_type']);
				$stmt->bindParam(3, $tt['reference']);
				$stmt->bindParam(4, $tt['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Readings";
				return false;
			}
		}
		return true;
	}
}

class Variation_Attribute_Editors extends Variation_Attribute{

	public function save($data) {
		if ($data == null) {
			return true;
		}
		foreach ($data as $editor) {
			$stmt = null;
			if (!isset($editor['id']) || $editor['id'] == null || $editor['id'] == "") {
				$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (unit_id, userid, name) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $editor['userid']);
				$stmt->bindParam(3, $editor['name']);
			} else {
				$stmt = $this->db->prepare("UPDATE " . $this->table . " SET unit_id=?, userid=?, name=? WHERE id=?");
				$stmt->bindParam(1, $this->variation_id);
				$stmt->bindParam(2, $editor['userid']);
				$stmt->bindParam(3, $editor['name']);
				$stmt->bindParam(4, $editor['id']);
			}
			if ($stmt->execute() === false) {
				echo "FAILED: Courses";
				return false;
			}
		}
		return true;
	}


}
