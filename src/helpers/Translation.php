<?php
/**
 * Translation Helper Class
 * Clase auxiliar para el sistema de traducciones
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
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load translation configuration
     */
    private function loadConfig()
    {
        $configPath = __DIR__ . '/../config/translations.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            // Default configuration
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
    
    /**
     * Detect current language from session, cookie, or browser
     */
    private function detectLanguage()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if language is set in session
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->config['supported_languages'])) {
            $this->currentLanguage = $_SESSION['language'];
            return;
        }
        
        // Check if language is set in cookie
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->config['supported_languages'])) {
            $this->currentLanguage = $_COOKIE['language'];
            $_SESSION['language'] = $this->currentLanguage;
            return;
        }
        
        // Check browser language
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
        
        // Use default language
        $this->currentLanguage = $this->config['default_language'];
        $_SESSION['language'] = $this->currentLanguage;
    }
    
    /**
     * Load translations for current language
     */
    private function loadTranslations()
    {
        $langFile = __DIR__ . '/../lang/' . $this->currentLanguage . '.php';
        
        if (file_exists($langFile)) {
            $this->translations = require $langFile;
        } else {
            // Fallback to default language
            $fallbackFile = __DIR__ . '/../lang/' . $this->config['fallback_language'] . '.php';
            if (file_exists($fallbackFile)) {
                $this->translations = require $fallbackFile;
            }
        }
    }
    
    /**
     * Get translation for a key
     * 
     * @param string $key Translation key
     * @param array $params Parameters to replace in translation
     * @return string Translated text
     */
    public function get($key, $params = [])
    {
        $translation = $this->translations[$key] ?? $key;
        
        // Replace parameters if provided
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(':' . $param, $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Set current language
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    public function setLanguage($language)
    {
        if (!in_array($language, $this->config['supported_languages'])) {
            return false;
        }
        
        $this->currentLanguage = $language;
        $_SESSION['language'] = $language;
        
        // Set cookie for 30 days
        setcookie('language', $language, time() + (30 * 24 * 60 * 60), '/');
        
        // Reload translations
        $this->loadTranslations();
        
        return true;
    }
    
    /**
     * Get current language
     * 
     * @return string Current language code
     */
    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }
    
    /**
     * Get supported languages
     * 
     * @return array Supported languages
     */
    public function getSupportedLanguages()
    {
        return $this->config['supported_languages'];
    }
    
    /**
     * Get language names
     * 
     * @return array Language names
     */
    public function getLanguageNames()
    {
        return $this->config['language_names'];
    }
    
    /**
     * Check if language is RTL
     * 
     * @param string $language Language code
     * @return bool Is RTL
     */
    public function isRTL($language = null)
    {
        $lang = $language ?: $this->currentLanguage;
        return in_array($lang, $this->config['rtl_languages'] ?? []);
    }
    
    /**
     * Get all translations for current language
     * 
     * @return array All translations
     */
    public function getAllTranslations()
    {
        return $this->translations;
    }
    
    /**
     * Check if translation exists
     * 
     * @param string $key Translation key
     * @return bool Translation exists
     */
    public function has($key)
    {
        return isset($this->translations[$key]);
    }
}

/**
 * Global translation function
 * 
 * @param string $key Translation key
 * @param array $params Parameters to replace
 * @return string Translated text
 */
function __($key, $params = [])
{
    return Translation::getInstance()->get($key, $params);
}

/**
 * Echo translation function
 * 
 * @param string $key Translation key
 * @param array $params Parameters to replace
 */
function _e($key, $params = [])
{
    echo __($key, $params);
}

/**
 * Get current language
 * 
 * @return string Current language code
 */
function getCurrentLanguage()
{
    return Translation::getInstance()->getCurrentLanguage();
}

/**
 * Set language
 * 
 * @param string $language Language code
 * @return bool Success status
 */
function setLanguage($language)
{
    return Translation::getInstance()->setLanguage($language);
}

/**
 * Get supported languages
 * 
 * @return array Supported languages
 */
function getSupportedLanguages()
{
    return Translation::getInstance()->getSupportedLanguages();
}

/**
 * Get language names
 * 
 * @return array Language names
 */
function getLanguageNames()
{
    return Translation::getInstance()->getLanguageNames();
}

/**
 * Check if current language is RTL
 * 
 * @return bool Is RTL
 */
function isRTL()
{
    return Translation::getInstance()->isRTL();
}
