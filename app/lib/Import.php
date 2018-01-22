<?php

class Import {

		protected $var = null;
		protected $lg = null;
		protected $unit_code = null;
		protected $year = null;
		protected $session = null;
		protected $class = null;
		protected $lgms_db = null;
		protected $cams_db = null;

		public function __construct($unit_code, $year, $session, $class, $uc, $lgms_db, $cams_db) {

			// Assign values
			$this->unit_code = $unit_code;
			$this->year = $year;
			$this->session = $session;
			$this->class = $class;
			$this->lgms_db = $lgms_db;
			$this->cams_db = $cams_db;

			// Get the current variation id
			$var_id = $this->get_current_variation();
			if ($var_id == null) {
				echo "FAILED: Could not find the variation.";
				return;
			}

			// Create the variation
			$this->var = new Variation($var_id, $cams_db);

			// Create the learning guide
			$this->lg = new Learning_Guide($unit_code, $lgms_db);
			$this->lg->create($unit_code);

            // Populate core attributes
            $core = $this->var->attribute('core')->get();
			$this->lg->attribute('core')->save_attribute('state', 'Scheduled');
			$this->lg->attribute('core')->save_attribute('year', $year);
			$this->lg->attribute('core')->save_attribute('session', $session);
			$this->lg->attribute('core')->save_attribute('class', $class);
			$this->lg->attribute('core')->save_attribute('editor', $uc);
			$users = new Users($lgms_db);
			$this->lg->attribute('core')->save_attribute('editor', $users->get_user_name($uc));
			$this->lg->attribute('core')->save_attribute('editor_id', $uc);
			$this->lg->attribute('core')->save_attribute('unit_name', $core['unit_name']);
			$this->lg->attribute('core')->save_attribute('credit_points', $core['credit_points']);
			$this->lg->attribute('core')->save_attribute('unit_level', $core['unit_level']);
			$this->lg->attribute('core')->save_attribute('assumed_knowledge', $core['assumed_knowledge']);
			$this->lg->attribute('core')->save_attribute('handbook_summary', $core['handbook_summary']);
			$this->lg->attribute('core')->save_attribute('attendance_requirements', $core['attendance_requirements']);
			$this->lg->attribute('core')->save_attribute('online_learning_requirements', $core['online_learning_requirements']);
			$this->lg->attribute('core')->save_attribute('essential_equipment', $core['essential_equipment']);
			$this->lg->attribute('core')->save_attribute('legislative_prerequisites', $core['legislative_prerequisites']);
			$this->lg->attribute('core')->save_attribute('regenerate', '1');

            // Unit outcomes
            $this->var->attribute('outcomes')->get();
            $this->var->attribute('outcomes')->remove_ids();
            $data = $this->var->attribute('outcomes')->get();
            $this->lg->attribute('outcomes')->save($data);

            // Readings
            $this->var->attribute('readings')->get();
            $this->var->attribute('readings')->remove_ids();
            $data = $this->var->attribute('readings')->get();
            $this->lg->attribute('readings')->save($data);

			// Assessments
			$data = $this->get_assessments();
			$this->lg->attribute('assessments')->save_approved($data);

			// Modes of Delivery
			$data = $this->var->get_modes_of_delivery($this->session);
			$this->lg->attribute('modes_of_delivery')->save_approved($data);

			// Prerequisites
			$data = $this->var->attribute('prerequisite')->get();
			$string = "";
			if ($data != null) {
				foreach ($data as $key => $value) {
					if ($data[0] != $value) {
						$string .= ", ";
					}
					$string .= $value['unit_code'] . " " . $value['unit_name'];
				}
			}
			$this->lg->attribute('core')->save_attribute('prerequisites', $string);

			// Corequisites
			$data = $this->var->attribute('corequisite')->get();
			$string = "";
			if ($data != null) {
				foreach ($data as $key => $value) {
					if ($data[0] != $value) {
						$string .= ", ";
					}
					$string .= $value['unit_code'] . " " . $value['unit_name'];
				}
			}
			$this->lg->attribute('core')->save_attribute('corequisites', $string);

			// Incompatible
			$data = $this->var->attribute('incompatible')->get();
			$string = "";
			if ($data != null) {
				foreach ($data as $key => $value) {
					if ($data[0] != $value) {
						$string .= ", ";
					}
					$string .= $value['unit_code'] . " " . $value['unit_name'];
				}
			}
			$this->lg->attribute('core')->save_attribute('incompatible', $string);
		}

		public function get_assessments() {
			$result = [];
			$assessments = null;
			$groups = $this->var->attribute('assessments')->get();
			if ($groups == null || count($groups) == 0) {
				return null;
			}

			if (count($groups) == 1) {
				$assessments = $groups[0]['assessments'];
			} else {
				// Find best match
				foreach ($groups as $group) {
					if ($group['session'] == $this->session && $group['mode'] == $this->class) {
						$assessments = $group['assessments'];
						break;
					}
				}
				// Find a single match
				if ($assessments == null) {
					foreach ($groups as $group) {
						if ($group['session'] == $this->session || $group['mode'] == $this->class) {
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
			// Collect assessments into a correctly formatted array
			foreach ($assessments as $key => $value) {
				$result[] = array(
					'id' => null,
					'name' => $value['name'],
					'weight' => $value['weight'],
					'ulos' => $value['ulos'],
					'length' => $value['length'],
					'collaboration' => $value['gi'],
					'threshold' =>$value['threshold']
				);
			}
			return $result;
		}

		public function get_current_variation() {
			$var_id = null;
			$stmt = $this->cams_db->prepare("SELECT unit_id FROM unit WHERE current=1 AND approved=1 AND state='Complete' AND unit_code=?");
			$stmt->bindParam(1, $this->unit_code);
			$stmt->execute();
			if ($stmt != false) {
				$data = $stmt->fetch(PDO::FETCH_ASSOC);
				$var_id = $data['unit_id'];
			}
			return $var_id;
		}
}
