<?php
header("Location: login.php");
exit();

$host = getenv('POSTGRES_HOST');
$db   = getenv('POSTGRES_DB');
$user = getenv('POSTGRES_USER');
$pass = getenv('POSTGRES_PASSWORD');

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg = "✅ Conexión a PostgreSQL exitosa";
} catch (PDOException $e) {
    $msg = "❌ Error: " . $e->getMessage();
}
?><!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chronos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-slate-100 flex items-center justify-center h-screen">
    <div class="p-8 bg-white rounded shadow text-center">
        <h1 class="text-3xl font-bold text-indigo-600 mb-2">Chronos</h1>
        <p class="text-lg text-gray-700 mb-6"><?= htmlspecialchars($msg) ?></p>
        <a href="login.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 ease-in-out transform hover:scale-105">
            Ir al Login
        </a>
    </div>
</body>
</html>
