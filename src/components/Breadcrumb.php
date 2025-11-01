<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Breadcrumb Component
 * Implements RF073: Breadcrumb navigation on multi-level pages
 */
class Breadcrumb {
    private $items;
    
    public function __construct($items = []) {
        $this->items = $items;
    }
    
    /**
     * Render breadcrumb navigation
     */
    public function render() {
        if (empty($this->items)) {
            return '';
        }
        
        $html = '<nav class="flex mb-4 md:mb-6" aria-label="Breadcrumb">';
        $html .= '<ol class="inline-flex items-center space-x-1 md:space-x-3">';
        
        foreach ($this->items as $index => $item) {
            if ($index === 0) {
                // First item (usually Dashboard/Home)
                $html .= '<li class="inline-flex items-center">';
                $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors">';
                $html .= '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">';
                $html .= '<path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>';
                $html .= '</svg>';
                $html .= htmlspecialchars($item['label']);
                $html .= '</a>';
                $html .= '</li>';
            } else {
                $html .= '<li>';
                $divClass = 'flex items-center';
                $spanClass = 'ml-1 text-sm font-medium text-gray-500 md:ml-2';
                
                if ($index === count($this->items) - 1) {
                    // Last item (current page) - not a link
                    $html .= '<div class="' . $divClass . '">';
                    $html .= '<svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">';
                    $html .= '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>';
                    $html .= '</svg>';
                    $html .= '<span class="' . $spanClass . '">' . htmlspecialchars($item['label']) . '</span>';
                    $html .= '</div>';
                } else {
                    // Middle items - links
                    $html .= '<div class="' . $divClass . '">';
                    $html .= '<svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">';
                    $html .= '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>';
                    $html .= '</svg>';
                    $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="' . $spanClass . ' hover:text-gray-700 transition-colors">';
                    $html .= htmlspecialchars($item['label']);
                    $html .= '</a>';
                    $html .= '</div>';
                }
                $html .= '</li>';
            }
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Static method to create breadcrumb for common admin pages
     */
    public static function forAdmin($items = []) {
        $defaultItems = [
            [
                'label' => _e('dashboard') ?? 'Dashboard',
                'url' => '/admin/dashboard'
            ]
        ];
        
        return new self(array_merge($defaultItems, $items));
    }
}

