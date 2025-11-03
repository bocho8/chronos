<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

// Handle language change
$languageSwitcher->handleLanguageChange();
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('error_500_title'); ?> - <?php _e('app_name'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <header class="bg-navy text-white h-[60px] flex items-center">
        <div class="w-full grid grid-cols-3 items-center px-4 h-full">
            <div class="flex items-center gap-2.5">
                <img src="/assets/images/LogoScuola.png" alt="Scuola Italiana" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg">Scuola Italiana</span>
            </div>
            <h1 class="m-0 text-center text-xl md:text-[22px] font-bold"><?php _e('app_name'); ?></h1>
            <div class="flex items-center gap-2 justify-end">
                <?php echo $languageSwitcher->render('', 'mr-4'); ?>
            </div>
        </div>
    </header>

    <main class="flex items-center justify-center min-h-[calc(100vh-60px)] p-4 md:p-6">
        <div class="text-center max-w-md mx-auto w-full">
            <div class="mb-6 md:mb-8">
                <h1 class="text-6xl md:text-9xl font-bold text-red-600 mb-4">500</h1>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?php _e('error_500_heading'); ?></h2>
                <p class="text-base md:text-lg text-gray-600 mb-6 md:mb-8 px-4"><?php _e('error_500_message'); ?></p>
            </div>
            
            <div class="space-y-4">
                <a href="/" class="inline-block bg-navy text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#142852] transition-colors">
                    <?php _e('error_go_home'); ?>
                </a>
                <div class="text-sm text-gray-500">
                    <button onclick="history.back()" class="text-navy hover:underline">
                        <?php _e('error_go_back'); ?>
                    </button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
