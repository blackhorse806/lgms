<?php

$app->get("/lg/list", function() use ($app, $db, $user, $users) {
	$user['navigation']->push(["List", $app->request->getResourceUri()]);
	$lgs = null;
	$dap_lgs = null;
    $acting_lgs = null;
    $acting_dap_name = null;

	// If admin or developer pull all lgs
	if ($user['admin'] || $user['developer']) {
		$stmt = $db->query("SELECT * FROM lg");
		if ($stmt != false) {
			$lgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

	// If unit coordinator or dap pull only their lgs
	if ($user['uc'] || $user['dap']) {
		$stmt = $db->prepare("SELECT * FROM lg WHERE editor_id=?");
		$stmt->bindParam(1, $user['id']);
		$stmt->execute();
		if ($stmt != false) {
			$lgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

	// If dap pull the lgs they are a reviewer for
	if ($user['dap']) {
		$stmt = $db->prepare("SELECT * FROM lg WHERE reviewer_id=?");
		$stmt->bindParam(1, $user['id']);
		$stmt->execute();
		if ($stmt != false) {
			$dap_lgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

    // If dap pull the lgs they are a reviewer for
	if ($user['acting_dap']) {
		$stmt = $db->prepare("SELECT * FROM lg WHERE reviewer_id=?");
		$stmt->bindParam(1, $user['acting_dap_id']);
		$stmt->execute();
		if ($stmt != false) {
			$acting_lgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
        $acting_dap_name = $users->get_user_name($user['acting_dap_id']);
	}

	// Render template with the data found above
	$app->render("lg/list.twig", [
		'lgs' => $lgs,
		'dap_lgs' => $dap_lgs,
        'acting_lgs' => $acting_lgs,
        'acting_dap_name' => $acting_dap_name,
		'user' => $user,
	]);

})->name('/lg/list');
