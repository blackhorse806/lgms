<?php

$app->get("/lg/:lg_id/merge_old", function($lg_id) use ($app, $db, $users) {
	$lg = new Learning_Guide($lg_id, $db);
	$lg->import_from_old();
	$lg = new Learning_Guide($lg_id, $db);
	$core = $lg->attribute('core')->get();
	$lg->attribute('core')->save_attribute('reviewer_id', $users->get_user_id($core['reviewer']));
	// Redirect page
	$app->redirect($app->urlFor('/lg', ['lg_id' => $lg_id]));
})->name('/lg/merge_old');
