<?php

$app->get("/edit_session_dates", function() use ($app, $user, $constants, $users) {
	$user['navigation']->push(["Dates", $app->request->getResourceUri()]);

	$app->render("edit_session_dates.twig", [
		'user' => $user,
		'years' => $constants['years'],
		'sessions' => $constants['sessions'],
		'modes' => $constants['modes'],
		'users' => $users->get_all_users(),
		'daps' => $users->get_all_dap(),
	]);
})->name('/edit_session_dates');


$app->post("/edit_session_dates/save", function() use ($app, $db, $user, $users) {

	$weeks = json_decode($app->request->post('data'), true);
	$delete = json_decode($app->request->post('delete'), true);

	// Add new weeks
	if ($weeks != null) {
		foreach ($weeks as $week) {
			$stmt = null;
			if (!isset($week['id'])) {
				$week['id'] = null;
			}
			if (!isset($week['session'])) {
				$week['session'] = null;
			}
			if (!isset($week['year'])) {
				$week['year'] = null;
			}
			if (!isset($week['week'])) {
				$week['week'] = null;
			}
			if (!isset($week['week_date'])) {
				$week['week_date'] = null;
			}
			if (!isset($week['week_type'])) {
				$week['week_type'] = null;
			}
			if ($week['id'] == null || $week['id'] == "") {
				$stmt = $db->prepare("INSERT INTO session_dates (session, year, week, week_date, week_type) VALUES (?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $week['session']);
				$stmt->bindParam(2, $week['year']);
				$stmt->bindParam(3, $week['week']);
				$stmt->bindParam(4, $week['week_date']);
				$stmt->bindParam(5, $week['week_type']);

			} else {
				$stmt = $db->prepare("UPDATE session_dates SET session=?, year=?, week=?, week_date=?, week_type=? WHERE id=?");
				$stmt->bindParam(1, $week['session']);
				$stmt->bindParam(2, $week['year']);
				$stmt->bindParam(3, $week['week']);
				$stmt->bindParam(4, $week['week_date']);
				$stmt->bindParam(5, $week['week_type']);
				$stmt->bindParam(6, $week['id']);
			}
			if ($stmt->execute() === false) {
				echo "Failed to insert/update weeks!";
				return;
			}
		}
	}

	// Delete weeks
	if ($delete != null) {
		foreach ($delete as $id) {
			$stmt = $db->prepare("DELETE FROM session_dates WHERE id=?");
			$stmt->bindParam(1, $id);
			if ($stmt->execute() == false) {
				echo "Could not delete weeks!";
				return;
			}
		}
	}

	echo "success";
})->name('/edit_session_dates/save');;


$app->post("/edit_session_dates/get", function() use ($app, $db, $user, $users) {


	$year = $app->request->post('year');
	$session = $app->request->post('session');

	$data = null;
	$stmt = $db->prepare("SELECT * FROM session_dates WHERE year=? AND session=? ORDER BY week ASC, id ASC");
	$stmt->bindParam(1, $year);
	$stmt->bindParam(2, $session);
	if ($stmt->execute() != false) {
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	echo json_encode($data);

})->name('/edit_session_dates/get');
