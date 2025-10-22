<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

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
    
    public static function jsonSuccess($data = null, $message = 'Success') {
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function jsonError($message = 'Error', $httpCode = 400) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        echo json_encode($response);
        exit;
    }
}
