<?php

$app->get("/lg/:lg_id/delete", function($lg_id) use ($app, $db) {
	$lg = new Learning_Guide($lg_id, $db);
	$lg->delete();
	$app->redirect($app->urlFor("/lg/list"));
})->name('/lg/delete');
