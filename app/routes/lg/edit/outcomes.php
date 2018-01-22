<?php

$app->get("/lg/:lg_id/edit/outcomes", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);
    $lg = new Learning_Guide($lg_id, $db);
	// Get unit details
	$core = $lg->attribute('core')->get();
    $outcomes = $lg->attribute('outcomes')->get();
    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/outcomes.twig", [
            'core' => $core,
            'outcomes' => $outcomes,
			'user' => $user,
		]);
    }
})->name('/lg/edit/outcomes');
