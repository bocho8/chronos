<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

class Translation
{
    private static $instance = null;
    private $currentLanguage = 'es';
    private $translations = [];
    private $config = [];
    
    private function __construct()
    {
        $this->loadConfig();
        $this->detectLanguage();
        $this->loadTranslations();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig()
    {
        $configPath = __DIR__ . '/../config/translations.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            $this->config = [
                'default_language' => 'es',
                'supported_languages' => ['es', 'it', 'en'],
                'language_names' => [
                    'es' => 'Español',
                    'it' => 'Italiano',
                    'en' => 'English'
                ],
                'fallback_language' => 'es'
            ];
        }
    }
    
    private function detectLanguage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->config['supported_languages'])) {
            $this->currentLanguage = $_SESSION['language'];
            return;
        }
        
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->config['supported_languages'])) {
            $this->currentLanguage = $_COOKIE['language'];
            $_SESSION['language'] = $this->currentLanguage;
            return;
        }
        
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browserLanguages as $lang) {
                $lang = strtolower(substr(trim($lang), 0, 2));
                if (in_array($lang, $this->config['supported_languages'])) {
                    $this->currentLanguage = $lang;
                    $_SESSION['language'] = $this->currentLanguage;
                    return;
                }
            }
        }
        
        $this->currentLanguage = $this->config['default_language'];
        $_SESSION['language'] = $this->currentLanguage;
    }
    
    private function loadTranslations()
    {
        $langFile = __DIR__ . '/../lang/' . $this->currentLanguage . '.php';
        
        if (file_exists($langFile)) {
            $this->translations = require $langFile;
        } else {
            $fallbackFile = __DIR__ . '/../lang/' . $this->config['fallback_language'] . '.php';
            if (file_exists($fallbackFile)) {
                $this->translations = require $fallbackFile;
            }
        }
    }
    
    public function get($key, $params = [])
    {
        $translation = $this->translations[$key] ?? $key;
        
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(':' . $param, $value, $translation);
            }
        }
        
        return $translation;
    }
    
    public function setLanguage($language)
    {
        if (!in_array($language, $this->config['supported_languages'])) {
            return false;
        }
        
        $this->currentLanguage = $language;
        $_SESSION['language'] = $language;
        setcookie('language', $language, time() + (30 * 24 * 60 * 60), '/');
        $this->loadTranslations();
        
        return true;
    }
    
    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }
    
    public function getSupportedLanguages()
    {
        return $this->config['supported_languages'];
    }
    
    public function getLanguageNames()
    {
        return $this->config['language_names'];
    }
    
    public function isRTL($language = null)
    {
        $lang = $language ?: $this->currentLanguage;
        return in_array($lang, $this->config['rtl_languages'] ?? []);
    }
    
    public function getAllTranslations()
    {
        return $this->translations;
    }
    
    public function has($key)
    {
        return isset($this->translations[$key]);
    }
}

function __($key, $params = [])
{
    return Translation::getInstance()->get($key, $params);
}

function _e($key, $params = [])
{
    echo __($key, $params);
}

function getCurrentLanguage()
{
    return Translation::getInstance()->getCurrentLanguage();
}

function setLanguage($language)
{
    return Translation::getInstance()->setLanguage($language);
}

function getSupportedLanguages()
{
    return Translation::getInstance()->getSupportedLanguages();
}

function getLanguageNames()
{
    return Translation::getInstance()->getLanguageNames();
}

function isRTL()
{
    return Translation::getInstance()->isRTL();
}
