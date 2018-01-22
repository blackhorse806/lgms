<?php

$app->get("/", function() use ($app) {
	$app->render("login.twig", [
		'login' => true,
	]);
})->name('/');

$app->post("/login", function() use ($app, $db) {
	// Define $username and $password
	$username = $app->request->post('username');
	$password = $app->request->post('password');

	// To protect MySQL injection for Security purpose
	$username = stripslashes($username);
	$password = stripslashes($password);
	//$username = mysqli_real_escape_string($username);
	//$password = mysqli_real_escape_string($password);

	// Authenticate with LDAP here
	$ldapserver = 'ad.uws.edu.au';
	$domain = '@ad.uws.edu.au';
	$base_dn = 'ou=Staff,ou=people,DC=AD,DC=UWS,DC=EDU,DC=AU';

	//$ldap_conn = ldap_connect($ldapserver);
	//ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	//ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

	//$userfound = @ldap_bind($ldap_conn, $username . $domain, $password);
	$userfound = true;

	if ($userfound == true) {
		// retrieve user details from DB
		$stmt = $db->prepare("SELECT * FROM user WHERE id=?");
		$stmt->bindParam(1, $username);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		$_SESSION['id'] = $result["id"];
		$_SESSION['email'] = $result["email"];
		$_SESSION['name'] = $result["name"];
		$_SESSION['observer'] = $result["observer"];
		$_SESSION['uc'] = $result["uc"];
		$_SESSION['dap'] = $result["dap"];
		$_SESSION['admin'] = $result["admin"];
		$_SESSION['developer'] = $result["developer"];
		$_SESSION["acting_dap"] = $result["acting_dap"];
		$_SESSION["acting_dap_id"] = $result["acting_dap_id"];
    $_SESSION['navigation'] = new Navigation;

		//ActionLog($conn, "Login", "User_type: " . $_SESSION["type"]);
        if ($_SESSION['admin'] || $_SESSION['developer']) {
            $app->redirect($app->urlFor('/home'));
        } else {
            $app->redirect($app->urlFor('/lg/list'));
        }

	} else {
		$error = "Username or Password is invalid";
		$app->redirect($app->urlFor('/'));
		//ActionLog($conn, "Login Failed", "username: " . $username . " IP: " . $_SERVER['REMOTE_ADDR']);
	}

})->name('/login');


$app->get("/logout", function() use ($app) {
    session_destroy();
    $app->response->redirect($app->urlFor('/'));
})->name('/logout');


function UpdateUserLoggedIn($staff_id) {
	// global $conn;
	// $datetime = new DateTime(date("Y-m-d H:i:s"));
	// $datetime->setTimezone(new DateTimeZone('Australia/Sydney'));
	// $sql = "UPDATE staff SET " .
	// 			"logged_in='" . $conn->real_escape_string($datetime->format('Y-m-d H:i:s')) . "' " .
	// 			"WHERE id='" . $conn->real_escape_string($staff_id) . "'";
	// $conn->query($sql);
}
