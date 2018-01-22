<?php

$app->post("/lg/:lg_id/email/request_modification", function($lg_id) use ($app, $db, $user, $users) {
	// Get data from POST
	$message = $app->request->post('message');
	// Save attribute
	$lg = new Learning_Guide($lg_id, $db);
	// Get core details
	$core = $lg->attribute('core')->get();
	// Get unit coordinators email
	$email = $users->get_email($core['editor_id']);
	// Create the email body
	$body = "";
	$body .= "Unit Code: {$core['unit_code']}<br>";
	$body .= "Unit Name: {$core['unit_name']}<br>";
	$body .= "Unit Coordinator: {$core['editor']}<br>";
	$body .= "Coordinator Email: {$email}<br><br>";
	$body .= "Submitted by: {$user['name']}<br><br>";
	$body .= "<b>Message:</b><br>{$message}";
	// Create the email
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = 'mail.scem.uws.edu.au;mail.scem.uws.edu.au';
	$mail->Port = 25;
	$mail->setFrom('scem_lg@westernsydney.edu.au', 'Learning Guide Support');
	$mail->addAddress('scem_lg@westernsydney.edu.au');
	$mail->isHTML(true);
	$mail->Subject = "Data Modification Request: ID " . $core['lg_id'] . " Unit Code " . $core['unit_code'];
	$mail->Body = $body;
	// Try to send email
	if(!$mail->send()) {
		echo "Message could not be sent.\n";
		echo "Mailer Error: " . $mail->ErrorInfo . "\n";
	} else {
		echo "success";
	}
})->name('/lg/email/request_modification');


$app->post("/lg/:lg_id/email/submit_for_review", function($lg_id) use ($app, $db, $users) {
	// Save attribute
	$lg = new Learning_Guide($lg_id, $db);
	// Get core details
	$core = $lg->attribute('core')->get();
	if ($core['reviewer'] == null || $core['reviewer'] == "") {
		echo "No reviewer set for the Learning Guide!\nCannot send email!";
		return;
	}
	// Get unit coordinators email
	$email = $users->get_email($core['reviewer_id']);
	// Send email
	$lg->attribute('core')->save_attribute('state', 'ReviewReady');
	// Create the email body
	$body = "";
	$body .= "Hi {$core['reviewer']},<br><br>";
	$body .= "The following unit is ready to be reviewed<br><br>";
	$body .= "Unit code: {$core['unit_code']}<br><br>";
	$body .= "Unit name: {$core['unit_name']}<br><br>";
	$body .= "<a href='http://lgms.scem.uws.edu.au'>http://lgms.scem.uws.edu.au</a>";
	// Create the email
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = 'mail.scem.uws.edu.au;mail.scem.uws.edu.au';
	$mail->Port = 25;
	$mail->setFrom('scem_lg@westernsydney.edu.au', 'Learning Guide Support');
	$mail->addAddress($email);
	$mail->isHTML(true);
	$mail->Subject = "Unit Review Ready: ID " . $core['lg_id'] . " Unit Code " . $core['unit_code'];
	$mail->Body = $body;
	// Try to send email
	if(!$mail->send()) {
		echo "Message could not be sent.\n";
		echo "Mailer Error: " . $mail->ErrorInfo . "\n";
	} else {
		echo "success";
	}
})->name('/lg/email/submit_for_review');


$app->post("/lg/:lg_id/email/publish", function($lg_id) use ($app, $db, $users) {
	// Save attribute
	$lg = new Learning_Guide($lg_id, $db);
	// Get core details
	$core = $lg->attribute('core')->get();
	// Get unit coordinators email
	$email = $users->get_email($core['editor_id']);

	// Change state
	$lg->attribute('core')->save_attribute('state', 'Published');

	// Generate learning guide
	$lg->attribute('core')->save_attribute('regenerate', '1');
	$lg->attribute('core')->clear_data();
	$lg->generate();

	// Check generation created the PDF
	if (!file_exists("C:\\Apache24\\lgms\\lg\\published\\" . $core['year'] . "\\" . $core['session'] . "\\" . $core['lg_id'] . ".pdf")) {
		echo "Generation failed!\nNo emails were sent.";
		// Change state
		$lg->attribute('core')->save_attribute('state', 'Approved');
		return;
	}

	// Send emails
	// Unit Coordinator email
	// Create the email body
	$body = "";
	$body .= "Hi {$core['editor']},<br><br>";
	$body .= "Please find attached your approved Learning Guide for the upcoming session.";
	$body .= "Remember you need to upload and ensure that this version of your Learning Guide is available to Students on your unit's vUWS site.<br><br>";
	$body .= "Unit code: {$core['unit_code']}<br><br>";
	$body .= "Unit name: {$core['unit_name']}<br><br>";
	$body .= "<a href='http://lgms.scem.uws.edu.au'>http://lgms.scem.uws.edu.au</a>";
	// Create the email
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = 'mail.scem.uws.edu.au;mail.scem.uws.edu.au';
	$mail->Port = 25;
	$mail->setFrom('scem_lg@westernsydney.edu.au', 'Learning Guide Support');
	$mail->addAddress($email);
	//$mail->addAddress('scem_lg@westernsydney.edu.au');
	$mail->isHTML(true);
	$mail->Subject = "Learning Guide " . $core['unit_code'] . " " . $core['session'] . " " . $core['year'];
	$mail->Body = $body;
	$mail->addAttachment("../lg/published/" . $core['year'] . "/" . $core['session'] . "/" . $core['lg_id'] . ".pdf");
	// Try to send email
	if(!$mail->send()) {
		echo "Message could not be sent to Unit Coordinator.\n";
		echo "Mailer Error: " . $mail->ErrorInfo . "\n";
		$lg->attribute('core')->save_attribute('state', 'Approved');
		return;
	}

	// Email BLADE, TRIM, Library
	$body = ""; //"This is a test of the SCEM LG system. Please call me when received.<br>Thomas Nixon.<br><br>";
	$body .= "To whom it may concern,<br><br>";
 	$body .= "Please find attached the Approved Learning Guide for the upcoming session for the below mentioned unit. This can now be added to your respective repositories.<br><br>";
 	$body .= "Unit code: {$core['unit_code']}<br>";
	$body .= "Unit name: {$core['unit_name']}<br><br>";
	$body .= "School of Computing, Engineering & Mathematics";
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = 'mail.scem.uws.edu.au;mail.scem.uws.edu.au';
	$mail->Port = 25;
	$mail->setFrom('scem_lg@westernsydney.edu.au', 'SCEM Learning Guide Support');
	// $mail->addAddress("t.nixon@westernsydney.edu.au");
	$mail->addAddress("libresources@westernsydney.edu.au");
	$mail->addAddress("scem_lg@westernsydney.edu.au");
	$mail->addAddress("blended-learning@scem.westernsydney.edu.au");
	$mail->addAddress("Archives@westernsydney.edu.au");
	$mail->addAddress($users->get_email($core['reviewer_id']));
	$mail->addAttachment("../lg/published/" . $core['year'] . "/" . $core['session'] . "/" . $core['lg_id'] . ".pdf");
	$mail->isHTML(true);
	$mail->Subject = "Learning Guide " . $core['unit_code'] . " " . $core['session'] . " " . $core['year'];
	$mail->Body = $body;
	// Try to send email
	if(!$mail->send()) {
		echo "Message could not be sent to external services.\n";
		echo "Mailer Error: " . $mail->ErrorInfo . "\n";
		$lg->attribute('core')->save_attribute('state', 'Approved');
		return;
	}
	echo "success";
})->name('/lg/email/publish');


$app->post("/lg/:lg_id/email/checklist", function($lg_id) use ($app, $db, $users) {
	// Save attribute
	$lg = new Learning_Guide($lg_id, $db);
	// Get core details
	$core = $lg->attribute('core')->get();
	$checks = $lg->attribute('checklist')->get();
	// Get unit coordinators email
	$email = $users->get_email($core['editor_id']);
	// Send email
	$lg->attribute('core')->save_attribute('state', 'Amend');
	// Prepare checklist
	$chk = "&#x2713;";
	$checklist = [
		["0", "Staff Information"],
		["1", "Units coordinators name and associated details (including consultation arrangements)"],
		["2", "Teaching Staff and associated details (including consultation arrangements)"],
		["0", "1.3 Changes to Unit as a Result of Past Student Feedback"],
		["3", "Ensure that there is a quality responses to 'Actions taken to improve the unit as a result of student feedback'. This section can be left blank, however consistence change to improve the unit as a result of student feedback is expected"],
		["4", "Make sure no extra bullet points are included, as this is bulleted automatically"],
		["0", "2.1 Unit Learning Outcomes"],
		["5", "Check that the Unit Learning Outcomes have not be listed in the Introduction to Learning Outcomes section"],
		["0", "2.2 Approach to Learning"],
		["6", "Check that each approach item has been provided some information (vUWS can be left blank)"],
		["0", "2.3 Contribution to Course Learning Outcomes"],
		["7", "Contribution to Course Learning Outcomes (CLOs) OR Graduate Attributes table is entered"],
		["8", "The correct courses are included (service units require the Graduate Attributes)"],
		["0", "2.4 Assessment Summary"],
		["9", "Ensure that the 'To pass this unit you must' is comprehensive and does not include anything unusual.  (Make sure they have not entered 'To pass this unit you must')"],
		["10", "Ensure that the 'Feedback on Assessments' section has been completed and provided the student satisfactory information on when they are expected to receive feedback and also what type of feedback"],
		["0", "2.5 Assessment Details"],
		["11", "Due date is entered"],
		["12", "Collaboration type is selected"],
		["13", "Submission information is clear and not confusing"],
		["14", "Format information is clear"],
		["15", "Curriculum Mode is selected"],
		["16", "Threshold detail is filled out when required"],
		["17", "Assessment Instructions have been filled out and ensure that it is both clear and concise"],
		["18", "Assessment Resource information is clear and directive (can be left blank)"],
		["19", "Marking criteria and standards are clear and concise"],
		["0", "3 Activities"],
		["20", "Assessment items are selected for the weeks they are due"],
		["21", "Ensure enough information is provided within each activity type, some can be left blank"],
		["0", "4 Learning Resources"],
		["22", "Ensure all learning resources  are referenced correctly"],
	];
	// Create the email body
	$body = "";
	$body .= "<div style='font-family: Calibri;'>";
	$body .= "Hi {$core['editor']},<br><br>";
	$body .= "The unit {$core['unit_name']} did not pass the quality assurance & peer review. Please see below for detail.<br><br>";
	$body .= "Please note that the <i>Pass</i> coloumn represents whether the checklist item has passed or not.<br><br>";
	$body .= "Once your changes have been complete, please resubmit your Learning Guide for Review.<br>";
	$body .= "<br><b>Comments from Reviewer({$core['reviewer']}):</b><br>";
	$body .= $checks["comments"];
	$body .= "<br><br></div>";
	$body .= "<table border='1' width='700px' style='font-size: 12px; border-collapse: collapse; font-family: Calibri;'>";
	$body .= "<tr><th>Checklist Item</th><th>Pass</th></tr>";
	// Output the checklist table
	foreach ($checklist as $c) {
		// Check if row is heading or checklist item
		if ($c[0] == "0") {
			$body .= "<tr><td colspan='2'><b>" . $c[1] . "</b></td></tr>";
		} else {
			// Find out if the item is checked
			$checked = false;
			if (isset($checks["x" . strval($c[0])]) && $checks["x" . strval($c[0])] == "1") {
				$checked = true;
			}
			// Change formatting of row dpending on check
			if ($checked) {
				$body .= "<tr style='background-color: #CCFFCC'>";
			} else {
				$body .= "<tr style='background-color: #fbc97f'>";
			}
			// Add the check or cross
			$body .= "<td>" . $c[1] . "</td><td style='text-align: center;'>";
			if ($checks != null) {
				if ($checked) {
					$body .= $chk;
				} else {
					$body .= "<b>X</b>";
				}
			}
			// End the row
			$body .= "</td></tr>";
		}
	}
	$body .= "</table>";
	$body .= "<div style='font-size: 11px; font-family: Calibri;'>";
	$body .= $chk . " = Passed (The checklist item is deemed satisfactory, no change required)<br>";
	$body .= "X  = Not Passed (The checklist item shows that something needs to be changed, refer to reviewer comments)<br>";
	$body .= "</div>";
	$body .= "<a href='http://lgms.scem.uws.edu.au'>http://lgms.scem.uws.edu.au</a>";
	// Create the email
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = 'mail.scem.uws.edu.au;mail.scem.uws.edu.au';
	$mail->Port = 25;
	$mail->setFrom('scem_lg@westernsydney.edu.au', 'Learning Guide Support');
	$mail->addAddress($email);
	$mail->isHTML(true);
	$mail->Subject = "Learning Guide " . $core['unit_code'] . " Unapproved";
	$mail->Body = $body;
	// Try to send email
	if(!$mail->send()) {
		echo "Message could not be sent.\n";
		echo "Mailer Error: " . $mail->ErrorInfo . "\n";
	} else {
		echo "success";
	}
})->name('/lg/email/checklist');
