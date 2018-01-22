<?php

$app->get("/lg/:lg_id", function($lg_id) use ($app, $db, $users, $user) {
	$user['navigation']->push(["Dash", $app->request->getResourceUri()]);
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();

	$all_users = $users->get_all_users();

	if ($core != null) {
		$app->render("lg/dash.twig", [
			'core' => $core,
			'user' => $user,
			'users' => $all_users,
		]);
	}
})->name('/lg');
