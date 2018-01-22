<?php

$app->post("/lg/:lg_id/set/dap", function($lg_id) use ($app, $db, $users) {
	// Get data from post
	$id = $app->request->post('dap');
	// Ssave sttribute
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->save_attribute('reviewer_id', $id);
	$name = $users->get_user_name($id);
	$core = $lg->attribute('core')->save_attribute('reviewer', $name);
	// Redirect page
	$app->redirect($app->urlFor('/lg', ['lg_id' => $lg_id]));
})->name('/lg/set/dap');
