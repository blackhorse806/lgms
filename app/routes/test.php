<?php

$app->get("/test", function() use ($app, $db) {
	// Get unit details
	$unit = new Unit("300666", $db);
	$unit_details = $unit->get_unit_details();
	$clos = $unit->get_clos();

	//var_dump($clos);

	if ($unit_details != null) {
		$app->render("test.twig", ["clos" => $clos, "unit_details" => $unit_details ]);
	}
})->name('/test');
