<?php

$app->get("/lg/:lg_id/edit/assessments", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);

	$lg = new Learning_Guide($lg_id, $db);

	// Get unit details
	$core = $lg->attribute('core')->get();

    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/assessments.twig", [
            'core' => $core,
			'user' => $user,
		]);
    }

})->name('/lg/edit/assessments');
