<?php

class Users {

	protected $db;

	public function __construct($db = null) {
		if ($db != null) {
			$this->db = $db;
		}
	}

	public function get_all_users() {
		$data = null;
		$stmt = $this->db->query("SELECT * FROM user ORDER BY name ASC");
		if ($stmt != false) {
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		return $data;
	}

	public function get_all_uc() {
		$data = null;
		$stmt = $this->db->query("SELECT * FROM user WHERE type='uc' ORDER BY name ASC");
		if ($stmt != false) {
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		return $data;
	}

	public function get_all_dap() {
		$data = null;
		$stmt = $this->db->query("SELECT * FROM user WHERE type='dap' ORDER BY name ASC");
		if ($stmt != false) {
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		return $data;
	}

	public function get_all_admin() {
		$data = null;
		$stmt = $this->db->query("SELECT * FROM user WHERE type='admin' ORDER BY name ASC");
		if ($stmt != false) {
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		return $data;
	}

	public function get_user_name($id) {
		$name = null;
		$stmt = $this->db->prepare("SELECT * FROM user WHERE id=?");
		$stmt->bindParam(1, $id);
		if ($stmt->execute() != false) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$name = $row['name'];
		}
		return $name;
	}

	public function get_user_id($name) {
		$id = null;
		$stmt = $this->db->prepare("SELECT * FROM user WHERE LOWER(name)=LOWER(?)");
		$stmt->bindParam(1, $name);
		if ($stmt->execute() != false) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$id = $row['id'];
		}
		return $id;
	}

	public function get_email($id) {
		$name = null;
		$stmt = $this->db->prepare("SELECT * FROM user WHERE id=?");
		$stmt->bindParam(1, $id);
		if ($stmt->execute() != false) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$name = $row['email'];
		}
		return $name;
	}

	public function set_user_type($id, $type) {
		if ($type == "uc" || $type == "dap" || $type == "admin" || $type == "actingdap") {
			$stmt = $this->db->prepare("UPDATE user SET type=? WHERE id=?");
			$stmt->bindParam(1, $type);
			$stmt->bindParam(2, $id);
			if ($stmt->execute() != false) {
				return true;
			}
		}
		return false;
	}

	public function update_user($user_details) {
		if ($user_details == null) {
			return false;
		}

		// Null any empty keys
		if (!isset($user_details['name'])) {
			$user_details['name'] = null;
		}
		if (!isset($user_details['observer'])) {
			$user_details['observer'] = null;
		}
		if (!isset($user_details['uc'])) {
			$user_details['uc'] = null;
		}
		if (!isset($user_details['acting_dap'])) {
			$user_details['acting_dap'] = null;
		}
		if (!isset($user_details['acting_dap_id'])) {
			$user_details['acting_dap_id'] = null;
		}
		if (!isset($user_details['admin'])) {
			$user_details['admin'] = null;
		}
		if (!isset($user_details['developer'])) {
			$user_details['developer'] = null;
		}
		if (!isset($user_details['dap'])) {
			$user_details['dap'] = null;
		}
		if (!isset($user_details['id'])) {
			$user_details['id'] = null;
		}

		// Update user
		try {
			$stmt = $this->db->prepare("UPDATE user SET name=?, observer=?, uc=?, acting_dap=?, acting_dap_id=?, admin=?, developer=?, dap=? WHERE id=?");
			$stmt->bindParam(1, $user_details['name']);
			$stmt->bindParam(2, $user_details['observer']);
			$stmt->bindParam(3, $user_details['uc']);
			$stmt->bindParam(4, $user_details['acting_dap']);
			$stmt->bindParam(5, $user_details['acting_dap_id']);
			$stmt->bindParam(6, $user_details['admin']);
			$stmt->bindParam(7, $user_details['developer']);
			$stmt->bindParam(8, $user_details['dap']);
			$stmt->bindParam(9, $user_details['id']);
			if ($stmt->execute() == false) {
				echo "Failed to update user";
				return false;
			}
		} catch (Exception $e) {
			echo "Failed to update user: " . $e;
			return false;
		}

		// Everything worked
		return true;
	}

}
