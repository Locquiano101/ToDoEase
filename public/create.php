<?php
session_start();

// Check authentication first
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../includes/header.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? '');
    $target_date = $_POST["target_date"] ?? '';
    $user_id = $_SESSION["user_id"];
    $invite_users = $_POST["invite_users"] ?? []; // now an array

    // Validation (same as before)
    if (empty($title) || empty($target_date)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $target_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $target_date);
    $now = new DateTime();
    if ($target_datetime <= $now) {
        $_SESSION['error'] = "Target date must be in the future.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert the task
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, target_date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $target_date]);
        $task_id = $pdo->lastInsertId();

        // Insert invites and notifications for each invited user
        if (!empty($invite_users)) {
            $stmtInvite = $pdo->prepare("INSERT INTO task_invites (task_id, invited_user_id) VALUES (?, ?)");
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, task_id, message, trigger_at) VALUES (?, ?, ?, ?)");

            foreach ($invite_users as $invited_user_id) {
                $stmtInvite->execute([$task_id, $invited_user_id]);
                $stmtNotif->execute([$invited_user_id, $task_id, "You were invited to a task: $title", $target_date]);
            }
        }

        $pdo->commit();

        $_SESSION['success'] = "Task added successfully!";
        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error adding task: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

?>

<div class="flex flex-col items-center justify-center w-screen h-screen">
    <div class="w-full max-w-2xl mx-auto rounded-2xl shadow-2xl overflow-hidden flex flex-col border-2 bg-white dark:bg-dark-surface border border-transparent dark:border-dark-border">
        <!-- Task Form -->
        <div class="p-8 md:p-12 md:w-full flex flex-col justify-center">
            <div class="mb-8 text-center">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-dark-text-primary">Add New Task</h2>
                <p class="text-gray-600 dark:text-dark-text-secondary mt-2">Fill in the details below to create a new task</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p class="text-red-700 dark:text-red-400"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p class="text-green-700 dark:text-green-400"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Task name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-text-primary mb-2">Task Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-tasks text-gray-400 dark:text-dark-text-muted"></i>
                        </div>
                        <input 
                            type="text" 
                            name="title" 
                            placeholder="What needs to be done?" 
                            required 
                            value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                            class="w-full border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary placeholder-gray-400 dark:placeholder-dark-text-muted rounded-lg p-3 pl-10 focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-blue-500 dark:focus:border-dark-accent-blue transition"
                        >
                    </div>
                </div>

                <!-- Target date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-text-primary mb-2">Target Date</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar-day text-gray-400 dark:text-dark-text-muted"></i>
                        </div>
                        <input 
                            type="datetime-local" 
                            name="target_date" 
                            required 
                            value="<?php echo isset($_POST['target_date']) ? htmlspecialchars($_POST['target_date']) : ''; ?>"
                            min="<?php echo date('Y-m-d\TH:i'); ?>"
                            class="w-full border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary rounded-lg p-3 pl-10 focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue focus:border-blue-500 dark:focus:border-dark-accent-blue transition" 
                        />
                    </div>
                </div>
                
                <!-- Invite users -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-text-primary mb-2">
                        Invite Users <span class="text-gray-500 dark:text-gray-400 font-normal">(optional)</span>
                    </label>
                    
                    <div class="border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated rounded-lg overflow-hidden">
                        <!-- Select All -->
                        <div class="border-b border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg px-4 py-2">
                            <label class="flex items-center cursor-pointer group">
                                <input 
                                    type="checkbox" 
                                    id="select_all_users"
                                    class="w-4 h-4 text-blue-600 bg-white dark:bg-dark-elevated border-gray-300 dark:border-dark-border rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue cursor-pointer"
                                    onclick="document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = this.checked)"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-dark-text-primary group-hover:text-gray-900 dark:group-hover:text-white">
                                    Select All
                                </span>
                            </label>
                        </div>
                        
                        <!-- User List -->
                        <div class="max-h-64 overflow-y-auto">
                            <?php
                            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id != ? ORDER BY name ASC");
                            $stmt->execute([$_SESSION['user_id']]);
                            
                            if ($stmt->rowCount() === 0) {
                                echo '<div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">';
                                echo '<svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>';
                                echo '</svg>';
                                echo '<p class="text-sm">No other users available</p>';
                                echo '</div>';
                            } else {
                                while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $userId = htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8');
                                    $userName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
                                    $userEmail = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
                                    
                                    echo '<label class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-dark-bg cursor-pointer transition border-b border-gray-100 dark:border-dark-border last:border-b-0 group">';
                                    echo '<input type="checkbox" name="invite_users[]" value="' . $userId . '" class="user-checkbox w-4 h-4 text-blue-600 bg-white dark:bg-dark-elevated border-gray-300 dark:border-dark-border rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue cursor-pointer">';
                                    echo '<div class="ml-3 flex-1">';
                                    echo '<div class="flex items-center gap-2">';
                                    echo '<div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">';
                                    echo strtoupper(substr($userName, 0, 1));
                                    echo '</div>';
                                    echo '<div>';
                                    echo '<p class="text-sm font-medium text-gray-900 dark:text-dark-text-primary group-hover:text-blue-600 dark:group-hover:text-dark-accent-blue">' . $userName . '</p>';
                                    echo '<p class="text-xs text-gray-500 dark:text-gray-400">' . $userEmail . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</label>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Select users to invite them to this project</span>
                    </p>
                </div>
                
                <!-- Buttons -->
                <div class="flex justify-between items-center pt-4">
                    <a href="index.php" class="flex items-center text-gray-600 dark:text-dark-text-secondary hover:text-gray-800 dark:hover:text-dark-text-primary transition">
                        <i class="fas fa-arrow-left mr-2"></i> 
                        Back to Tasks
                    </a>
                    <button 
                        type="submit" 
                        class="bg-gradient-to-r from-blue-500 to-purple-600 dark:from-dark-accent-blue dark:to-dark-accent-purple hover:from-blue-600 hover:to-purple-700 dark:hover:from-blue-600 dark:hover:to-purple-700 text-white font-medium py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition flex items-center"
                    >
                        <i class="fas fa-plus-circle mr-2"></i> 
                        Add Task
                    </button>
                </div>
            </form>

            <!-- Statistics -->
            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-dark-border">
                <div class="flex justify-center space-x-4">
                    <div class="text-center px-4">
                        <div class="text-2xl font-bold text-gray-800 dark:text-dark-text-primary">
                            <?php 
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE user_id = ?");
                                $stmt->execute([$_SESSION["user_id"]]);
                                echo htmlspecialchars($stmt->fetchColumn());
                            } catch (PDOException $e) {
                                error_log("Error fetching task count: " . $e->getMessage());
                                echo "0";
                            }
                            ?>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-dark-text-secondary">Total Tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>