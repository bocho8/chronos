<?php

class ValidationHelper {
    
    public static function validateRequired($data, $fields) {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = self::getFieldLabel($field) . ' es requerido';
            }
        }
        return $errors;
    }
    
    public static function validateCedula($cedula) {
        if (empty($cedula)) {
            return 'La cédula es requerida';
        }
        if (!preg_match('/^\d{7,8}$/', $cedula)) {
            return 'Cédula debe tener 7 u 8 dígitos';
        }
        return null;
    }
    
    public static function validateEmail($email, $required = false) {
        if ($required && empty($email)) {
            return 'El email es requerido';
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email inválido';
        }
        return null;
    }
    
    public static function validatePassword($password, $required = false) {
        if ($required && empty($password)) {
            return 'La contraseña es requerida';
        }
        if (!empty($password) && strlen($password) < 6) {
            return 'Contraseña debe tener al menos 6 caracteres';
        }
        return null;
    }
    
    public static function validateName($name, $field = 'nombre') {
        if (empty($name)) {
            return self::getFieldLabel($field) . ' es requerido';
        }
        if (strlen($name) < 2) {
            return self::getFieldLabel($field) . ' debe tener al menos 2 caracteres';
        }
        return null;
    }
    
    public static function validatePhone($phone) {
        if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
            return 'Teléfono inválido';
        }
        return null;
    }
    
    public static function validateNumericRange($value, $field, $min = 0, $max = null) {
        if (!is_numeric($value) || $value < $min) {
            return self::getFieldLabel($field) . " debe ser un número mayor o igual a $min";
        }
        if ($max !== null && $value > $max) {
            return self::getFieldLabel($field) . " debe ser menor o igual a $max";
        }
        return null;
    }
    
    private static function getFieldLabel($field) {
        $labels = [
            'cedula' => 'La cédula',
            'nombre' => 'El nombre',
            'apellido' => 'El apellido',
            'email' => 'El email',
            'telefono' => 'El teléfono',
            'contrasena' => 'La contraseña',
            'horas_asignadas' => 'Horas asignadas',
            'porcentaje_margen' => 'Porcentaje de margen',
            'horas_semanales' => 'Horas semanales'
        ];
        
        return $labels[$field] ?? ucfirst($field);
    }
}
