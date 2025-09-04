<?php
/**
 * Test Translation System
 * Archivo de prueba para el sistema de traducciones
 */

require_once 'src/helpers/Translation.php';

echo "<h1>Test del Sistema de Traducciones</h1>";

// Test 1: Default language (Spanish)
echo "<h2>Test 1: Idioma por defecto (Español)</h2>";
$translation = Translation::getInstance();
echo "Idioma actual: " . $translation->getCurrentLanguage() . "<br>";
echo "App Name: " . $translation->get('app_name') . "<br>";
echo "Login: " . $translation->get('login') . "<br>";
echo "Teachers: " . $translation->get('teachers') . "<br>";

// Test 2: Change to Italian
echo "<h2>Test 2: Cambiar a Italiano</h2>";
$translation->setLanguage('it');
echo "Idioma actual: " . $translation->getCurrentLanguage() . "<br>";
echo "App Name: " . $translation->get('app_name') . "<br>";
echo "Login: " . $translation->get('login') . "<br>";
echo "Teachers: " . $translation->get('teachers') . "<br>";

// Test 3: Change to English
echo "<h2>Test 3: Cambiar a Inglés</h2>";
$translation->setLanguage('en');
echo "Idioma actual: " . $translation->getCurrentLanguage() . "<br>";
echo "App Name: " . $translation->get('app_name') . "<br>";
echo "Login: " . $translation->get('login') . "<br>";
echo "Teachers: " . $translation->get('teachers') . "<br>";

// Test 4: Test global functions
echo "<h2>Test 4: Funciones globales</h2>";
$translation->setLanguage('es');
echo "Usando __(): " . __('app_name') . "<br>";
echo "Usando _e(): ";
_e('login');
echo "<br>";

// Test 5: Test with parameters
echo "<h2>Test 5: Con parámetros</h2>";
echo "Mensaje con parámetro: " . $translation->get('validation_ci_required') . "<br>";

// Test 6: Test supported languages
echo "<h2>Test 6: Idiomas soportados</h2>";
$supportedLangs = $translation->getSupportedLanguages();
echo "Idiomas soportados: " . implode(', ', $supportedLangs) . "<br>";

$langNames = $translation->getLanguageNames();
echo "Nombres de idiomas: " . implode(', ', $langNames) . "<br>";

// Test 7: Test RTL
echo "<h2>Test 7: RTL</h2>";
echo "¿Es RTL el español? " . ($translation->isRTL('es') ? 'Sí' : 'No') . "<br>";
echo "¿Es RTL el italiano? " . ($translation->isRTL('it') ? 'Sí' : 'No') . "<br>";

echo "<h2>Test completado exitosamente!</h2>";
?>
