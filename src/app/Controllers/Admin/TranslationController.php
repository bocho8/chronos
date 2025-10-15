<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

namespace App\Controllers\Admin;

use App\Services\TranslationService;

class TranslationController
{
    private $translationService;
    private $database;

    public function __construct($database = null)
    {
        $this->database = $database;
        $this->translationService = new TranslationService();
        require_once __DIR__ . '/../../../helpers/ResponseHelper.php';
    }

    /**
     * Display translation management interface
     */
    public function index()
    {
        require_once __DIR__ . '/../../../helpers/Translation.php';
        require_once __DIR__ . '/../../../components/LanguageSwitcher.php';

        $translation = \Translation::getInstance();
        $languageSwitcher = new \LanguageSwitcher();

        // Handle language change
        $languageSwitcher->handleLanguageChange();

        // Get statistics
        $statistics = $this->translationService->getStatistics();

        include __DIR__ . '/../../../views/admin/AdminTranslations.php';
    }

    /**
     * Get all translations for all languages (AJAX)
     */
    public function getAll()
    {
        try {
            $translations = $this->translationService->getAllTranslations();
            \ResponseHelper::success('Translations loaded successfully', $translations);
        } catch (\Exception $e) {
            \ResponseHelper::error('Error loading translations: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get missing translation keys (AJAX)
     */
    public function getMissing()
    {
        try {
            $language = $_GET['language'] ?? null;
            
            if (!$language) {
                $missing = [];
                foreach ($this->translationService->getSupportedLanguages() as $lang) {
                    $missing[$lang] = $this->translationService->getMissingKeys($lang);
                }
            } else {
                $missing = $this->translationService->getMissingKeys($language);
            }

            \ResponseHelper::success('Missing translations loaded successfully', $missing);
        } catch (\Exception $e) {
            \ResponseHelper::error('Error loading missing translations: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update a specific translation key (AJAX)
     */
    public function update()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['key']) || !isset($input['language']) || !isset($input['value'])) {
            \ResponseHelper::error('Missing required parameters', null, 400);
                return;
            }

            $key = $input['key'];
            $language = $input['language'];
            $value = $input['value'];

            // Validate key format
            if (!$this->translationService->validateKey($key)) {
            \ResponseHelper::error('Invalid key format', null, 400);
                return;
            }

            $result = $this->translationService->updateTranslation($key, $language, $value);

            if ($result) {
            \ResponseHelper::success('Translation updated successfully');
            } else {
                \ResponseHelper::error('Failed to update translation', null, 500);
            }
        } catch (\Exception $e) {
            \ResponseHelper::error('Error updating translation: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk update translations (AJAX)
     */
    public function bulkUpdate()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['language']) || !isset($input['translations'])) {
            \ResponseHelper::error('Missing required parameters', null, 400);
                return;
            }

            $language = $input['language'];
            $translations = $input['translations'];

            $result = $this->translationService->bulkUpdateTranslations($language, $translations);

            if ($result) {
            \ResponseHelper::success('Translations updated successfully');
            } else {
                \ResponseHelper::error('Failed to update translations', null, 500);
            }
        } catch (\Exception $e) {
            \ResponseHelper::error('Error updating translations: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export translations
     */
    public function export()
    {
        try {
            $format = $_GET['format'] ?? 'json';
            $language = $_GET['language'] ?? null;

            if ($format === 'csv') {
                $content = $this->translationService->exportToCsv($language);
                $filename = 'translations_' . ($language ?: 'all') . '_' . date('Y-m-d_H-i-s') . '.csv';
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $content;
            } else {
                $content = $this->translationService->exportToJson($language);
                $filename = 'translations_' . ($language ?: 'all') . '_' . date('Y-m-d_H-i-s') . '.json';
                
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $content;
            }
        } catch (\Exception $e) {
            \ResponseHelper::error('Error exporting translations: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get statistics (AJAX)
     */
    public function getStatistics()
    {
        try {
            $statistics = $this->translationService->getStatistics();
            
            \ResponseHelper::success('Statistics loaded successfully', $statistics);
        } catch (\Exception $e) {
            \ResponseHelper::error('Error loading statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Fill missing translations from source language
     */
    public function fillMissing()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['target_language']) || !isset($input['source_language'])) {
            \ResponseHelper::error('Missing required parameters', null, 400);
                return;
            }

            $targetLanguage = $input['target_language'];
            $sourceLanguage = $input['source_language'];

            if (!in_array($targetLanguage, $this->translationService->getSupportedLanguages()) ||
                !in_array($sourceLanguage, $this->translationService->getSupportedLanguages())) {
            \ResponseHelper::error('Invalid language', null, 400);
                return;
            }

            $sourceTranslations = $this->translationService->getTranslations($sourceLanguage);
            $missingKeys = $this->translationService->getMissingKeys($targetLanguage);
            
            $translationsToAdd = [];
            foreach ($missingKeys as $key) {
                if (isset($sourceTranslations[$key])) {
                    $translationsToAdd[$key] = $sourceTranslations[$key];
                }
            }

            $result = $this->translationService->bulkUpdateTranslations($targetLanguage, $translationsToAdd);

            if ($result) {
            \ResponseHelper::success('Missing translations filled successfully', ['count' => count($translationsToAdd)]);
            } else {
                \ResponseHelper::error('Failed to fill missing translations', null, 500);
            }
        } catch (\Exception $e) {
            \ResponseHelper::error('Error filling missing translations: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Validate translation key
     */
    public function validateKey()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['key'])) {
            \ResponseHelper::error('Missing key parameter', null, 400);
                return;
            }

            $isValid = $this->translationService->validateKey($input['key']);
            
            \ResponseHelper::success($isValid ? 'Key is valid' : 'Key format is invalid', ['valid' => $isValid]);
        } catch (\Exception $e) {
            \ResponseHelper::error('Error validating key: ' . $e->getMessage(), null, 500);
        }
    }
}
