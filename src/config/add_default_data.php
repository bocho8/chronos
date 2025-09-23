<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/Database.php';

try {
    $dbConfig = require __DIR__ . '/database.php';
    $database = new Database($dbConfig);
    $pdo = $database->getConnection();
    
    $pautas = [
        ['nombre' => 'Pauta Estándar', 'dias_minimos' => 1, 'dias_maximos' => 5, 'condiciones_especiales' => 'Distribución normal durante la semana'],
        ['nombre' => 'Pauta Intensiva', 'dias_minimos' => 2, 'dias_maximos' => 3, 'condiciones_especiales' => 'Clases concentradas en pocos días'],
        ['nombre' => 'Pauta Extendida', 'dias_minimos' => 3, 'dias_maximos' => 5, 'condiciones_especiales' => 'Distribución amplia durante la semana'],
        ['nombre' => 'Pauta Laboratorio', 'dias_minimos' => 1, 'dias_maximos' => 2, 'condiciones_especiales' => 'Para materias prácticas y laboratorios']
    ];
    
    foreach ($pautas as $pauta) {
        $stmt = $pdo->prepare("INSERT INTO pauta_anep (nombre, dias_minimos, dias_maximos, condiciones_especiales) 
                              VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$pauta['nombre'], $pauta['dias_minimos'], $pauta['dias_maximos'], $pauta['condiciones_especiales']]);
        } catch (PDOException $e) {
            // Ignore duplicate entries
            if ($e->getCode() != '23505') {
                throw $e;
            }
        }
    }
    
    // Insert default grupos
    $grupos = [
        ['nombre' => '1º Año A', 'nivel' => '1º Año'],
        ['nombre' => '1º Año B', 'nivel' => '1º Año'],
        ['nombre' => '2º Año A', 'nivel' => '2º Año'],
        ['nombre' => '2º Año B', 'nivel' => '2º Año'],
        ['nombre' => '3º Año A', 'nivel' => '3º Año'],
        ['nombre' => '3º Año B', 'nivel' => '3º Año'],
        ['nombre' => '4º Año', 'nivel' => '4º Año'],
        ['nombre' => '5º Año', 'nivel' => '5º Año'],
        ['nombre' => '6º Año', 'nivel' => '6º Año']
    ];
    
    foreach ($grupos as $grupo) {
        $stmt = $pdo->prepare("INSERT INTO grupo (nombre, nivel) VALUES (?, ?)");
        try {
            $stmt->execute([$grupo['nombre'], $grupo['nivel']]);
        } catch (PDOException $e) {
            // Ignore duplicate entries
            if ($e->getCode() != '23505') {
                throw $e;
            }
        }
    }
    
    // Insert some default materias
    $materias = [
        ['nombre' => 'Matemáticas', 'horas_semanales' => 4, 'id_pauta_anep' => 1, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Lengua Española', 'horas_semanales' => 3, 'id_pauta_anep' => 1, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Historia', 'horas_semanales' => 2, 'id_pauta_anep' => 1, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Geografía', 'horas_semanales' => 2, 'id_pauta_anep' => 1, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Biología', 'horas_semanales' => 3, 'id_pauta_anep' => 2, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Física', 'horas_semanales' => 3, 'id_pauta_anep' => 2, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Química', 'horas_semanales' => 3, 'id_pauta_anep' => 2, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Inglés', 'horas_semanales' => 3, 'id_pauta_anep' => 1, 'es_programa_italiano' => 'f'],
        ['nombre' => 'Italiano', 'horas_semanales' => 4, 'id_pauta_anep' => 1, 'es_programa_italiano' => 't'],
        ['nombre' => 'Educación Física', 'horas_semanales' => 2, 'id_pauta_anep' => 3, 'es_programa_italiano' => 'f']
    ];
    
    foreach ($materias as $materia) {
        $stmt = $pdo->prepare("INSERT INTO materia (nombre, horas_semanales, id_pauta_anep, es_programa_italiano) 
                              VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$materia['nombre'], $materia['horas_semanales'], $materia['id_pauta_anep'], $materia['es_programa_italiano']]);
        } catch (PDOException $e) {
            // Ignore duplicate entries
            if ($e->getCode() != '23505') {
                throw $e;
            }
        }
    }
    
    echo "Datos por defecto agregados exitosamente.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
