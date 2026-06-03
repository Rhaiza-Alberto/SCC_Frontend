<?php
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$redirect_url = "verify_user.php";
if ($email || $token) {
    $redirect_url .= "?";
    if ($email) $redirect_url .= "email=" . urlencode($email);
    if ($token) $redirect_url .= ($email ? "&" : "") . "token=" . urlencode($token);
}
header("Location: $redirect_url");
exit();
?>
