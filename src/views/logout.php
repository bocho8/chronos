<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

/**
 * Logout Page
 * Handles user logout with confirmation
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

initSecureSession();

$translation = Translation::getInstance();

if (!AuthHelper::isLoggedIn()) {
    header("Location: /src/views/login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
/** @var string $userName */
$userName = AuthHelper::getUserDisplayName() ?: 'Usuario';
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('app_name'); ?> — <?php _e('logout'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <!-- Header -->
    <header class="bg-navy text-white h-[60px] flex items-center">
        <div class="w-full grid grid-cols-3 items-center px-4 h-full">
            <!-- Left -->
            <div class="flex items-center gap-2.5">
                <img src="/assets/images/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
                <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
            </div>

            <!-- Center -->
            <h1 class="m-0 text-center text-xl md:text-[22px] font-bold"><?php _e('app_name'); ?></h1>

            <!-- Right -->
            <div class="flex items-center gap-2 justify-end">
                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-navy font-semibold">
                    <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex items-center justify-center min-h-[calc(100vh-60px)] p-4 md:p-6">
        <div class="w-full flex justify-center">
            <section class="bg-card rounded-2xl md:rounded-3xl p-6 md:p-10 lg:px-16 lg:py-10 w-full max-w-[500px] shadow-lg text-center">
                <div class="mb-6">
                    <div class="w-12 h-12 md:w-16 md:h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xs md:text-sm">📋</span>
                    </div>
                    <h2 class="text-xl md:text-2xl lg:text-[28px] font-extrabold text-navy mb-2"><?php _e('logout'); ?></h2>
                    <p class="text-sm md:text-base text-gray-600 mb-6">
                        <?php echo sprintf(__("logout_confirm_user"), htmlspecialchars($userName)); ?>
                    </p>
                </div>

                <div class="space-y-3 md:space-y-4">
                    <form method="POST" action="/src/controllers/LogoutController.php" class="inline-block w-full">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="w-full h-10 md:h-11 px-4 py-2 md:py-2.5 rounded-lg border-0 font-bold cursor-pointer bg-red-600 text-white hover:bg-red-700 transition-colors text-sm md:text-base">
                            <?php _e('logout'); ?>
                        </button>
                    </form>
                    
                    <a href="javascript:history.back()" class="block w-full h-10 md:h-11 px-4 py-2 md:py-2.5 rounded-lg border border-gray-300 font-bold cursor-pointer bg-white text-gray-700 hover:bg-gray-50 transition-colors text-center text-sm md:text-base">
                        <?php _e('cancel'); ?>
                    </a>
                </div>
            </section>
        </div>
    </main>

    <script>

        let countdown = 30;
        const countdownElement = document.createElement('div');
        countdownElement.className = 'text-sm text-gray-500 mt-4';
        document.querySelector('section').appendChild(countdownElement);
        
        const updateCountdown = () => {
            countdownElement.textContent = `<?php _e('auto_logout_in'); ?> ${countdown} <?php _e('seconds'); ?>`;
            countdown--;
            
            if (countdown < 0) {
                document.querySelector('form').submit();
            }
        };
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
