<?php
/**
 * Language Switcher Component
 * Componente para cambiar idioma en la interfaz
 */

require_once __DIR__ . '/../helpers/Translation.php';

class LanguageSwitcher
{
    private $translation;
    
    public function __construct()
    {
        $this->translation = Translation::getInstance();
    }
    
    /**
     * Render language switcher dropdown
     * 
     * @param string $currentUrl Current page URL
     * @param string $class Additional CSS classes
     * @return string HTML output
     */
    public function render($currentUrl = '', $class = '')
    {
        $currentLang = $this->translation->getCurrentLanguage();
        $supportedLangs = $this->translation->getSupportedLanguages();
        $langNames = $this->translation->getLanguageNames();
        
        $html = '<div class="language-switcher ' . $class . '">';
        $html .= '<div class="relative inline-block text-left">';
        $html .= '<div>';
        $html .= '<button type="button" class="language-switcher-btn inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="language-menu-button" aria-expanded="true" aria-haspopup="true">';
        $html .= '<span class="mr-2">🌐</span>';
        $html .= '<span class="language-current">' . ($langNames[$currentLang] ?? $currentLang) . '</span>';
        $html .= '<svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">';
        $html .= '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '<div class="language-dropdown origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="language-menu-button" tabindex="-1">';
        $html .= '<div class="py-1" role="none">';
        
        foreach ($supportedLangs as $langCode) {
            $isActive = $langCode === $currentLang;
            $langName = $langNames[$langCode] ?? $langCode;
            $flag = $this->getLanguageFlag($langCode);
            
            $html .= '<a href="#" class="language-option ' . ($isActive ? 'bg-gray-100 text-gray-900' : 'text-gray-700') . ' group flex items-center px-4 py-2 text-sm hover:bg-gray-100 hover:text-gray-900" role="menuitem" data-lang="' . $langCode . '">';
            $html .= '<span class="mr-3 text-lg">' . $flag . '</span>';
            $html .= '<span>' . $langName . '</span>';
            if ($isActive) {
                $html .= '<svg class="ml-auto h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">';
                $html .= '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />';
                $html .= '</svg>';
            }
            $html .= '</a>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Add JavaScript for functionality
        $html .= $this->getJavaScript();
        
        return $html;
    }
    
    /**
     * Get language flag emoji
     * 
     * @param string $langCode Language code
     * @return string Flag emoji
     */
    private function getLanguageFlag($langCode)
    {
        $flags = [
            'es' => '🇪🇸',
            'it' => '🇮🇹',
            'en' => '🇺🇸'
        ];
        
        return $flags[$langCode] ?? '🌐';
    }
    
    /**
     * Get JavaScript for language switcher functionality
     * 
     * @return string JavaScript code
     */
    private function getJavaScript()
    {
        return '
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
        </script>';
    }
    
    /**
     * Handle language change request
     * 
     * @return bool Success status
     */
    public function handleLanguageChange()
    {
        if (isset($_POST['change_language'])) {
            $newLanguage = $_POST['change_language'];
            return $this->translation->setLanguage($newLanguage);
        }
        return false;
    }
}
