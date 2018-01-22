<?php

$app->get("/lg/:lg_id/edit/assessment_summary", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);

	$lg = new Learning_Guide($lg_id, $db);

	// Get unit details
	$core = $lg->attribute('core')->get();
	$assessments = $lg->attribute('assessment_summary')->get();

    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/assessment_summary.twig", [
            'core' => $core,
            'assessments' => $assessments,
			'user' => $user,
		]);
    }
})->name('/lg/edit/assessment_summary');
