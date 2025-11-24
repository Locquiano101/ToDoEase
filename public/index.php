<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$stmt = $pdo->prepare("
    SELECT t.* 
    FROM tasks t
    LEFT JOIN task_invites ti ON t.id = ti.task_id
    WHERE t.user_id = :user_id OR ti.invited_user_id = :user_id
    GROUP BY t.id
    ORDER BY 
        is_done ASC, 
        CASE WHEN target_date IS NULL THEN 1 ELSE 0 END,
        target_date ASC,
        created_at DESC
");

$stmt->execute(['user_id' => $user_id]);
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is admin
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Group todos
$overdue = [];
$today = [];
$upcoming = [];
$no_date = [];
$completed = [];

$today_date = date('Y-m-d');

foreach ($todos as $todo) {
    if ($todo['is_done']) {
        $completed[] = $todo;
    } elseif (empty($todo['target_date'])) {
        $no_date[] = $todo;
    } else {
        $target = $todo['target_date'];
        if ($target < $today_date) {
            $overdue[] = $todo;
        } elseif ($target === $today_date) {
            $today[] = $todo;
        } else {
            $upcoming[] = $todo;
        }
    }
}

function renderTodoItem($todo, $show_overdue_badge = false)
{
    $is_done = $todo['is_done'];

    // Show date + time
    $formatted_date = !empty($todo['target_date']) 
        ? date('M j, Y • g:i A', strtotime($todo['target_date'])) 
        : null;

    // Show updated time
    $updated_time = date('M j, g:i A', strtotime($todo['updated_at']));

    echo '<li class="py-4 hover:bg-gray-50 dark:hover:bg-dark-elevated transition-colors rounded-lg px-3 -mx-3">';
    echo '<div class="flex items-start justify-between gap-3">';

    // Checkbox + Title
    echo '<div class="flex items-start gap-3 flex-1 min-w-0">';

    echo '<div class="flex-1 min-w-0">';
    echo '<div class="' . ($is_done ? 'line-through text-gray-400 dark:text-dark-text-muted' : 'text-gray-900 dark:text-dark-text-primary font-medium') . '">';
    echo htmlspecialchars($todo['title']);
    if ($show_overdue_badge) {
        echo ' <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 ml-2">Overdue</span>';
    }
    echo '</div>';

    // Dates info
    echo '<div class="flex flex-wrap gap-3 mt-1 text-xs text-gray-500 dark:text-dark-text-secondary w-fit border-2 border-gray-200 dark:border-dark-border rounded-md px-2 py-1">';

    if ($formatted_date) {
        echo '<span class="flex items-center gap-1">';
        echo '<i class="far fa-calendar"></i>';
        echo $formatted_date;
        echo '</span>';
    }
    echo '<div>||</div>';
    
    echo '<span class="flex items-center gap-1">';
    echo '<i class="far fa-clock"></i>';
    echo 'Updated ' . $updated_time;
    echo '</span>';

    echo '</div>'; // end dates info

    echo '</div>';
    echo '</div>';

    // Action buttons
    echo '<div class="flex gap-2 flex-shrink-0">';
    echo '<a href="edit.php?id=' . $todo['id'] . '" 
          class="flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 dark:text-dark-text-muted hover:text-blue-600 dark:hover:text-dark-accent-blue hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors" 
          title="Edit">';
    echo '<i class="fas fa-edit"></i>';
    echo '<span>Edit</span>';
    echo '</a>';

    echo '<a href="delete.php?id=' . $todo['id'] . '" 
          class="flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 dark:text-dark-text-muted hover:text-red-600 dark:hover:text-dark-accent-red hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors" 
          onclick="return confirm(\'Delete this task?\')" 
          title="Delete">';
    echo '<i class="fas fa-trash"></i>';
    echo '<span>Delete</span>';
    echo '</a>';

    echo '</div>'; // end actions

    echo '</div>';
    echo '</li>';
}


function renderSection($title, $todos, $color, $show_overdue = false)
{
    if (empty($todos)) {
        return;
    }

    $colors = [
        'red' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800',
        'blue' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'green' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
        'gray' => 'bg-gray-50 dark:bg-gray-700/30 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
        'purple' => 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800',
    ];

    echo '<div class="">';
    echo '<div class="flex items-center justify-between mb-3">';
    echo '<h3 class="text-sm font-semibold uppercase tracking-wider ' . $colors[$color] . ' px-3 py-1 rounded-full border">';
    echo $title . ' <span class="ml-1 opacity-75">(' . count($todos) . ')</span>';
    echo '</h3>';
    echo '</div>';
    echo '<ul class="space-y-1">';
    foreach ($todos as $todo) {
        renderTodoItem($todo, $show_overdue);
    }
    echo '</ul>';
    echo '</div>';
}

$total_pending = count($overdue) + count($today) + count($upcoming) + count($no_date);
?>

<!-- Profile Circle - Fixed bottom right -->
<div class="fixed bottom-12 right-12 z-50 group">
    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 dark:from-dark-accent-blue dark:to-dark-accent-purple rounded-full flex items-center justify-center text-white font-semibold cursor-pointer transition-all duration-500 group-hover:w-52 group-hover:rounded shadow-lg hover:shadow-xl hover:-translate-y-1">
        <span class="text-xl transition-all duration-500 group-hover:mr-2 group-hover:scale-90">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
        </span>
        <span class="hidden group-hover:inline opacity-0 group-hover:opacity-100 transition-all duration-500 delay-100 truncate max-w-32">
            <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
        </span>
    </div>
    
    <!-- Dropdown -->
    <div class="absolute right-0 bottom-full w-52 rounded-lg shadow-2xl bg-white/90 dark:bg-dark-surface/90 backdrop-blur-lg 
        opacity-0 invisible group-hover:opacity-100 group-hover:visible transform translate-y-2 
        group-hover:translate-y-0 transition-all duration-500 overflow-hidden border border-gray-100 dark:border-dark-border">

        <!-- Logout -->
        <a href="logout.php" 
          class="flex items-center gap-3 px-4 py-3 text-red-600 dark:text-red-400 font-semibold 
          hover:bg-red-50 dark:hover:bg-red-900/20 transition">
            <i class="fas fa-sign-out-alt"></i>
            Log out
        </a>

        <!-- Divider -->
        <div class="border-t border-gray-200 dark:border-dark-border"></div>

        <!-- Theme Toggle -->
        <button onclick="toggleTheme()" 
            class="w-full flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-dark-text-primary font-medium
            bg-transparent hover:bg-gray-100 dark:hover:bg-dark-elevated transition">
            
            <i class="fa-solid fa-moon hidden dark:inline"></i>
            <i class="fa-solid fa-sun inline dark:hidden"></i>

            <span>Toggle Theme</span>
        </button>
    </div>
</div>

<div class="w-full max-w-7xl mx-auto px-4 py-6">
  <!-- Updated Header Card Section with Admin Button -->
  <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-dark-accent-blue dark:to-dark-accent-purple rounded-lg shadow-lg dark:shadow-2xl p-6 text-white flex justify-between">
      <div>
        <h1 class="text-2xl font-bold mb-2">My Tasks</h1>
        <div class="flex gap-6 text-sm">
          <div>
            <span class="opacity-90">Pending:</span>
            <span class="font-semibold ml-1"><?= $total_pending ?></span>
          </div>
          <div>
            <span class="opacity-90">Completed:</span>
            <span class="font-semibold ml-1"><?= count($completed) ?></span>
          </div>
          <div>
            <span class="opacity-90">Total:</span>
            <span class="font-semibold ml-1"><?= count($todos) ?></span>
          </div>
        </div>
      </div>
      
      <!-- Action Buttons Container -->
      <div class="flex gap-3">
        <?php if ($is_admin): ?>
        <!-- Admin Dashboard Button -->
        <a href="../admin/admin.php" 
          class="inline-flex items-center gap-2 bg-purple-600 dark:bg-purple-700 border-2 border-white dark:border-dark-text-primary hover:bg-white dark:hover:bg-dark-elevated text-white hover:text-purple-600 dark:hover:text-purple-600 py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-500 font-medium">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Admin Panel
        </a>
        <?php endif; ?>
        
        <!-- Add Task Button -->
        <a href="create.php" 
          class="inline-flex items-center gap-2 bg-blue-600 dark:bg-dark-accent-blue border-2 border-white dark:border-dark-text-primary hover:bg-white dark:hover:bg-dark-elevated text-white hover:text-blue-900 dark:hover:text-dark-accent-blue py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-500 font-medium">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add New Task
        </a>
      </div>
  </div>

  <!-- Tasks List -->
  <?php if (empty($todos)): ?>
  <div class="bg-white dark:bg-dark-surface rounded-lg shadow-lg p-6 mt-6">
    <div class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-dark-text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 dark:text-dark-text-primary mb-2">No tasks yet</h3>
      <p class="text-gray-500 dark:text-dark-text-secondary mb-4">Get started by creating your first task</p>
      <a href="create.php" class="text-blue-500 dark:text-dark-accent-blue hover:text-blue-600 dark:hover:text-blue-400 font-medium">+ Create Task</a>
    </div>
  </div>
  <?php else: ?>

  <div class="flex flex-col gap-4 w-full mt-6">
    <!-- Overdue -->
    <?php if (!empty($overdue)): ?>
    <div class="bg-white dark:bg-dark-surface rounded-lg shadow-lg border border-transparent dark:border-dark-border p-6">
      <h2 class="text-xl font-bold text-red-600 dark:text-dark-accent-red flex items-center gap-2 mb-4">
        🔥 Overdue
      </h2>
      <?php renderSection('', $overdue, 'red', true); ?>
    </div>
    <?php endif; ?>

    <!-- Today -->
    <?php if (!empty($today)): ?>
    <div class="bg-white dark:bg-dark-surface rounded-lg shadow-lg border border-transparent dark:border-dark-border p-6">
      <h2 class="text-xl font-bold text-blue-600 dark:text-dark-accent-blue mb-4 flex items-center gap-2">
        📌 Today
      </h2>
      <?php renderSection('', $today, 'blue'); ?>
    </div>
    <?php endif; ?>

    <!-- Upcoming -->
    <?php if (!empty($upcoming)): ?>
    <div class="bg-white dark:bg-dark-surface rounded-lg shadow-lg border border-transparent dark:border-dark-border p-6">
      <h2 class="text-xl font-bold text-green-600 dark:text-dark-accent-green mb-4 flex items-center gap-2">
        📅 Upcoming
      </h2>
      <?php renderSection('', $upcoming, 'green'); ?>
    </div>
    <?php endif; ?>

    <!-- No Due Date -->
    <?php if (!empty($no_date)): ?>
    <div class="bg-white dark:bg-dark-surface rounded-lg shadow-lg border border-transparent dark:border-dark-border p-6">
      <h2 class="text-xl font-bold text-gray-600 dark:text-dark-text-secondary mb-4 flex items-center gap-2">
        📝 No Due Date
      </h2>
      <?php renderSection('', $no_date, 'gray'); ?>
    </div>
    <?php endif; ?>

    <!-- Completed -->
    <?php if (!empty($completed)): ?>
    <div class="bg-white dark:bg-dark-surface rounded-lg shadow-lg border border-transparent dark:border-dark-border p-6">
      <h2 class="text-xl font-bold text-purple-600 dark:text-dark-accent-purple mb-4 flex items-center gap-2">
        ✓ Completed Tasks
      </h2>
      <?php renderSection('', $completed, 'purple'); ?>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<script>
  const todos = <?= json_encode($todos) ?>;
  console.log("Fetched todos:", todos);
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>