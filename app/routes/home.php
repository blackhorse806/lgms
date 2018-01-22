<?php

$app->get("/home", function() use ($app, $user) {
	$user['navigation']->push(["Home", $app->request->getResourceUri()]);
	$app->render("home.twig", [
		'user' => $user,
	]);
})->name('/home');
