<?php

$app->get("/lg/:lg_id/edit/staff", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);
	$lg = new Learning_Guide($lg_id, $db);

	$core = $lg->attribute('core')->get();

    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/staff.twig", [
            'core' => $core,
			'user' => $user,
		]);
    }
})->name('/lg/edit/staff');
