<?php

$app->get("/lg/:lg_id/generate", function($lg_id) use ($app, $db) {
	$lg = new Learning_Guide($lg_id, $db);
	$lg->generate();
	//echo "success";
})->name('/lg/generate');
