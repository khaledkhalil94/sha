<?php
require_once ($_SERVER["DOCUMENT_ROOT"] . "/sha/classes/init.php");


$session->is_logged_in() ? require(ROOT_PATH . "questions/question_user.php") : require(ROOT_PATH . "questions/question_pub.php");
?>
	<script src="scripts/ajax.js"></script>