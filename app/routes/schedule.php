<?php

$app->get("/schedule", function() use ($app, $user, $constants, $users) {
	$user['navigation']->push(["Schedule", $app->request->getResourceUri()]);
	$app->render("schedule.twig", [
		'user' => $user,
		'years' => $constants['years'],
		'sessions' => $constants['sessions'],
		'modes' => $constants['modes'],
		'users' => $users->get_all_users(),
	]);
})->name('/schedule');


$app->post("/schedule", function() use ($app, $user, $db, $cams_db, $constants) {
	$user['navigation']->pop();
	$session = $app->request->post('session');
	$year = $app->request->post('year');
	$mode = $app->request->post('mode');
	$unit_code = $app->request->post('unit_code');
	$uc = $app->request->post('uc');
	$import = new Import($unit_code, $year, $session, $mode, $uc, $db, $cams_db);
	$app->redirect($app->urlFor('/home'));
});
