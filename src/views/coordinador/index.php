<?php
/**
 * Redirección al dashboard principal del coordinador
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';

initSecureSession();

AuthHelper::requireRole('COORDINADOR');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

header("Location: dashboard.php");
exit();
?>

