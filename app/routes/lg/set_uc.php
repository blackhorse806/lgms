<?php

$app->post("/lg/:lg_id/set/uc", function($lg_id) use ($app, $db, $users) {
	// Get data from post
	$id = $app->request->post('uc');
	// Ssave sttribute
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->save_attribute('editor_id', $id);
	$name = $users->get_user_name($id);
	$core = $lg->attribute('core')->save_attribute('editor', $name);
	// Redirect page
	$app->redirect($app->urlFor('/lg', ['lg_id' => $lg_id]));
})->name('/lg/set/uc');
