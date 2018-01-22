<?php

$app->get("/lg/:lg_id/edit/marking_criteria", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);
	$lg = new Learning_Guide($lg_id, $db);
	// Get unit details
	$core = $lg->attribute('core')->get();
	// Get unit marking_criteria
	$marking_criteria = $lg->attribute('marking_criteria')->get();

    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/marking_criteria.twig", [
            'core' => $core,
            'marking_criteria' => $marking_criteria,
			'user' => $user,
		]);
    }
})->name('/lg/edit/marking_criteria');
