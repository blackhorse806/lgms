<?php

$app->get("/lg/:lg_id/view_pdf", function($lg_id) use ($app, $db) {

	//header("Content-length: " . filesize('config.ini'));
	//header("Content-Disposition: attachment; filename=\"{$_GET['name']}\" ");
	//header("Content-Disposition: attachment; filename=test.pdf");
	//header('Content-type: application/zip');
	@apache_setenv('no-gzip', 1);
	header("Content-type: application/pdf");
	header("Content-Disposition: inline; filename=\"{$lg_id}.pdf\"");
	header('Content-Transfer-Encoding: binary');
	header("Content-length: " . filesize("../lg/generation/{$lg_id}.pdf"));
	header('Accept-Ranges: bytes');

	// add these two lines
	ob_clean();   // discard any data in the output buffer (if possible)
	flush();      // flush headers (if possible)

	readfile("../lg/generation/{$lg_id}.pdf");
	exit();

})->name('/lg/view_pdf');
