<?php

require '../vendor/autoload.php';
require 'lib/LearningGuide.php';
require 'lib/Template.php';
require 'lib/Navigation.php';
require 'lib/Variation.php';
require 'lib/Import.php';
require 'lib/Users.php';

// Define constants
$constants = [];
$constants['states'] = ["Stateless", "Scheduled", "Amend", "ReviewReady", "Approved", "Published"];
$constants['modes'] = ["Online", "Composite", "External", "Internal"];
$constants['years'] = ["2017", "2018", "2019", "2020", "2021", "2022", "2023"];
$constants['sessions'] = ["Autumn","Spring","1H","2H","Quarter 1","Quarter 2","Quarter 3","Quarter 4","Term 1","Term 2","Term 3","Summer A","Summer B"];
$constants['levels'] = ["Foundation Studies and Preparatory units (The College) Level Z", "Undergraduate Level 1", "Undergraduate Level 2", "Undergraduate Level 3", "Undergraduate Level 4", "Undergraduate Honours Level 5", "Postgraduate Level 7"];


// Start session
session_start();
$user = null;
if (isset($_SESSION)) {
	$user = [];
	$user['id'] = '';
	$user['email'] = '';
	$user['name'] = '';
	$user['observer'] = '';
	$user['uc'] = '';
	$user['dap'] = '';
	$user['admin'] = '';
	$user['developer'] = '';
	$user["acting_dap"] = '';
	$user["acting_dap_id"] = '';
	$user['search'] = '';
	$user['results'] = '';
    $user['navigation'] = null;

	foreach ($_SESSION as $key => $value) {
		if (isset($user[$key])) {
			$user[$key] = $value;
		}
	}

    if (isset($_SESSION['navigation'])) {
        $user['navigation'] = $_SESSION['navigation'];
    }
}

// Configure the template directory and the template parser.
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig(),
    'templates.path' => '../app/views'
));

// Set the view to use Twig templates
$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    //'cache' => dirname(__FILE__) . '/cache'
);
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

// Load the ini config file
$app->config = parse_ini_file("../app/config.ini");

// Connect to database
$db = new PDO(
	"mysql:host=" . $app->config['db_host'] .
	";dbname=" . $app->config['db_name'],
	$app->config['db_user'],
	$app->config['db_pass'],
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
);

// Connect to database
$cams_db = new PDO(
	"mysql:host=localhost;dbname=cams", "root", "",
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
);

// Setup users class
$users = new Users($db);

// Redirect all traffic for no session to home page
if (!isset($user['navigation']) || $user['navigation'] == null) {
	require('../app/routes/login.php');
	// Catch all traffic
	$app->get("/:stuff+", function($stuff) use ($app) {
		$app->redirect($app->urlFor("/"));
	});
	// Keep for urlFor in login
	$app->post("/home", function() use ($app) {
	})->name('/home');
    $app->post("/lg/list", function() use ($app) {
	})->name('/lg/list');
} else {
	// Routes file
	require('../app/routes.php');
}

// Run the application.
$app->run();
