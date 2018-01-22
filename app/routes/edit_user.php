<?php

$app->get("/edit_user", function() use ($app, $user, $constants, $users) {
	$user['navigation']->push(["Schedule", $app->request->getResourceUri()]);
	$app->render("edit_user.twig", [
		'user' => $user,
		'years' => $constants['years'],
		'sessions' => $constants['sessions'],
		'modes' => $constants['modes'],
		'users' => $users->get_all_users(),
		'daps' => $users->get_all_dap(),
	]);
})->name('/edit_user');


$app->post("/edit_user/save", function() use ($app, $user, $users) {
	// Get data from post
	$user_details = json_decode($app->request->post('data'), true);
	// Save data and display result
    if ($users->update_user($user_details)) {
		echo "success";
	} else {
		echo "Save failed!";
	}
})->name('/edit_user/save');;
