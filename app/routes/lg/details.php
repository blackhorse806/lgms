<?php

$app->get("/lg/:lg_id/details", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Details", $app->request->getResourceUri()]);
	// Get unit details
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();
	$staff = $lg->attribute("staff")->get();
	$modes = $lg->attribute("modes_of_delivery")->get();
	$outcomes = $lg->attribute("outcomes")->get();
	$readings = $lg->attribute("readings")->get();
	$assessments = $lg->attribute("assessments")->get();
	$marking_criteria = $lg->attribute("marking_criteria")->get();

	if ($core != null) {
		$app->render("lg/details.twig", [
			'core' => $core,
			'staff' => $staff,
			'modes' => $modes,
			'outcomes' => $outcomes,
			'readings' => $readings,
			'assessments' => $assessments,
			'marking_criteria' => $marking_criteria,
			'user' => $user,
		]);
	}
})->name('/lg/details');
