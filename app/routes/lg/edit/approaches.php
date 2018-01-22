<?php

$app->get("/lg/:lg_id/edit/approaches", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);
	$unit = new Learning_Guide($lg_id, $db);
	// Get unit details
	$core = $unit->attribute('core')->get();
    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/approaches.twig", [
            'core' => $core,
			'user' => $user,
		]);
    }
})->name('/lg/edit/approaches');
