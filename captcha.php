<?php
session_start();
$code = strval(rand(1000, 9999));
$_SESSION['captcha_code'] = $code;
echo $code;
?>
