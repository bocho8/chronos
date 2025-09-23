<?php
/**
 * Redirección al dashboard principal del padre
 */

// Include required files
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';

// Initialize secure session first
initSecureSession();

// Require authentication and padre role
AuthHelper::requireRole('PADRE');

// Check session timeout
if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

// Redirect to dashboard
header("Location: dashboard.php");
exit();
?>

