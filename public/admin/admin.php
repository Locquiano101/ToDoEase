<?php
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../../includes/header.php';

session_start();

// Prevent modifying the root admin (ID 12)
$ROOT_ADMIN_ID = 12;

// Redirect non-logged in users
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Check if the user is admin
$user_id = $_SESSION["user_id"];
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$isRoot = ($user_id == $ROOT_ADMIN_ID);

if (!$current_user || $current_user['role'] !== 'admin') {
    die("Access denied. You must be an admin to view this page.");
}

/* ============================
      ROLE MANAGEMENT
============================ */

// Promote
if (isset($_GET['promote_user'])) {
    $id = intval($_GET['promote_user']);
    if ($id != $ROOT_ADMIN_ID) {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$id]);
    }
    header("Location: admin.php");
    exit;
}

// Demote
if (isset($_GET['demote_user'])) {
    $id = intval($_GET['demote_user']);
    if ($id != $ROOT_ADMIN_ID && $id != $user_id) {
        $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$id]);
    }
    header("Location: admin.php");
    exit;
}

/* ============================
      ACCOUNT SUSPENSION
============================ */

// Suspend for 7 days
if (isset($_GET['suspend_user'])) {
    $id = intval($_GET['suspend_user']);
    if ($id != $ROOT_ADMIN_ID && $id != $user_id) {
        $suspensionDate = date('Y-m-d', strtotime("+7 days"));
        $pdo->prepare("UPDATE users SET status='suspended', suspension_until=? WHERE id=?")
            ->execute([$suspensionDate, $id]);
    }
    header("Location: admin.php");
    exit;
}

// Activate user
if (isset($_GET['activate_user'])) {
    $id = intval($_GET['activate_user']);
    if ($id != $ROOT_ADMIN_ID) {
        $pdo->prepare("UPDATE users SET status='active', suspension_until=NULL WHERE id=?")
            ->execute([$id]);
    }
    header("Location: admin.php");
    exit;
}

/* ============================
      DELETE USER
============================ */

if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    if ($id != $user_id && $id != $ROOT_ADMIN_ID) {
        // Delete user and all tasks they own
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM tasks WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM task_invites WHERE invited_user_id = ?")->execute([$id]);
    }
    header("Location: admin.php");
    exit;
}

/* ============================
      FETCH USERS AND TASKS
============================ */

// Fetch all users
$stmt = $pdo->query("SELECT id, email, name , role, status, suspension_until FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all tasks including invites
$stmt = $pdo->prepare("
    SELECT t.id AS task_id, t.title, t.is_done, t.target_date, t.user_id, u.email AS owner_email,
           GROUP_CONCAT(invited.invited_user_id) AS invited_user_ids
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN task_invites invited ON t.id = invited.task_id
    GROUP BY t.id
    ORDER BY t.is_done ASC, t.target_date ASC, t.created_at DESC
");
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
      HELPER FUNCTIONS
============================ */

// Format date for display
function formatDate($dateString) {
    if (!$dateString) return '-';
    $date = new DateTime($dateString);
    return $date->format('d/m/Y || H:i');
}

// Get invited user emails
function getInvitedUsers($invitedIds, $pdo) {
    if (!$invitedIds) return [];

    // Split and trim IDs
    $ids = array_map('trim', explode(',', $invitedIds));
    if (empty($ids)) return [];

    // Prepare placeholders for the query
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Fetch email and name
    $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    // Returns an array of associative arrays: [['email' => ..., 'name' => ...], ...]
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 py-8">
    <div class="w-full max-w-7xl mx-auto px-4">
        <!-- Header Section -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 p-8 mb-6 hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-crown text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent mb-2">Admin Dashboard</h1>
                        <p class="text-gray-600 dark:text-gray-400 flex items-center gap-2">
                            <i class="fas fa-shield-halved text-purple-500"></i>
                            Manage users and tasks across the platform
                        </p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="../index.php" 
                    class="group px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-110 flex items-center gap-2">
                        <i class="fas fa-user transition-transform duration-300"></i>
                        Visitor Access
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium mb-1">Total Users</p>
                        <p class="text-3xl font-bold"><?= count($users) ?></p>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium mb-1">Total Tasks</p>
                        <p class="text-3xl font-bold"><?= count($tasks) ?></p>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-tasks text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium mb-1">Completed</p>
                        <p class="text-3xl font-bold"><?= count(array_filter($tasks, fn($t) => $t['is_done'])) ?></p>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-700/50  mb-6 hover:shadow-2xl transition-all duration-300">
            <div class="bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-700 px-8 py-6">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    Users Management
                </h2>
            </div>
            <div>
                <table class="w-full text-left">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b-2 border-purple-200 dark:border-purple-900">
                        <tr>
                            <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-envelope mr-2 text-purple-500"></i>Email
                            </th>
                            <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-user mr-2 text-purple-500"></i>Name
                            </th>
                            <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-user-tag mr-2 text-purple-500"></i>Role
                            </th>
                            <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                <i class="fas fa-cog mr-2 text-purple-500"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-purple-50 dark:hover:bg-gray-700/50 transition-all duration-200">
                            <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-user-circle text-purple-500 mr-2"></i>
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                                <?= htmlspecialchars($user['name']) ?>
                            </td>
                            <td class="py-4 px-6 text-sm">
                                <span class="px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-2 shadow-md 
                                    <?= $user['role'] === 'admin' ? 
                                        'bg-gradient-to-r from-purple-500 to-indigo-600 text-white' : 
                                        'bg-gradient-to-r from-gray-200 to-gray-300 text-gray-800 dark:from-gray-700 dark:to-gray-600 dark:text-gray-200' 
                                    ?>">
                                    
                                    <i class="fas <?= $user['role'] === 'admin' ? 'fa-crown' : 'fa-user' ?>"></i>

                                    <?php 
                                        if ($user['id'] == $ROOT_ADMIN_ID) {
                                            echo "Main Admin";   // root admin
                                        } else {
                                            echo ucfirst($user['role']); // normal admin/user
                                        }
                                    ?>
                                </span>

                                <?php if ($user['status'] === 'suspended'): ?>
                                    <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-lg">SUSPENDED</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-sm">
                                <div class="flex gap-3">
                                    <!-- ROLE MANAGEMENT -->
                                    <?php if ($user['role'] === 'user' && $user['id'] != $ROOT_ADMIN_ID): ?>
                                        <!-- Promote user to admin -->
                                        <a 
                                            href="?promote_user=<?= $user['id'] ?>" 
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-semibold rounded-lg transition-all duration-200 hover:-translate-y-0.5 shadow hover:shadow-md cursor-pointer">
                                            <i class="fas fa-arrow-up"></i> Promote
                                        </a>
                                    <?php elseif ($user['role'] === 'admin' && $user['id'] != $ROOT_ADMIN_ID && $user['id'] != $user_id): ?>
                                        <!-- Demote admin (but not root admin or current user) -->
                                        <a href="?demote_user=<?= $user['id'] ?>" 
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 font-semibold rounded-lg transition-all duration-200 hover:-translate-y-0.5 shadow hover:shadow-md">
                                            <i class="fas fa-arrow-down"></i> Demote
                                        </a>
                                    <?php endif; ?>

                                    <!-- STATUS MANAGEMENT (Suspend / Activate) -->
                                    <?php if ($user['id'] != $ROOT_ADMIN_ID && $user['id'] != $user_id): ?>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a href="?suspend_user=<?= $user['id'] ?>" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 font-semibold rounded-lg transition-all duration-200 hover:-translate-y-0.5 shadow hover:shadow-md">
                                                <i class="fas fa-ban"></i> Suspend
                                            </a>
                                        <?php else: ?>
                                            <a href="?activate_user=<?= $user['id'] ?>" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-700 dark:text-green-300 font-semibold rounded-lg transition-all duration-200 hover:-translate-y-0.5 shadow hover:shadow-md">
                                                <i class="fas fa-check-circle"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- DELETE USER -->
                                    <?php if ($user['id'] != $ROOT_ADMIN_ID && $user['id'] != $user_id): ?>
                                        <a href="?delete_user=<?= $user['id'] ?>" 
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-200 hover:bg-red-300 dark:bg-red-800/40 dark:hover:bg-red-800/60 text-red-800 dark:text-red-200 font-semibold rounded-lg transition-all duration-200 hover:-translate-y-0.5 shadow hover:shadow-md"
                                            onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <!-- Tasks Section -->
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 hover:shadow-2xl transition-all duration-300">
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-cyan-700 px-8 py-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                All Tasks
            </h2>
        </div>
        <div >
            <table class="w-full text-left">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b-2 border-blue-200 dark:border-blue-900">
                    <tr>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Title</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Owner</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Invited Users</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($tasks as $task): ?>
                    <tr class="hover:bg-blue-50 dark:hover:bg-gray-700/50 transition-all duration-200">
                        <td class="py-4 px-6 text-sm font-bold text-gray-900 dark:text-white"><?= $task['task_id'] ?></td>
                        <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300 font-medium">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-file-alt text-blue-500"></i>
                                <?= htmlspecialchars($task['title']) ?>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-user-circle text-blue-500"></i>
                                <?= htmlspecialchars($task['owner_email']) ?>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                            <?php
                            $invitedUsers = getInvitedUsers($task['invited_user_ids'], $pdo);
                            if ($invitedUsers):
                                foreach ($invitedUsers as $user):
                                    echo '<div class="flex items-center gap-2">
                                            <i class="fas fa-user-friends text-purple-500"></i>'
                                            . htmlspecialchars($user['name']) 
                                            . ' (' . htmlspecialchars($user['email']) . ')
                                        </div>';
                                endforeach;
                            else:
                                echo '<span class="text-gray-400">-</span>';
                            endif;
                            ?>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <?php 
                                    if (!empty($task['target_date'])) {
                                        echo '<i class="fas fa-clock text-blue-500"></i>';
                                        echo '<span>' . date('M d, Y', strtotime($task['target_date'])) 
                                            . ' <span class="text-gray-400">||</span> '
                                            . date('h:i A', strtotime($task['target_date'])) . '</span>';
                                    } else {
                                        echo '<i class="fas fa-minus-circle text-gray-400"></i><span>-</span>';
                                    }
                                ?>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-sm">
                            <span class="px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-2 shadow-md <?= $task['is_done'] ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' : 'bg-gradient-to-r from-yellow-400 to-orange-500 text-white' ?>">
                                <i class="fas <?= $task['is_done'] ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
                                <?= $task['is_done'] ? 'Completed' : 'Pending' ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-sm">
                            <div class="relative inline-block text-left">
                                <!-- Dropdown Button -->
                                <button 
                                    class="email-dropdown-btn px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 
                                            text-blue-700 dark:text-blue-300 font-semibold rounded-lg transition-all duration-200 
                                            shadow hover:shadow-md inline-flex items-center gap-2">
                                    <i class="fas fa-envelope"></i>
                                    Email User
                                    <i class="fas fa-chevron-down ml-1"></i>
                                </button>
                                <!-- Dropdown Menu -->
                                <div class="hidden dropdown-menu absolute right-0 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg 
                                            border border-gray-200 dark:border-gray-700 bottom-full mb-2 z-[9999]">
                                    <?php
                                        $allUsers = array_merge([['email'=>$task['owner_email'], 'name'=>'Owner']], $invitedUsers);
                                        foreach ($allUsers as $user):
                                    ?>
                                        <a href="email_form.php?to=<?= urlencode($user['email']) ?>&title=<?= rawurlencode($task['title']) ?>"
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 
                                                dark:hover:bg-gray-700 rounded-lg transition">
                                            <i class="fas fa-user mr-2"></i>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </td>
                
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>


    </div>
    
    <!-- Profile Circle - Fixed bottom right -->
    <div class="fixed bottom-12 right-12 z-50 group">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 via-purple-600 to-pink-600 dark:from-blue-600 dark:to-purple-700 rounded-full flex items-center justify-center text-white font-bold cursor-pointer transition-all duration-500 group-hover:w-52 group-hover:rounded-2xl shadow-2xl hover:shadow-3xl hover:-translate-y-1 border-4 border-white dark:border-gray-800">
            <span class="text-xl transition-all duration-500 group-hover:mr-2 group-hover:scale-90">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
            </span>
            <span class="hidden group-hover:inline opacity-0 group-hover:opacity-100 transition-all duration-500 delay-100 truncate max-w-32">
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
            </span>
        </div>
        
        <!-- Dropdown -->
        <div class="absolute right-0 bottom-full mb-4 w-56 rounded-2xl shadow-2xl bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl 
            opacity-0 invisible group-hover:opacity-100 group-hover:visible transform translate-y-2 
            group-hover:translate-y-0 transition-all duration-500  border-2 border-gray-200 dark:border-gray-700">

            <!-- Logout -->
            <a href="../logout.php" 
            class="flex items-center gap-3 px-5 py-4 text-red-600 dark:text-red-400 font-bold 
            hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200 hover:pl-6">
                <i class="fas fa-sign-out-alt text-lg"></i>
                Log out
            </a>

            <!-- Divider -->
            <div class="border-t-2 border-gray-200 dark:border-gray-700"></div>

            <!-- Theme Toggle -->
            <button onclick="toggleTheme()" 
                class="w-full flex items-center gap-3 px-5 py-4 text-gray-700 dark:text-gray-200 font-bold
                bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 hover:pl-6">
                
                <i class="fa-solid fa-moon hidden dark:inline text-lg"></i>
                <i class="fa-solid fa-sun inline dark:hidden text-lg"></i>

                <span>Toggle Theme</span>
            </button>
        </div>
    </div>
</div>


<script>
    document.addEventListener("click", function (event) {
        // Close all dropdowns when clicking anywhere
        document.querySelectorAll(".dropdown-menu").forEach(menu => {
            menu.classList.add("hidden");
        });

        // If clicking on a dropdown button, toggle its menu
        const dropdownBtn = event.target.closest("button");
        if (dropdownBtn && dropdownBtn.textContent.includes("Email User")) {
            const dropdown = dropdownBtn.nextElementSibling;
            if (dropdown && dropdown.classList.contains("dropdown-menu")) {
                dropdown.classList.toggle("hidden");
                event.stopPropagation(); // Prevent the document click from immediately closing it
            }
        }
    });

    // Alternative approach - more reliable:
    document.addEventListener("DOMContentLoaded", function() {
        // Add click event to all dropdown buttons
        document.querySelectorAll('.relative button').forEach(button => {
            if (button.textContent.includes('Email User')) {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = this.nextElementSibling;
                    dropdown.classList.toggle('hidden');
                });
            }
        });

        // Close dropdowns when clicking elsewhere
        document.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        });
    });
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>