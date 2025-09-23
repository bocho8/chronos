<?php

class ResponseHelper {
    
    public static function success($message, $data = null) {
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function error($message, $data = null, $httpCode = 400) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function validationError($errors) {
        self::error('Datos inválidos', $errors);
    }
    
    public static function notFound($resource = 'Recurso') {
        self::error("$resource no encontrado", null, 404);
    }
    
    public static function unauthorized($message = 'No autorizado') {
        self::error($message, null, 401);
    }
    
    public static function forbidden($message = 'Acceso denegado') {
        self::error($message, null, 403);
    }
}
