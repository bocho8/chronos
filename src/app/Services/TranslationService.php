<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

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
        
        if (empty($allKeys)) {
            return 0;
        }

        $completedKeys = 0;
        foreach ($allKeys as $key) {
            $value = $this->translations[$language][$key] ?? '';
            // Count as completed only if the value exists and is not empty
            if (!empty($value) && trim($value) !== '') {
                $completedKeys++;
            }
        }

        return round(($completedKeys / count($allKeys)) * 100, 2);
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
        $allKeys = $this->getAllKeys();
        
        foreach ($this->languages as $lang) {
            $completedKeys = 0;
            foreach ($allKeys as $key) {
                $value = $this->translations[$lang][$key] ?? '';
                if (!empty($value) && trim($value) !== '') {
                    $completedKeys++;
                }
            }
            
            $stats[$lang] = [
                'total_keys' => $completedKeys, // Only count non-empty keys
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

    /**
     * Detect Spanish text in non-Spanish fields (EN/IT)
     * Returns array of keys where EN or IT fields match ES field exactly
     */
    public function detectSpanishInNonSpanishFields()
    {
        $spanishErrors = [];
        $allTranslations = $this->getAllTranslations();

        foreach ($allTranslations as $key => $translation) {
            $esValue = $translation['es'] ?? '';
            $enValue = $translation['en'] ?? '';
            $itValue = $translation['it'] ?? '';

            // Only check if Spanish field is not empty
            if (empty($esValue)) {
                continue;
            }

            $errors = [];

            // Check if English field matches Spanish field exactly
            if ($enValue === $esValue) {
                $errors[] = 'en';
            }

            // Check if Italian field matches Spanish field exactly
            if ($itValue === $esValue) {
                $errors[] = 'it';
            }

            // If we found any Spanish errors, add to results
            if (!empty($errors)) {
                $spanishErrors[$key] = $errors;
            }
        }

        return $spanishErrors;
    }

    /**
     * Clear all Spanish errors in one bulk operation
     * Returns array with count and details of cleared items
     */
    public function clearAllSpanishErrors()
    {
        // Detect all Spanish errors first
        $spanishErrors = $this->detectSpanishInNonSpanishFields();
        
        if (empty($spanishErrors)) {
            return [
                'success' => true,
                'count' => 0,
                'cleared' => [],
                'message' => 'No Spanish errors found to clear'
            ];
        }

        // Create backup before making changes
        $this->createBackup('es');
        $this->createBackup('en');
        $this->createBackup('it');

        $clearedItems = [];
        $clearedCount = 0;

        try {
            // Process each language file
            foreach (['en', 'it'] as $language) {
                $hasChanges = false;
                
                foreach ($spanishErrors as $key => $errorFields) {
                    if (in_array($language, $errorFields)) {
                        // Clear the field (set to empty)
                        $this->translations[$language][$key] = '';
                        $hasChanges = true;
                        $clearedCount++;
                        
                        if (!isset($clearedItems[$key])) {
                            $clearedItems[$key] = [];
                        }
                        $clearedItems[$key][] = $language;
                    }
                }

                // Save the language file if there were changes
                if ($hasChanges) {
                    if (!$this->saveTranslations($language)) {
                        throw new \Exception("Failed to save {$language} translations");
                    }
                }
            }

            return [
                'success' => true,
                'count' => $clearedCount,
                'cleared' => $clearedItems,
                'message' => "Successfully cleared Spanish text from {$clearedCount} field(s)"
            ];

        } catch (\Exception $e) {
            // If there was an error, we could implement rollback here
            // For now, just return the error
            return [
                'success' => false,
                'count' => 0,
                'cleared' => [],
                'message' => 'Error clearing Spanish errors: ' . $e->getMessage()
            ];
        }
    }
}
