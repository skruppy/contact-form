<?php

declare(strict_types=1);

if (@!include __DIR__ . '/vendor/autoload.php') {
	echo 'Install Latte using `composer install`';
	exit(1);
}

// error_reporting(E_ALL);
// ini_set('display_errors', 'On');

use ContactForm\ContactForm;

$config = include __DIR__ . '/config.php';
$contactForm = new ContactForm($config);
$contactForm->render();
