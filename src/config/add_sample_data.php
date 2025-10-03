<?php
/**
 * Script para agregar datos de ejemplo a la base de datos
 * Ejecutar desde la línea de comandos: php src/config/add_sample_data.php
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/Database.php';

try {
    $dbConfig = require __DIR__ . '/database.php';
    $database = new Database($dbConfig);
    $pdo = $database->getConnection();
    
    echo "Conectando a la base de datos...\n";
    
    // Insertar pautas ANEP
    $pautas = [
        ['nombre' => 'Pauta General', 'dias_minimos' => 1, 'dias_maximos' => 5, 'condiciones_especiales' => 'Distribución estándar'],
        ['nombre' => 'Educación Física', 'dias_minimos' => 2, 'dias_maximos' => 3, 'condiciones_especiales' => 'Horarios finales del turno'],
        ['nombre' => 'Programa Italiano', 'dias_minimos' => 1, 'dias_maximos' => 2, 'condiciones_especiales' => 'Al final del horario en grupos mixtos']
    ];
    
    foreach ($pautas as $pauta) {
        $stmt = $pdo->prepare("INSERT INTO pauta_anep (nombre, dias_minimos, dias_maximos, condiciones_especiales) VALUES (?, ?, ?, ?)");
        $stmt->execute([$pauta['nombre'], $pauta['dias_minimos'], $pauta['dias_maximos'], $pauta['condiciones_especiales']]);
    }
    echo "Pautas ANEP insertadas.\n";
    
    // Insertar grupos
    $grupos = [
        ['nombre' => '1A', 'nivel' => 'Primer Año'],
        ['nombre' => '1B', 'nivel' => 'Primer Año'],
        ['nombre' => '2A', 'nivel' => 'Segundo Año'],
        ['nombre' => '2B', 'nivel' => 'Segundo Año'],
        ['nombre' => '3A', 'nivel' => 'Tercer Año'],
        ['nombre' => '3B', 'nivel' => 'Tercer Año']
    ];
    
    foreach ($grupos as $grupo) {
        $stmt = $pdo->prepare("INSERT INTO grupo (nombre, nivel) VALUES (?, ?)");
        $stmt->execute([$grupo['nombre'], $grupo['nivel']]);
    }
    echo "Grupos insertados.\n";
    
    // Insertar materias
    $materias = [
        ['nombre' => 'Matemáticas', 'horas_semanales' => 4, 'id_pauta_anep' => 1, 'en_conjunto' => false, 'es_programa_italiano' => false],
        ['nombre' => 'Lengua Española', 'horas_semanales' => 3, 'id_pauta_anep' => 1, 'en_conjunto' => false, 'es_programa_italiano' => false],
        ['nombre' => 'Historia', 'horas_semanales' => 2, 'id_pauta_anep' => 1, 'en_conjunto' => false, 'es_programa_italiano' => false],
        ['nombre' => 'Educación Física', 'horas_semanales' => 2, 'id_pauta_anep' => 2, 'en_conjunto' => false, 'es_programa_italiano' => false],
        ['nombre' => 'Italiano', 'horas_semanales' => 2, 'id_pauta_anep' => 3, 'en_conjunto' => false, 'es_programa_italiano' => true],
        ['nombre' => 'Ciencias Naturales', 'horas_semanales' => 3, 'id_pauta_anep' => 1, 'en_conjunto' => false, 'es_programa_italiano' => false]
    ];
    
    foreach ($materias as $materia) {
        $stmt = $pdo->prepare("INSERT INTO materia (nombre, horas_semanales, id_pauta_anep, en_conjunto, es_programa_italiano) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$materia['nombre'], $materia['horas_semanales'], $materia['id_pauta_anep'], $materia['en_conjunto'] ? 'true' : 'false', $materia['es_programa_italiano'] ? 'true' : 'false']);
    }
    echo "Materias insertadas.\n";
    
    // Insertar usuarios docentes
    $usuarios = [
        ['cedula' => '11111111', 'nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => 'juan.perez@chronos.edu.uy', 'telefono' => '099111111', 'contrasena_hash' => '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri'],
        ['cedula' => '22222222', 'nombre' => 'María', 'apellido' => 'González', 'email' => 'maria.gonzalez@chronos.edu.uy', 'telefono' => '099222222', 'contrasena_hash' => '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri'],
        ['cedula' => '33333333', 'nombre' => 'Carlos', 'apellido' => 'Rodríguez', 'email' => 'carlos.rodriguez@chronos.edu.uy', 'telefono' => '099333333', 'contrasena_hash' => '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri'],
        ['cedula' => '44444444', 'nombre' => 'Ana', 'apellido' => 'Martínez', 'email' => 'ana.martinez@chronos.edu.uy', 'telefono' => '099444444', 'contrasena_hash' => '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri'],
        ['cedula' => '55555555', 'nombre' => 'Luis', 'apellido' => 'Fernández', 'email' => 'luis.fernandez@chronos.edu.uy', 'telefono' => '099555555', 'contrasena_hash' => '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri'],
        ['cedula' => '66666666', 'nombre' => 'Laura', 'apellido' => 'López', 'email' => 'laura.lopez@chronos.edu.uy', 'telefono' => '099666666', 'contrasena_hash' => '$2y$10$6fF5.mtmT4ScYKYmJp6TpuaSfcbLVJQ20czlx.JK.a/f800qjD7Ri']
    ];
    
    foreach ($usuarios as $usuario) {
        $stmt = $pdo->prepare("INSERT INTO usuario (cedula, nombre, apellido, email, telefono, contrasena_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario['cedula'], $usuario['nombre'], $usuario['apellido'], $usuario['email'], $usuario['telefono'], $usuario['contrasena_hash']]);
    }
    echo "Usuarios docentes insertados.\n";
    
    // Asignar rol DOCENTE a los usuarios
    for ($i = 2; $i <= 7; $i++) { // IDs 2-7 corresponden a los docentes
        try {
            $stmt = $pdo->prepare("INSERT INTO usuario_rol (id_usuario, nombre_rol) VALUES (?, 'DOCENTE')");
            $stmt->execute([$i]);
        } catch (PDOException $e) {
            if ($e->getCode() != '23505') { // Ignorar error de duplicado
                throw $e;
            }
        }
    }
    echo "Roles de docente asignados.\n";
    
    // Insertar docentes
    for ($i = 2; $i <= 7; $i++) {
        try {
            $stmt = $pdo->prepare("INSERT INTO docente (id_usuario, trabaja_otro_liceo, fecha_envio_disponibilidad, horas_asignadas, porcentaje_margen) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$i, 'false', date('Y-m-d'), 0, 0.00]);
        } catch (PDOException $e) {
            if ($e->getCode() != '23505') { // Ignorar error de duplicado
                throw $e;
            }
        }
    }
    echo "Docentes insertados.\n";
    
    // Asignar materias a docentes
    $asignaciones = [
        [2, 1], // Juan - Matemáticas
        [3, 2], // María - Lengua Española
        [4, 3], // Carlos - Historia
        [5, 4], // Ana - Educación Física
        [6, 5], // Luis - Italiano
        [7, 6], // Laura - Ciencias Naturales
        [2, 6], // Juan también enseña Ciencias Naturales
        [3, 1]  // María también enseña Matemáticas
    ];
    
    foreach ($asignaciones as $asignacion) {
        $stmt = $pdo->prepare("INSERT INTO docente_materia (id_docente, id_materia) VALUES (?, ?)");
        $stmt->execute($asignacion);
    }
    echo "Asignaciones docente-materia insertadas.\n";
    
    // Insertar algunos horarios de ejemplo
    $horarios = [
        [1, 2, 1, 1, 'LUNES'],    // Grupo 1A, Docente Juan, Matemáticas, Bloque 1, Lunes
        [1, 2, 1, 2, 'LUNES'],    // Grupo 1A, Docente Juan, Matemáticas, Bloque 2, Lunes
        [1, 3, 2, 3, 'LUNES'],    // Grupo 1A, Docente María, Lengua Española, Bloque 3, Lunes
        [1, 4, 3, 4, 'LUNES'],    // Grupo 1A, Docente Carlos, Historia, Bloque 4, Lunes
        [1, 5, 4, 5, 'LUNES'],    // Grupo 1A, Docente Ana, Educación Física, Bloque 5, Lunes
        
        [2, 2, 1, 1, 'MARTES'],   // Grupo 1B, Docente Juan, Matemáticas, Bloque 1, Martes
        [2, 3, 2, 2, 'MARTES'],   // Grupo 1B, Docente María, Lengua Española, Bloque 2, Martes
        [2, 6, 6, 3, 'MARTES'],   // Grupo 1B, Docente Laura, Ciencias Naturales, Bloque 3, Martes
        [2, 4, 3, 4, 'MARTES'],   // Grupo 1B, Docente Carlos, Historia, Bloque 4, Martes
        [2, 5, 4, 5, 'MARTES'],   // Grupo 1B, Docente Ana, Educación Física, Bloque 5, Martes
    ];
    
    foreach ($horarios as $horario) {
        $stmt = $pdo->prepare("INSERT INTO horario (id_grupo, id_docente, id_materia, id_bloque, dia) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($horario);
    }
    echo "Horarios de ejemplo insertados.\n";
    
    echo "\n¡Datos de ejemplo insertados exitosamente!\n";
    echo "Ahora puedes acceder a la gestión de horarios con datos reales.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Error inserting sample data: " . $e->getMessage());
}
?>
