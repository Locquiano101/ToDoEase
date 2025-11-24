<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../../includes/header.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate and sanitize email from GET parameter
$to_email = '';
if (isset($_GET['to']) && filter_var($_GET['to'], FILTER_VALIDATE_EMAIL)) {
    $to_email = htmlspecialchars($_GET['to'], ENT_QUOTES, 'UTF-8');
}

$task_title = '';
if (isset($_GET['title'])) {
    // Decode URL safely
    $task_title = htmlspecialchars($_GET['title'], ENT_QUOTES, 'UTF-8');
}
?>

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-lg mb-4">
                <i class="fas fa-paper-plane text-2xl text-white"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mb-2">
                Send Email
            </h2>
            <p class="text-gray-600 dark:text-slate-400">
                Compose and send your message
            </p>
        </div>

        <!-- Email Form Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
            <form action="send_email.php" method="post" class="p-6 sm:p-8 space-y-6">
                <!-- Recipient Email Field -->
                <div class="space-y-2">
                    <label for="to_email" class="flex items-center text-sm font-semibold text-gray-700 dark:text-slate-200">
                        <i class="fas fa-envelope mr-2 text-blue-500"></i>
                        Recipient Email
                    </label>
                    <div class="relative">
                        <input 
                            type="email" 
                            id="to_email"
                            name="to_email" 
                            value="<?= $to_email ?>" 
                            required
                            maxlength="255"
                            placeholder="recipient@example.com"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 outline-none"
                        >
                        <div class="absolute right-3 top-3.5 text-gray-400 dark:text-slate-500">
                            <i class="fas fa-at"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Subject Field -->  
                <div class="space-y-2">
                    <label for="subject" class="flex items-center text-sm font-semibold text-gray-700 dark:text-slate-200">
                        <i class="fas fa-heading mr-2 text-purple-500"></i>
                        Subject
                    </label>
                    <input 
                        type="text" 
                        id="subject"
                        name="subject" 
                        value="<?= htmlspecialchars($task_title ? 'Reminder for the task ['.$task_title.']' : '', ENT_QUOTES, 'UTF-8') ?>" 
                        placeholder="Enter email subject" 
                        required
                        maxlength="255"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 outline-none"
                    >
                </div>

                <!-- Message Field -->
                <div class="space-y-2">
                    <label for="message" class="flex items-center text-sm font-semibold text-gray-700 dark:text-slate-200">
                        <i class="fas fa-align-left mr-2 text-green-500"></i>
                        Message
                    </label>
                    <textarea 
                        id="message"
                        name="message" 
                        rows="8" 
                        placeholder="Type your message here..."
                        required
                        maxlength="5000"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 outline-none resize-none"
                    ></textarea>
                    <p class="text-xs text-gray-500 dark:text-slate-400 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Maximum 5000 characters
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button 
                        type="submit"
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                    >
                        <i class="fas fa-paper-plane"></i>
                        Send Email
                    </button>
                    
                    <button 
                        type="reset"
                        class="px-6 py-3.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                    >
                        <i class="fas fa-redo mr-2"></i>
                        Reset
                    </button>
                    
                    <a 
                        href="javascript:history.back()"
                        class="px-6 py-3.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 text-center"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
