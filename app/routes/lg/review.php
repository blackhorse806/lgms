<?php

$app->get("/lg/:lg_id/review", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Review", $app->request->getResourceUri()]);
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$lg->generate();
	$core = $lg->attribute('core')->get();
	$checklist = $lg->attribute('checklist')->get();

	if ($core != null) {
		$app->render("lg/review.twig", [
			'core' => $core,
			'checklist' => $checklist,
			'user' => $user,
		]);
	}
})->name('/lg/review');
