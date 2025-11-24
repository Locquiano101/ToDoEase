<?php
include("../config/database.php");
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../includes/header.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $emailExists = $stmt->fetchColumn();

    if ($emailExists > 0) {
        $error = "An account with this email already exists. Please use a different email or login.";
    } else {
        // Email doesn't exist, proceed with registration
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $password]);

        $_SESSION["user_id"] = $pdo->lastInsertId();
        $_SESSION["user_name"] = $name;
        header("Location: index.php");
        exit;
    }
}
?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="login-container bg-white dark:bg-dark-surface rounded-2xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row shadow-2xl border border-transparent dark:border-dark-border">
        <!-- Left Side - Welcome Text -->
        <div class="md:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-700 dark:from-dark-accent-blue dark:to-indigo-800 text-white p-8 md:p-12 flex flex-col justify-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Join Us Today!</h1>
            <p class="text-blue-100 dark:text-blue-200 mb-6">Create your account and start your journey with our platform.</p>
            
            <div class="space-y-4 mt-4">
                <div class="feature-item flex items-center bg-blue-500/20 dark:bg-blue-500/30 p-3 rounded-lg backdrop-blur-sm">
                    <i class="fas fa-rocket text-blue-200 dark:text-blue-100 mr-3"></i>
                    <span>Get started in seconds</span>
                </div>
                <div class="feature-item flex items-center bg-blue-500/20 dark:bg-blue-500/30 p-3 rounded-lg backdrop-blur-sm">
                    <i class="fas fa-chart-line text-blue-200 dark:text-blue-100 mr-3"></i>
                    <span>Grow with our community</span>
                </div>
            </div>
        </div>

        <!-- Right Side - Registration Form -->
        <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-dark-text-primary">Create Account</h2>
                <p class="text-gray-600 dark:text-dark-text-secondary mt-2">Fill in your details to get started</p>
            </div>

            <form method="POST" class="space-y-6">
                <div class="space-y-4">
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-dark-text-muted"></i>
                        <input 
                            name="name" 
                            type="text" 
                            placeholder="Enter your full name" 
                            required 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary placeholder-gray-400 dark:placeholder-dark-text-muted rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-transparent transition-all duration-200"
                        >
                    </div>
                    
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-dark-text-muted"></i>
                        <input 
                            name="email" 
                            type="email" 
                            placeholder="Enter your email" 
                            required 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary placeholder-gray-400 dark:placeholder-dark-text-muted rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-transparent transition-all duration-200"
                        >
                    </div>
                    
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-dark-text-muted"></i>
                        <input 
                            name="password" 
                            type="password" 
                            placeholder="Create a password" 
                            required 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary placeholder-gray-400 dark:placeholder-dark-text-muted rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-transparent transition-all duration-200"
                        >
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-dark-accent-blue dark:to-indigo-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 dark:hover:from-blue-700 dark:hover:to-indigo-800 focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 transition-all duration-200 transform hover:-translate-y-0.5"
                >
                    Create Account
                </button>

                <div class="text-center mt-6">
                    <p class="text-gray-600 dark:text-dark-text-secondary">
                        Already have an account? 
                        <a href="login.php" class="text-blue-600 dark:text-dark-accent-blue font-semibold hover:text-blue-800 dark:hover:text-blue-400 transition-colors duration-200">
                            Sign in here
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>