<?php
	include_once('./ContactFormSubmit.php');
	$submitClass = new ContactFormSubmit();
	if(!$submitClass->submit($_POST)) die(json_encode($submitClass->errors));


	die(json_encode(['error' => false]));
?>