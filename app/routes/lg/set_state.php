<?php

$app->post("/lg/:lg_id/set/state", function($lg_id) use ($app, $db) {
	// Get data from post
	$state = $app->request->post('state');
	// Ssave sttribute
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->save_attribute('state', $state);
    $lg->attribute('core')->save_attribute('regenerate', '1');
	// Redirect page
	$app->redirect($app->urlFor('/lg', ['lg_id' => $lg_id]));
})->name('/lg/set/state');
