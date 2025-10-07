<?php
echo "Testing PDO: ";
try {
    $pdo = new PDO('pgsql:host=postgres;dbname=chronos_db;user=chronos_user;password=chronos_pass');
    echo "Connected successfully!\n";

    $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE cedula = ?");
    $stmt->execute(['12345678']);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "Admin user exists. Updating password...\n";
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuario SET contrasena_hash = ? WHERE cedula = ?");
        $result = $stmt->execute([$passwordHash, '12345678']);
        
        if ($result) {
            echo "Admin user password updated successfully!\n";
            echo "CI: 12345678\n";
            echo "Password: password\n";
            echo "Role: ADMIN\n";
        } else {
            echo "Failed to update admin user password.\n";
        }
    } else {
        echo "Admin user does not exist. Creating...\n";
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);
        
        $pdo->beginTransaction();
        
        try {

            $stmt = $pdo->prepare("INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['12345678', 'Administrador', 'Sistema', 'admin@chronos.edu.uy', '099123456', $passwordHash]);
            
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (?, ?)");
            $stmt->execute([$userId, 'ADMIN']);
            
            $pdo->commit();
            
            echo "Admin user created successfully!\n";
            echo "CI: 12345678\n";
            echo "Password: password\n";
            echo "Role: ADMIN\n";
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
