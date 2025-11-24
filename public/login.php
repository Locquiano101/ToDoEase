<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$error = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if account is suspended
            if ($user['status'] === 'suspended') {
                // Check if suspension period has ended
                $currentDate = date('Y-m-d');
                $suspensionUntil = $user['suspension_until'];
                
                if ($suspensionUntil && $suspensionUntil > $currentDate) {
                    $error = "Your account has been suspended until " . date('F j, Y', strtotime($suspensionUntil)) . ". Please contact support.";
                } else {
                    // Suspension period has ended, auto-reactivate account
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active', suspension_until = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $user['status'] = 'active';
                    $user['suspension_until'] = null;
                }
            }

            // Verify password and check if account is active
            if ($user['status'] === 'active' && password_verify($password, $user["password"])) {
                // Store user info in session
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_role"] = $user["role"];

                // Redirect based on role
                if ($user["role"] === "admin") {
                    header("Location: admin/admin.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } elseif ($user['status'] === 'suspended') {
                // Account is still suspended
                $suspensionUntil = $user['suspension_until'];
                if ($suspensionUntil) {
                    $error = "Your account has been suspended until " . date('F j, Y', strtotime($suspensionUntil)) . ". Please contact support.";
                } else {
                    $error = "Your account has been suspended. Please contact support.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="login-container bg-white dark:bg-dark-surface rounded-2xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row shadow-2xl border border-transparent dark:border-dark-border">
        <!-- Left Side - Welcome Text -->
        <div class="md:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-700 dark:from-dark-accent-blue dark:to-indigo-800 text-white p-8 md:p-12 flex flex-col justify-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Welcome Back!</h1>
            <p class="text-blue-100 dark:text-blue-200 mb-6">Sign in to access your account and continue your journey with us.</p>
            
            <div class="space-y-4 mt-4">
                <div class="feature-item flex items-center bg-blue-500/20 dark:bg-blue-500/30 p-3 rounded-lg backdrop-blur-sm">
                    <i class="fas fa-bolt text-blue-200 dark:text-blue-100 mr-3"></i>
                    <span>Fast and reliable access</span>
                </div>
                <div class="feature-item flex items-center bg-blue-500/20 dark:bg-blue-500/30 p-3 rounded-lg backdrop-blur-sm">
                    <i class="fas fa-users text-blue-200 dark:text-blue-100 mr-3"></i>
                    <span>Join our growing community</span>
                </div>
                <div class="feature-item flex items-center bg-blue-500/20 dark:bg-blue-500/30 p-3 rounded-lg backdrop-blur-sm">
                    <i class="fas fa-shield-alt text-blue-200 dark:text-blue-100 mr-3"></i>
                    <span>Secure and protected</span>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-dark-text-primary">Sign In</h2>
                <p class="text-gray-600 dark:text-dark-text-secondary mt-2">Enter your credentials to continue</p>
            </div>

            <form method="POST" class="space-y-6">
                <div class="space-y-4">
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-dark-text-muted"></i>
                        <input 
                            name="email" 
                            type="email" 
                            placeholder="Enter your email" 
                            required 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary placeholder-gray-400 dark:placeholder-dark-text-muted rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-transparent transition-all duration-200"
                        >
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-dark-text-muted"></i>
                        <input 
                            id="password"
                            name="password" 
                            type="password" 
                            placeholder="Enter your password" 
                            required 
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary placeholder-gray-400 dark:placeholder-dark-text-muted rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-transparent transition-all duration-200"
                        >
                        <i id="togglePassword" class="fas fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-dark-text-muted cursor-pointer"></i>
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
                    Sign In
                </button>

                <div class="text-center mt-6">
                    <p class="text-gray-600 dark:text-dark-text-secondary">
                        Don't have an account? 
                        <a href="register.php" class="text-blue-600 dark:text-dark-accent-blue font-semibold hover:text-blue-800 dark:hover:text-blue-400 transition-colors duration-200">
                            Create one here
                        </a>
                    </p>
                </div>             
            </form>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);

        // Toggle eye / eye-slash icon
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>