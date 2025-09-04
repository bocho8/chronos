<?php
/**
 * Logout Page
 * Handles user logout with confirmation
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Initialize secure session
initSecureSession();

// Initialize translation system
$translation = Translation::getInstance();

// Check if user is logged in
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
                <img src="/upload/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
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
    <main class="flex items-center justify-center min-h-[calc(100vh-60px)] p-6">
        <div class="w-full flex justify-center">
            <section class="bg-card rounded-3xl p-10 md:px-16 md:py-10 w-full max-w-[500px] shadow-lg text-center">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl md:text-[28px] font-extrabold text-navy mb-2"><?php _e('logout'); ?></h2>
                    <p class="text-gray-600 mb-6">
                        <?php echo sprintf(__("logout_confirm_user"), htmlspecialchars($userName)); ?>
                    </p>
                </div>

                <div class="space-y-4">
                    <form method="POST" action="/src/controllers/LogoutController.php" class="inline-block">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="w-full h-11 px-4 py-2.5 rounded-lg border-0 font-bold cursor-pointer bg-red-600 text-white hover:bg-red-700 transition-colors">
                            <?php _e('logout'); ?>
                        </button>
                    </form>
                    
                    <a href="javascript:history.back()" class="block w-full h-11 px-4 py-2.5 rounded-lg border border-gray-300 font-bold cursor-pointer bg-white text-gray-700 hover:bg-gray-50 transition-colors text-center">
                        <?php _e('cancel'); ?>
                    </a>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Auto-logout after 30 seconds if no action
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
