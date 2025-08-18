<?php
require_once('../../helpers/startSession.php');
startRoleSession('admin'); // huỷ session của admin

session_unset();
session_destroy();

header("Location: ../../login/login.php");
exit();
