<?php

require_once __DIR__ . '/../helpers/Translation.php';

class LanguageSwitcher
{
    private $translation;
    
    public function __construct()
    {
        $this->translation = Translation::getInstance();
    }
    
    public function render($currentUrl = '', $class = '')
    {
        $currentLang = $this->translation->getCurrentLanguage();
        $supportedLangs = $this->translation->getSupportedLanguages();
        $langNames = $this->translation->getLanguageNames();
        
        // Build language options
        $languageOptions = '';
        foreach ($supportedLangs as $langCode) {
            $isActive = $langCode === $currentLang;
            $langName = $langNames[$langCode] ?? $langCode;
            $flag = $this->getLanguageFlag($langCode);
            $activeClass = $isActive ? 'bg-gray-100 text-gray-900' : 'text-gray-700';
            $currentLangName = $langNames[$currentLang] ?? $currentLang;
            
            $languageOptions .= <<<HTML
                <a href="#" class="language-option {$activeClass} group flex items-center px-4 py-2 text-sm hover:bg-gray-100 hover:text-gray-900" role="menuitem" data-lang="{$langCode}">
                    <span class="mr-3 text-lg">{$flag}</span>
                    <span>{$langName}</span>
                HTML;
            
            if ($isActive) {
                $languageOptions .= <<<HTML
                    <svg class="ml-auto h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                HTML;
            }
            
            $languageOptions .= '</a>';
        }
        
        return <<<HTML
        <div class="language-switcher {$class}">
            <div class="relative inline-block text-left">
                <div>
                    <button type="button" class="language-switcher-btn inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="language-menu-button" aria-expanded="true" aria-haspopup="true">
                        <span class="mr-2">🌐</span>
                        <span class="language-current">{$currentLangName}</span>
                        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                
                <div class="language-dropdown origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="language-menu-button" tabindex="-1">
                    <div class="py-1" role="none">
                        {$languageOptions}
                    </div>
                </div>
            </div>
        </div>
        {$this->getJavaScript()}
        HTML;
    }
    
    private function getLanguageFlag($langCode)
    {
        return match ($langCode) {
            'es' => '🇪🇸',
            'it' => '🇮🇹',
            'en' => '🇺🇸',
            default => '🌐'
        };
    }
    
    private function getJavaScript()
    {
        return <<<HTML
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const languageBtn = document.getElementById("language-menu-button");
            const languageDropdown = document.querySelector(".language-dropdown");
            const languageOptions = document.querySelectorAll(".language-option");
            
            if (languageBtn && languageDropdown) {
                // Toggle dropdown
                languageBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    languageDropdown.classList.toggle("hidden");
                });
                
                // Close dropdown when clicking outside
                document.addEventListener("click", function(e) {
                    if (!languageBtn.contains(e.target) && !languageDropdown.contains(e.target)) {
                        languageDropdown.classList.add("hidden");
                    }
                });
                
                // Handle language selection
                languageOptions.forEach(option => {
                    option.addEventListener("click", function(e) {
                        e.preventDefault();
                        const selectedLang = this.getAttribute("data-lang");
                        
                        // Create form to submit language change
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = window.location.href;
                        
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "change_language";
                        input.value = selectedLang;
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    });
                });
            }
        });
        </script>
        HTML;
    }
    
    public function handleLanguageChange()
    {
        if (isset($_POST['change_language'])) {
            $newLanguage = $_POST['change_language'];
            return $this->translation->setLanguage($newLanguage);
        }
        return false;
    }
}
