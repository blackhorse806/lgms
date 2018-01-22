<?php

// *******************************
// Feedback
// *******************************
$app->get("/lg/:lg_id/get/feedback", function($lg_id) use ($app, $db) {

	// Get unit details
	$unit = new Learning_Guide($lg_id, $db);
	$feedback = $unit->attribute("feedback")->get();
	echo json_encode($feedback);
})->name('/lg/get/feedback');

$app->post("/lg/:lg_id/put/feedback", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	// Get date from post
	$feedback = json_decode($app->request->post('json'),true);
	$delete = json_decode($app->request->post('delete'),true);
	// Make sure everything worked
	$success = true;
	$lg->attribute("feedback")->delete($delete);
	$success = $lg->attribute("feedback")->save($feedback);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/feedback');


// *******************************
// Staff
// *******************************
$app->get("/lg/:lg_id/get/staff", function($lg_id) use ($app, $db) {
		// Get unit details
	$unit = new Learning_Guide($lg_id, $db);
	$staff = $unit->attribute("staff")->get();
	echo json_encode($staff);
})->name('/lg/get/staff');

$app->post("/lg/:lg_id/put/staff", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	// Get date from post
	$staff = json_decode($app->request->post('json'),true);
	$delete = json_decode($app->request->post('delete'),true);
	// Make sure everything worked
	$success = true;
	$lg->attribute("staff")->delete($delete);
	$success = $lg->attribute("staff")->save($staff);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/staff');



// *******************************
// Outcomes Introduction
// *******************************
$app->get("/lg/:lg_id/get/outcomes_intro", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();
	echo json_encode($core['learning_outcomes_intro']);
})->name('/lg/get/outcomes_intro');

$app->post("/lg/:lg_id/put/outcomes_intro", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$success = true;
	$intro = json_decode($app->request->post('intro'),true);
	$success = $lg->attribute('core')->save_attribute('learning_outcomes_intro', $intro);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/outcomes_intro');


// *******************************
// Unit Outcomes
// *******************************
$app->get("/lg/:unit_code/get/outcomes", function($unit_code) use ($app, $db) {
	// Get unit details
	$unit = new Learning_Guide($unit_code, $db);
	$outcomes = $unit->attribute("outcomes")->get();
	echo json_encode($outcomes);
})->name('/lg/get/outcomes');


// *******************************
// Apporaches
// *******************************
$app->get("/lg/:lg_id/get/approaches", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();
	$data = [];
	$data['intro'] = $core['approach_to_learning'];
	$data['approaches'] = $lg->attribute('approaches')->get();
	echo json_encode($data);
})->name('/lg/get/approaches');

$app->post("/lg/:lg_id/put/approaches", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$success = true;
	$data = json_decode($app->request->post('data'),true);
	$intro = $data["intro"];
	$approaches = $data["approaches"];
	if ($lg->attribute("approaches")->save($approaches) == false) {
		echo "Failed to submit intro.";
		return;
	}
	if ($lg->attribute('core')->save_attribute('approach_to_learning', $intro) == false) {
		echo "Failed to submit approaches.";
		return;
	}
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/approaches');


// *******************************
// Assessment Summary
// *******************************
$app->get("/lg/:lg_id/get/assessment_summary", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();
	$data = [];
	$data['pass_criteria'] = $core['pass_criteria'];
	$data['feedback'] = $core['assessment_feedback'];
	echo json_encode($data);
})->name('/lg/get/assessment_summary');

$app->post("/lg/:lg_id/put/assessment_summary", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = json_decode($app->request->post('data'),true);
	$pass_criteria = $data['pass_criteria'];
	$feedback = $data['feedback'];
	$success = true;
	if ($lg->attribute('core')->save_attribute('pass_criteria', $pass_criteria) == false) {
		echo "Failed to submit intro.";
		return;
	}
	if ($lg->attribute('core')->save_attribute('assessment_feedback', $feedback) == false) {
		echo "Failed to submit approaches.";
		return;
	}
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/assessment_summary');


// *******************************
// Assessments
// *******************************
$app->get("/lg/:lg_id/get/assessments", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$assessments = $lg->attribute('assessments')->get();
	echo json_encode($assessments);
})->name('/lg/get/assessments');

$app->post("/lg/:lg_id/put/assessments", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = json_decode($app->request->post('json'),true);
	$success = $lg->attribute('assessments')->save($data);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/assessments');


// *******************************
// Marking Criteria
// *******************************
$app->get("/lg/:lg_id/get/marking_criteria", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$marking_criteria = $lg->attribute('marking_criteria')->get();
	echo json_encode($marking_criteria);
})->name('/lg/get/marking_criteria');


$app->post("/lg/:lg_id/put/marking_criteria", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = json_decode($app->request->post('json'),true);
	$success = true;
	$sanatised_data = [];
	foreach ($data as $ass) {
		$data_row = [];
		$data_row["id"] = $ass["id"];
		$data_row["marking_criteria_type"] = $ass["type"];
		$data_row["marking_criteria_plain"] = $ass["plain"];
		$data_row["marking_criteria_rich"] = $ass["rich"];
		$data_row["table"] = $ass["table"];
		array_push($sanatised_data, $data_row);
	}
	$success = $lg->attribute('marking_criteria')->save($sanatised_data);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/marking_criteria');


// *******************************
// Course Learning Outcomes
// *******************************
$app->get("/lg/:lg_id/get/clos", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = $lg->attribute("clos")->get();
	echo json_encode($data);
})->name('/lg/get/clos');

$app->post("/lg/:lg_id/put/clos", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = json_decode($app->request->post('json'),true);
	// If no data then delete all clo tables otherwise save
	if ($data == null) {
		$lg->attribute('clos')->delete_all();
		$success = true;
	} else {
		$success = $lg->attribute("clos")->save($data);
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
	// Send response
	if ($success) {
		echo "success";
	}
})->name('/lg/put/clos');

$app->post("/lg/:lg_id/put/add_course", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = json_decode($app->request->post('json'),true);
	$success = $lg->attribute("clos")->add_clo_table($data);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/add_course');


// *******************************
// Readings
// *******************************
$app->get("/lg/:lg_id/get/readings", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$readings = $lg->attribute('readings')->get();
	echo json_encode($readings);
})->name('/lg/get/readings');

$app->post("/lg/:lg_id/put/readings", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$readings = $lg->attribute('readings')->get();
	$data = json_decode($app->request->post('json'),true);
	// Check for bad readings
	foreach ($readings as $stored) {
		foreach ($data as $modified) {
			if ($stored["id"] === $modified["id"]) {
				if (($modified["resource_type"] == "Prescribed Textbook" || $modified["resource_type"] == "Essential Reading") && $stored["resource_type"] != $modified["resource_type"]) {
					echo "You modified the form and changed the Reading Type. Event Reported.";
					return;
				};
			}
		}
	}
	$success = $lg->attribute("readings")->save($data);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/readings');


// *******************************
// Activities
// *******************************
$app->get("/lg/:lg_id/get/activities", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	// Get unit activities
	$activities = [];
	$activities["data"] = $lg->attribute("activity_data")->get();
	$activities["col"] = $lg->attribute("activity_columns")->get();
	$activities["ass"] = $lg->attribute("activity_assessments")->get();
	$activities["ass_sum"] = $lg->attribute("assessment_summary")->get();
	$activities["dates"] = $lg->attribute("session_weeks")->get();
	echo json_encode($activities);
})->name('/lg/get/activities');

$app->post("/lg/:lg_id/put/activities", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$ass = json_decode($app->request->post('ass'),true);
	$activites = json_decode($app->request->post('activities'),true);
	$delete_id = json_decode($app->request->post('delete'),true);
	$success = true;
	$lg->attribute('activity_columns')->delete($delete_id);
	$success = $lg->attribute('activity_assessments')->save($ass);
	$success = $lg->attribute('activity_columns')->save($activites);
	// Send response
	if ($success) {
		echo "success";
	}
	$lg->attribute('core')->save_attribute('regenerate', '1');
})->name('/lg/put/activities');


// *******************************
// Checklist
// *******************************
$app->get("/lg/:lg_id/get/checklist", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$checklist = $lg->attribute('checklist')->get();
	echo json_encode($checklist);
})->name('/lg/get/checklist');

$app->post("/lg/:lg_id/put/checklist", function($lg_id) use ($app, $db) {
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$data = json_decode($app->request->post('json'),true);
	$success = $lg->attribute("checklist")->save($data);
	if ($data['approved']) {
		$lg->attribute('core')->save_attribute('state', 'Approved');
		$lg->attribute('core')->save_attribute('regenerate', '1');
	}
	// Send response
	if ($success) {
		echo "success";
	}
})->name('/lg/put/checklist');
