<?php

$app->get("/lg/:lg_id/edit/clo", function($lg_id) use ($app, $db, $user) {
	$user['navigation']->push(["Edit", $app->request->getResourceUri()]);

	$lg = new Learning_Guide($lg_id, $db);

	// Get unit details
	$core = $lg->attribute('core')->get();
	// Get unit outcomes
	$outcomes = $lg->attribute('outcomes')->get();
	$courses = $lg->attribute('courses')->get();
	$all_courses = $lg->attribute('all_courses')->get();

    // Check that the unit exists
    if ($core != null) {
        $app->render("lg/edit/clo.twig", [
            'core' => $core,
            'outcomes' => $outcomes,
			'courses' => $courses,
			'all_courses' => $all_courses,
			'user' => $user,
		]);
    }
})->name('/lg/edit/clo');
