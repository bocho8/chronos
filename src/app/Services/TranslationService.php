<?php

namespace App\Services;

class TranslationService
{
    private $languages = ['es', 'en', 'it'];
    private $translations = [];
    private $langPath;

    public function __construct()
    {
        $this->langPath = __DIR__ . '/../../lang/';
        $this->loadAllTranslations();
    }

    /**
     * Load all translation files
     */
    private function loadAllTranslations()
    {
        foreach ($this->languages as $lang) {
            $filePath = $this->langPath . $lang . '.php';
            if (file_exists($filePath)) {
                $this->translations[$lang] = require $filePath;
            } else {
                $this->translations[$lang] = [];
            }
        }
    }

    /**
     * Get all translation keys across all languages
     */
    public function getAllKeys()
    {
        $allKeys = [];
        foreach ($this->translations as $lang => $translations) {
            $allKeys = array_merge($allKeys, array_keys($translations));
        }
        return array_unique($allKeys);
    }

    /**
     * Get missing translation keys for a specific language
     */
    public function getMissingKeys($language)
    {
        if (!in_array($language, $this->languages)) {
            return [];
        }

        $allKeys = $this->getAllKeys();
        $existingKeys = array_keys($this->translations[$language]);
        
        return array_diff($allKeys, $existingKeys);
    }

    /**
     * Get completion percentage for a language
     */
    public function getCompletionPercentage($language)
    {
        if (!in_array($language, $this->languages)) {
            return 0;
        }

        $allKeys = $this->getAllKeys();
        $existingKeys = array_keys($this->translations[$language]);
        
        if (empty($allKeys)) {
            return 0;
        }

        return round((count($existingKeys) / count($allKeys)) * 100, 2);
    }

    /**
     * Get all translations for all languages
     */
    public function getAllTranslations()
    {
        $allKeys = $this->getAllKeys();
        $result = [];

        // Debug: Log the count of keys found
        error_log("TranslationService: Found " . count($allKeys) . " unique keys across all languages");

        foreach ($allKeys as $key) {
            $result[$key] = [];
            foreach ($this->languages as $lang) {
                $result[$key][$lang] = $this->translations[$lang][$key] ?? '';
            }
        }

        // Debug: Log the count of result entries
        error_log("TranslationService: Returning " . count($result) . " translation entries");

        return $result;
    }

    /**
     * Get translations for a specific language
     */
    public function getTranslations($language)
    {
        return $this->translations[$language] ?? [];
    }

    /**
     * Update a translation key for a specific language
     */
    public function updateTranslation($key, $language, $value)
    {
        if (!in_array($language, $this->languages)) {
            return false;
        }

        // Validate key format
        if (!$this->validateKey($key)) {
            return false;
        }

        // Update in memory
        $this->translations[$language][$key] = $value;

        // Save to file
        return $this->saveTranslations($language);
    }

    /**
     * Bulk update translations for a language
     */
    public function bulkUpdateTranslations($language, $translations)
    {
        if (!in_array($language, $this->languages)) {
            return false;
        }

        foreach ($translations as $key => $value) {
            if ($this->validateKey($key)) {
                $this->translations[$language][$key] = $value;
            }
        }

        return $this->saveTranslations($language);
    }

    /**
     * Save translations to file
     */
    private function saveTranslations($language)
    {
        $filePath = $this->langPath . $language . '.php';
        
        // Create backup
        $this->createBackup($language);

        // Prepare file content
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * " . ucfirst($language) . " Language File\n";
        $content .= " * " . ($language === 'es' ? 'Archivo de idioma español para el sistema Chronos' : 
                    ($language === 'it' ? 'File di lingua italiana per il sistema Chronos' : 
                    'English language file for Chronos system')) . "\n";
        $content .= " */\n\n";
        $content .= "return [\n\n";

        // Sort translations by key
        ksort($this->translations[$language]);

        foreach ($this->translations[$language] as $key => $value) {
            $escapedValue = $this->escapeValue($value);
            $content .= "    '{$key}' => {$escapedValue},\n";
        }

        $content .= "];\n";

        return file_put_contents($filePath, $content) !== false;
    }

    /**
     * Escape value for PHP file
     */
    private function escapeValue($value)
    {
        if (is_string($value)) {
            return "'" . str_replace("'", "\\'", $value) . "'";
        }
        return var_export($value, true);
    }

    /**
     * Create backup of translation file
     */
    private function createBackup($language)
    {
        $filePath = $this->langPath . $language . '.php';
        $backupPath = $this->langPath . 'backups/' . $language . '_' . date('Y-m-d_H-i-s') . '.php';
        
        if (!is_dir($this->langPath . 'backups/')) {
            mkdir($this->langPath . 'backups/', 0755, true);
        }

        if (file_exists($filePath)) {
            copy($filePath, $backupPath);
        }
    }

    /**
     * Validate translation key format
     */
    public function validateKey($key)
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key);
    }

    /**
     * Find duplicate keys within a language
     */
    public function findDuplicates($language)
    {
        if (!in_array($language, $this->languages)) {
            return [];
        }

        $keys = array_keys($this->translations[$language]);
        return array_diff_assoc($keys, array_unique($keys));
    }

    /**
     * Get statistics for all languages
     */
    public function getStatistics()
    {
        $stats = [];
        
        foreach ($this->languages as $lang) {
            $stats[$lang] = [
                'total_keys' => count($this->translations[$lang]),
                'completion_percentage' => $this->getCompletionPercentage($lang),
                'missing_keys' => count($this->getMissingKeys($lang)),
                'duplicates' => count($this->findDuplicates($lang))
            ];
        }

        return $stats;
    }

    /**
     * Export translations to JSON
     */
    public function exportToJson($language = null)
    {
        if ($language && in_array($language, $this->languages)) {
            return json_encode($this->translations[$language], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return json_encode($this->getAllTranslations(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export translations to CSV
     */
    public function exportToCsv($language = null)
    {
        $data = [];
        
        if ($language && in_array($language, $this->languages)) {
            $data[] = ['Key', 'Value'];
            foreach ($this->translations[$language] as $key => $value) {
                $data[] = [$key, $value];
            }
        } else {
            $allTranslations = $this->getAllTranslations();
            $data[] = ['Key', 'Spanish', 'English', 'Italian'];
            
            foreach ($allTranslations as $key => $translations) {
                $data[] = [
                    $key,
                    $translations['es'] ?? '',
                    $translations['en'] ?? '',
                    $translations['it'] ?? ''
                ];
            }
        }

        $output = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages()
    {
        return $this->languages;
    }

    /**
     * Reload translations from files
     */
    public function reload()
    {
        $this->loadAllTranslations();
    }
}
