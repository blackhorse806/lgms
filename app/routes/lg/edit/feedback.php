<?php

$app->get("/lg/:lg_id/edit/feedback", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();

	if ($core != null) {
		$app->render("lg/edit/feedback.twig", [
			'core' => $core,
			'user' => $user,
		]);
	}
})->name('/lg/edit/feedback');
