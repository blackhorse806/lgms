<?php

$app->get("/statistics", function() use ($app, $db, $user, $constants, $users) {
	// Add navigation button
	$user['navigation']->push(["Statistics", $app->request->getResourceUri()]);

	// Set the year and session to pull statistics
	$session = 'Autumn';
	$year = '2017';

	// Get list of daps
	$stmt = $db->query("SELECT * FROM user WHERE dap = '1'");
	$daps = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Prepare to capture totals for each state
	$totals = [];
	foreach ($constants['states'] as $state) {
		$totals[$state] = 0;
	}

	// Create data to pass to front end
	$data = [];
	// Loop over each dap and count number of lgs in a specific state
	foreach ($daps as $dap) {
		$row = [];
		$row['name'] = $dap['name'];
		// Loop over each state
		foreach ($constants['states'] as $state) {
			$count = 0;
			$stmt = $db->prepare("SELECT count(*) FROM lg WHERE reviewer_id=? AND session=? AND year=? AND state=?");
			$stmt->bindParam(1, $dap['id']);
			$stmt->bindParam(2, $session);
			$stmt->bindParam(3, $year);
			$stmt->bindParam(4, $state);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			$count = $result['count(*)'];
			$row[$state] = $count;
			// Add state count to totals
			$totals[$state] += $count;
		}
		// Add dap and counts for each state to data
		$data[] = $row;
	}

	// Render page with data
	$app->render("statistics.twig", [
		'user' => $user,
		'data' => $data,
		'year' => $year,
		'session' => $session,
		'totals' => $totals,
	]);
})->name('/statistics');
