<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    // If .env file doesn't exist, use environment variables from the container
}

// Database connection
$dbPath = getenv('DATABASE_PATH') ?: __DIR__ . '/../database/tweets.db';
$db = new SQLite3($dbPath);

// Ensure database directory exists
$dbDir = dirname($dbPath);
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Fetch tweets from database
$tweets = [];
$results = $db->query('SELECT * FROM tweets ORDER BY created_at DESC');
while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    $tweets[] = $row;
}

// Move bill.jpg to public directory if it doesn't exist
if (!file_exists(__DIR__ . '/bill.jpg') && file_exists(__DIR__ . '/../bill.jpg')) {
    copy(__DIR__ . '/../bill.jpg', __DIR__ . '/bill.jpg');
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friend of a Global Economy - Bill Ackman's Insights</title>
    <meta name="description" content="Explore Bill Ackman's latest insights and perspectives on global economy, markets, and investment strategies.">
    <meta name="keywords" content="Bill Ackman, Pershing Square, Investment, Global Economy, Markets">
    <meta name="author" content="Friend of a Global Economy">
    <meta property="og:title" content="Friend of a Global Economy - Bill Ackman's Insights">
    <meta property="og:description" content="Explore Bill Ackman's latest insights and perspectives on global economy, markets, and investment strategies.">
    <meta property="og:image" content="/bill.jpg">
    <meta property="og:url" content="https://friendofglobaleconomy.com">
    <meta name="twitter:card" content="summary_large_image">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --brand-primary: #1D9BF0;
            --brand-secondary: #0F5D91;
            --brand-accent: #FFD700;
            --mouse-x: 50%;
            --mouse-y: 50%;
            --scroll-offset: 0;
            --bg-color: #ffffff;
        }

        .dark {
            --bg-color: #000000;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            perspective: 1000px;
            overflow-x: hidden;
            letter-spacing: -0.01em;
            font-feature-settings: "ss01" 1, "ss02" 1, "ss03" 1;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(29, 155, 240, 0.2);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(29, 155, 240, 0.4);
        }
        
        .tweet-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            padding: 3rem 2rem;
            perspective: 2000px;
            transform-style: preserve-3d;
            position: relative;
            z-index: 1;
        }
        
        #modal-container {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .tweet {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #eff3f4;
            border-radius: 16px;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            transform-style: preserve-3d;
            position: relative;
            box-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.1);
            transform-origin: center;
            will-change: transform;
            backface-visibility: hidden;
            opacity: 0;
            transform: translateY(20px) translateZ(var(--scroll-offset)) 
                      scale(calc(1 + var(--scroll-offset) * 0.001));
            height: fit-content;
            min-height: 150px;
            display: flex;
            flex-direction: column;
        }

        .tweet .tweet-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .tweet.visible {
            opacity: 1;
            transform: translateY(0) translateZ(var(--scroll-offset))
                      scale(calc(1 + var(--scroll-offset) * 0.001));
        }

        .dark .tweet {
            background: rgba(30, 39, 50, 0.95);
            border-color: #2f3336;
            box-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.4);
        }
        
        .tweet:hover {
            transform: translateY(-15px) scale(1.02) rotateX(5deg) rotateY(-5deg);
            box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .tweet.expanded {
            position: relative;
            transform: none;
            width: 90%;
            max-width: 600px;
            margin: 0;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            cursor: auto;
            background: var(--bg-color);
            pointer-events: auto;
            animation: modal-appear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-style: preserve-3d;
            will-change: transform;
        }

        @keyframes modal-appear {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .tweet.expanded .close-button {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bg-color);
            border: 1px solid #eff3f4;
            color: #536471;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            animation: button-appear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            animation-delay: 0.1s;
        }

        @keyframes button-appear {
            from {
                opacity: 0;
                transform: scale(0.8) rotate(-90deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }

        .tweet.expanded .tweet-text {
            animation: content-appear 0.4s ease-out;
        }

        @keyframes content-appear {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dark .tweet.expanded {
            background: rgb(30, 39, 50);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .dark .tweet.expanded .close-button {
            background: rgb(30, 39, 50);
            border-color: #2f3336;
            color: #71767b;
        }

        .dark .tweet.expanded .close-button:hover {
            background: rgb(39, 51, 64);
            color: #fff;
        }

        .tweet::before {
            content: '';
            position: absolute;
            inset: -1px;
            background: linear-gradient(45deg, rgba(29, 155, 240, 0.1), transparent, rgba(29, 155, 240, 0.1));
            border-radius: inherit;
            z-index: -1;
            transition: all 0.5s;
            opacity: 0;
        }

        .tweet:hover::before {
            opacity: 1;
        }

        .tweet::after {
            content: '';
            position: absolute;
            inset: -20px;
            background: radial-gradient(circle at center, rgba(29, 155, 240, 0.1), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .tweet:hover::after {
            opacity: 1;
        }

        .verified-badge {
            color: rgb(29, 155, 240);
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .verified-badge::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            font-size: 8px;
            color: white;
        }

        .dark .verified-badge {
            color: rgb(41, 163, 245);
        }

        .tweet-text {
            font-size: 15px;
            line-height: 1.6;
            margin-top: 0.5rem;
            letter-spacing: -0.01em;
            position: relative;
            transition: all 0.3s ease;
        }

        .tweet-text.truncated {
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
        }

        .tweet-text.truncated::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, transparent, var(--bg-color));
            pointer-events: none;
        }

        .dark .tweet-text.truncated::after {
            background: linear-gradient(to bottom, transparent, rgb(30, 39, 50));
        }

        .read-more-btn {
            color: var(--brand-primary);
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .read-more-btn:hover {
            opacity: 0.8;
        }

        .read-more-btn i {
            font-size: 12px;
            margin-left: 4px;
            transition: transform 0.2s ease;
        }

        .read-more-btn.expanded i {
            transform: rotate(180deg);
        }

        .tweet-date {
            color: rgb(83, 100, 113);
            font-size: 15px;
            text-decoration: none;
        }

        .tweet-date:hover {
            text-decoration: underline;
        }

        .dark .tweet-date {
            color: rgb(139, 152, 165);
        }

        .profile-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .main-content {
            min-height: calc(100vh - 140px);
            background: 
                radial-gradient(circle at var(--mouse-x) var(--mouse-y), 
                    rgba(29, 155, 240, 0.15), 
                    transparent 50%),
                linear-gradient(45deg, 
                    rgba(29, 155, 240, 0.05) 0%, 
                    rgba(0, 0, 0, 0) 70%);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .main-content::before {
            content: '';
            position: absolute;
            inset: -50%;
            background: radial-gradient(
                circle at center,
                rgba(29, 155, 240, 0.1) 0%,
                transparent 70%
            );
            animation: rotate 20s linear infinite;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            z-index: 998;
        }

        .overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotateX(0) rotateY(0);
            }
            25% {
                transform: translateY(-10px) rotateX(2deg) rotateY(-2deg);
            }
            50% {
                transform: translateY(5px) rotateX(-1deg) rotateY(1deg);
            }
            75% {
                transform: translateY(-5px) rotateX(1deg) rotateY(-1deg);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .tweet.loading {
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0.1),
                rgba(255, 255, 255, 0.2),
                rgba(255, 255, 255, 0.1)
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @media (max-width: 768px) {
            .tweet-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 1.5rem;
            }

            .tweet {
                animation: none;
            }

            .tweet:hover {
                transform: translateY(-5px);
            }

            .tweet.expanded {
                width: 95%;
            }
        }

        canvas#particle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        /* Brand Colors */
        :root {
            --brand-primary: #1D9BF0;
            --brand-secondary: #0F5D91;
            --brand-accent: #FFD700;
            --mouse-x: 50%;
            --mouse-y: 50%;
            --scroll-offset: 0;
            --bg-color: #ffffff;
        }

        .brand-gradient {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-border {
            position: relative;
        }

        .brand-border::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--brand-primary), var(--brand-secondary));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .brand-border:hover::after {
            transform: scaleX(1);
        }

        .quote-mark {
            font-family: Georgia, serif;
            font-size: 4em;
            height: 40px;
            line-height: 1;
            color: var(--brand-primary);
            opacity: 0.1;
            position: absolute;
            transform: translateY(-50%);
        }

        .quote-mark-left {
            left: -20px;
            top: 50%;
        }

        .quote-mark-right {
            right: -20px;
            top: 50%;
            transform: translateY(-50%) rotate(180deg);
        }

        .disclaimer-banner {
            background: rgba(255, 244, 229, 0.98);
            border-bottom: 1px solid #f3d03e;
            padding: 0.75rem 1rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            backdrop-filter: blur(8px);
            font-size: 0.875rem;
            color: #92400e;
        }

        @media (max-width: 640px) {
            .disclaimer-banner {
                padding: 0.5rem;
                font-size: 0.75rem;
                line-height: 1.2;
            }

            .disclaimer-banner i {
                display: none;
            }

            .nav-content {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .nav-logo-text {
                font-size: 0.875rem;
            }
        }

        .dark .disclaimer-banner {
            background: rgba(45, 35, 20, 0.98);
            border-bottom: 1px solid #854d0e;
            color: #fbbf24;
        }

        /* Add top margin to main content to account for fixed elements */
        .main-content {
            padding-top: calc(2.5rem + 64px); /* Banner height + Nav height */
        }

        @media (max-width: 640px) {
            .main-content {
                padding-top: calc(3rem + 56px); /* Adjusted for mobile */
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300" x-data="{ darkMode: false, isMenuOpen: false }" :class="{ 'dark': darkMode }">
    <canvas id="particle-canvas"></canvas>
    <div id="overlay" class="overlay"></div>
    <div id="modal-container"></div>

    <!-- Disclaimer Banner -->
    <div class="disclaimer-banner">
        <p class="font-medium">
            <i class="fas fa-info-circle mr-2"></i>
            <span>This is a fan-made website and is not affiliated with Bill Ackman or Pershing Square Capital Management.</span>
        </p>
    </div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 mt-[2.5rem] sm:mt-[2.75rem] bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 sm:h-16 nav-content">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2 sm:space-x-3">
                        <div class="relative w-8 sm:w-10 h-8 sm:h-10 rounded-full overflow-hidden border-2 border-blue-500">
                            <img src="/bill.jpg" alt="Bill Ackman" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 block nav-logo-text">Bill Ackman</span>
                            <span class="text-sm sm:text-lg font-bold brand-gradient nav-logo-text">Friend of a Global Economy</span>
                        </div>
                    </a>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <button @click="darkMode = !darkMode" class="p-1.5 sm:p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i :class="darkMode ? 'fas fa-sun' : 'fas fa-moon'" class="text-gray-600 dark:text-gray-300 text-sm sm:text-base"></i>
                    </button>
                    <a href="#contact" class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        Contact
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white dark:from-gray-900 dark:to-gray-800 opacity-50"></div>
        <div class="max-w-7xl mx-auto text-center relative z-10">
            <div class="inline-block mb-4">
                <div class="relative">
                    <img src="/bill.jpg" alt="Bill Ackman" class="w-32 h-32 rounded-full border-4 border-white dark:border-gray-800 shadow-xl mx-auto mb-6">
                    <div class="absolute -bottom-2 -right-2 bg-blue-600 text-white p-2 rounded-full shadow-lg">
                        <i class="fas fa-globe text-xl"></i>
                    </div>
                </div>
            </div>
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                Championing a 
                <span class="brand-gradient">Global Economy</span>
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-8 relative">
                <span class="quote-mark quote-mark-left">"</span>
                Exploring perspectives on markets, investments, and fostering economic growth for a better world.
                <span class="quote-mark quote-mark-right">"</span>
            </p>
            <div class="flex justify-center space-x-4">
                <a href="#tweets" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-stream mr-2"></i>
                    Latest Insights
                </a>
                <a href="https://twitter.com/BillAckman" target="_blank" class="px-8 py-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-lg hover:shadow-xl">
                    <i class="fab fa-twitter mr-2 text-blue-400"></i>
                    Follow Journey
                </a>
            </div>
        </div>
    </section>

    <!-- Mission Statement -->
    <section class="py-16 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 rounded-xl bg-gradient-to-br from-blue-50 to-white dark:from-gray-700 dark:to-gray-800 shadow-lg">
                    <i class="fas fa-chart-line text-4xl text-blue-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Market Insights</h3>
                    <p class="text-gray-600 dark:text-gray-300">Deep analysis and perspectives on global market trends and opportunities.</p>
                </div>
                <div class="p-6 rounded-xl bg-gradient-to-br from-blue-50 to-white dark:from-gray-700 dark:to-gray-800 shadow-lg">
                    <i class="fas fa-handshake text-4xl text-blue-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Economic Partnership</h3>
                    <p class="text-gray-600 dark:text-gray-300">Building bridges between markets and fostering global economic cooperation.</p>
                </div>
                <div class="p-6 rounded-xl bg-gradient-to-br from-blue-50 to-white dark:from-gray-700 dark:to-gray-800 shadow-lg">
                    <i class="fas fa-lightbulb text-4xl text-blue-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Future Vision</h3>
                    <p class="text-gray-600 dark:text-gray-300">Shaping tomorrow's economy through innovative thinking and strategic insights.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tweets Section -->
    <section id="tweets" class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-2">Global Economic Insights</h2>
            <p class="text-gray-600 dark:text-gray-400 text-center mb-12 max-w-2xl mx-auto">Curated thoughts and analyses on markets, economics, and global opportunities.</p>
            <div class="tweet-container">
                <?php foreach ($tweets as $tweet): ?>
                    <div class="tweet" x-data="{ expanded: false, isLongText: false }" 
                         x-init="isLongText = $refs.content.scrollHeight > 96">
                        <div class="p-6 tweet-content">
                            <div class="flex items-center space-x-4 mb-3">
                                <img src="/bill.jpg" alt="Bill Ackman" class="h-10 w-10 rounded-full">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Bill Ackman</h3>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">@BillAckman</p>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed mb-2 whitespace-pre-line" 
                                   x-ref="content"
                                   :class="{ 'truncated': isLongText && !expanded }"><?php echo nl2br(htmlspecialchars($tweet['content'])); ?></p>
                                
                                <template x-if="isLongText">
                                    <button @click.stop="expanded = !expanded" 
                                            class="read-more-btn text-sm"
                                            :class="{ 'expanded': expanded }">
                                        <span x-text="expanded ? 'Show less' : 'Show more'"></span>
                                        <i class="fas fa-chevron-down ml-1"></i>
                                    </button>
                                </template>
                            </div>
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <span class="text-sm"><?php echo date('M j, Y', strtotime($tweet['created_at'])); ?></span>
                                <div class="flex space-x-4">
                                    <button class="hover:text-blue-600 transition-colors text-sm">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="hover:text-blue-600 transition-colors text-sm">
                                        <i class="far fa-comment"></i>
                                    </button>
                                    <button class="hover:text-blue-600 transition-colors text-sm">
                                        <i class="far fa-share-square"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-100 dark:bg-gray-800">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">Get in Touch</h2>
            <form class="space-y-6" action="/contact.php" method="POST">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" id="name" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" id="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                    <textarea id="message" name="message" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
                <div class="text-center">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <div class="relative w-12 h-12 rounded-full overflow-hidden border-2 border-blue-500">
                        <img src="/bill.jpg" alt="Bill Ackman" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400 block">Bill Ackman</span>
                        <span class="text-lg font-bold brand-gradient">Friend of a Global Economy</span>
                    </div>
                </div>
                <div class="flex space-x-6">
                    <a href="https://twitter.com/BillAckman" target="_blank" class="text-gray-400 hover:text-blue-600 transition-colors">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="https://www.linkedin.com/in/billackman" target="_blank" class="text-gray-400 hover:text-blue-600 transition-colors">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                </div>
            </div>
            <div class="mt-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">&copy; <?php echo date('Y'); ?> Friend of a Global Economy. All rights reserved.</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Championing economic growth and global prosperity.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize dark mode based on system preference
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            Alpine.store('darkMode', true);
        }

        // Particle system
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const particles = [];
        const particleCount = 50;

        class Particle {
            constructor() {
                this.x = Math.random() * window.innerWidth;
                this.y = Math.random() * window.innerHeight;
                this.vx = Math.random() * 2 - 1;
                this.vy = Math.random() * 2 - 1;
                this.size = Math.random() * 3 + 1;
            }

            update() {
                this.x += this.vx;
                this.y += this.vy;
                
                if (this.x < 0 || this.x > window.innerWidth) this.vx *= -1;
                if (this.y < 0 || this.y > window.innerHeight) this.vy *= -1;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(29, 155, 240, 0.1)';
                ctx.fill();
            }
        }

        // Initialize particles
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }
        animate();

        // Track mouse position for gradient
        document.addEventListener('mousemove', (e) => {
            document.documentElement.style.setProperty('--mouse-x', e.clientX + 'px');
            document.documentElement.style.setProperty('--mouse-y', e.clientY + 'px');
        });

        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.tweet').forEach(tweet => {
            observer.observe(tweet);
        });

        // Enhanced magnetic hover effect
        function addMagneticEffect(tweet) {
            let isExpanded = tweet.classList.contains('expanded');
            let intensity = isExpanded ? 3 : 10; // Reduced intensity for expanded state
            let translateZ = isExpanded ? 20 : 10;
            
            tweet.addEventListener('mousemove', (e) => {
                if (tweet.classList.contains('expanded')) {
                    intensity = 3;
                    translateZ = 20;
                }
                
                const rect = tweet.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const deltaX = (x - centerX) / centerX;
                const deltaY = (y - centerY) / centerY;
                
                const transform = `
                    perspective(1000px)
                    rotateX(${deltaY * intensity}deg)
                    rotateY(${deltaX * intensity}deg)
                    translateZ(${translateZ}px)
                    ${isExpanded ? 'scale(1)' : ''}
                `;
                
                tweet.style.transform = transform;
            });
            
            tweet.addEventListener('mouseleave', () => {
                if (tweet.classList.contains('expanded')) {
                    tweet.style.transform = 'perspective(1000px) translateZ(0) scale(1)';
                } else {
                    tweet.style.transform = '';
                }
            });
        }

        // Click to expand
        document.querySelectorAll('.tweet').forEach(tweet => {
            const closeButton = tweet.querySelector('.close-button');
            addMagneticEffect(tweet);
            
            tweet.addEventListener('click', (e) => {
                if (e.target.closest('.close-button')) {
                    return;
                }
                
                const overlay = document.getElementById('overlay');
                const modalContainer = document.getElementById('modal-container');
                const tweetText = tweet.querySelector('.tweet-text');
                
                if (!tweet.classList.contains('expanded')) {
                    // Clone the tweet for the modal
                    const tweetClone = tweet.cloneNode(true);
                    tweetClone.classList.add('expanded');
                    
                    // Clear any existing modal content
                    modalContainer.innerHTML = '';
                    modalContainer.appendChild(tweetClone);
                    
                    // Show overlay
                    overlay.classList.add('active');
                    
                    // Add magnetic effect to the cloned tweet
                    addMagneticEffect(tweetClone);
                    
                    // Add event listener to close button on clone
                    const cloneCloseButton = tweetClone.querySelector('.close-button');
                    cloneCloseButton.classList.remove('hidden');
                    cloneCloseButton.addEventListener('click', () => {
                        modalContainer.innerHTML = '';
                        overlay.classList.remove('active');
                    });

                    // Add smooth transition back to flat state when mouse leaves
                    tweetClone.addEventListener('mouseleave', () => {
                        tweetClone.style.transition = 'transform 0.3s ease-out';
                        tweetClone.style.transform = 'perspective(1000px) translateZ(0) scale(1)';
                    });
                }
            });
        });

        // Close expanded tweet when clicking overlay
        document.getElementById('overlay').addEventListener('click', () => {
            const modalContainer = document.getElementById('modal-container');
            modalContainer.innerHTML = '';
            document.getElementById('overlay').classList.remove('active');
        });

        // Update scroll position for 3D effect
        window.addEventListener('scroll', () => {
            document.documentElement.style.setProperty('--scroll-offset', window.scrollY + 'px');
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const tweets = document.querySelectorAll('.tweet');
                tweets.forEach(tweet => {
                    tweet.style.transition = 'all 0.2s ease-out';
                });
            } else if (e.key === 'Escape') {
                const modalContainer = document.getElementById('modal-container');
                modalContainer.innerHTML = '';
                document.getElementById('overlay').classList.remove('active');
            }
        });
    </script>
</body>
</html> 