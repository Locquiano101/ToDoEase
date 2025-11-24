<?php 
include_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$id = $_GET["id"] ?? null;
if (!$id) {
    die("No ID provided");
}

// Fetch current task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id=?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    die("Task not found");
}

// Fetch invited users for this task
$stmtInv = $pdo->prepare("SELECT invited_user_id FROM task_invites WHERE task_id=?");
$stmtInv->execute([$id]);
$currentInvites = $stmtInv->fetchAll(PDO::FETCH_COLUMN);

// Handle update form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $target_date = $_POST["target_date"];
    $invite_users = $_POST["invite_users"] ?? [];
    $is_done = isset($_POST['is_done']) ? 1 : 0;

    if (empty($title) || empty($target_date)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: edit.php?id=".$id);
        exit;
    }

    $target_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $target_date);
    $now = new DateTime();
    if ($target_datetime <= $now) {
        $_SESSION['error'] = "Target date must be in the future.";
        header("Location: edit.php?id=".$id);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update main task including is_done
        $stmt = $pdo->prepare("UPDATE tasks SET title=?, target_date=?, is_done=? WHERE id=?");
        $stmt->execute([$title, $target_date, $is_done, $id]);

        // Remove old invites & notifications
        $pdo->prepare("DELETE FROM task_invites WHERE task_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM notifications WHERE task_id=?")->execute([$id]);

        // Insert new invites & notifications
        $stmtInvite = $pdo->prepare("INSERT INTO task_invites (task_id, invited_user_id) VALUES (?, ?)");
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, task_id, message, trigger_at) VALUES (?, ?, ?, ?)");

        foreach ($invite_users as $invited_uid) {
            $stmtInvite->execute([$id, $invited_uid]);
            $stmtNotif->execute([$invited_uid, $id, "Task updated: $title", $target_date]);
        }

        $pdo->commit();

        $_SESSION['success'] = "Task updated!";
        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

?>

<?php include("../includes/header.php"); ?>
<div class="w-screen h-screen flex justify-center items-center">
  <div class="w-full max-w-2xl mx-auto">
    <div class="bg-white dark:bg-dark-surface rounded-2xl shadow-2xl border border-transparent dark:border-dark-border overflow-hidden">
      
      <!-- Header -->
      <div class="pt-8 flex justify-center text-center">
        <h2 class="text-2xl md:text-3xl font-bold flex items-center gap-3">
          <i class="fas fa-edit"></i>
          Edit Task
        </h2>
      </div>

      <!-- Form -->
      <div class="p-4">
        <form method="POST" class="space-y-6">
          <!-- Task title -->
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-dark-text-primary">Task Title</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-tasks text-gray-400 dark:text-dark-text-muted"></i>
              </div>
              <input 
                type="text" 
                name="title"
                value="<?= htmlspecialchars($task['title']) ?>"
                required 
                class="w-full border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary rounded-lg p-3 pl-10 focus:ring-2 focus:ring-yellow-500 dark:focus:ring-dark-accent-yellow focus:border-yellow-500 dark:focus:border-dark-accent-yellow transition"
              >
            </div>
          </div>

          <!-- Target date -->
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-dark-text-primary">Target Date</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-calendar-day text-gray-400 dark:text-dark-text-muted"></i>
              </div>
              <input 
                type="datetime-local" 
                name="target_date"
                value="<?= htmlspecialchars($task['target_date']) ?>"
                required
                class="w-full border border-gray-300 dark:border-dark-border bg-white dark:bg-dark-elevated text-gray-900 dark:text-dark-text-primary rounded-lg p-3 pl-10 focus:ring-2 focus:ring-yellow-500 dark:focus:ring-dark-accent-yellow focus:border-yellow-500 dark:focus:border-dark-accent-yellow transition"
              >
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
                        <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-dark-border rounded-lg">
                            <?php
                            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id != ? ORDER BY name ASC");
                            $stmt->execute([$_SESSION['user_id']]);

                            if ($stmt->rowCount() === 0) {
                                ?>
                                <div class="px-4 flex flex-col items-center py-8 text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <p class="text-sm">No other users available</p>
                                </div>
                                <?php
                            } else {
                                while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $userId = htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8');
                                    $userName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
                                    $userEmail = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
                                    $isChecked = in_array($userId, $currentInvites ?? []) ? 'checked' : '';
                                    ?>
                                    <label class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-dark-bg cursor-pointer transition border-b border-gray-100 dark:border-dark-border last:border-b-0">
                                        <input type="checkbox" name="invite_users[]" value="<?= $userId ?>" <?= $isChecked ?>
                                              class="user-checkbox w-4 h-4 text-blue-600 bg-white dark:bg-dark-elevated border-gray-300 dark:border-dark-border rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-dark-accent-blue cursor-pointer">
                                        <div class="ml-3 flex-1 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                                                <?= strtoupper(substr($userName, 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-dark-text-primary hover:text-blue-600 dark:hover:text-dark-accent-blue">
                                                    <?= $userName ?>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= $userEmail ?></p>
                                            </div>
                                        </div>
                                    </label>
                                    <?php
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
                

          <div class="bg-gray-50 dark:bg-dark-elevated rounded-lg p-4 border border-gray-200 dark:border-dark-border">
            <label class="flex items-center space-x-3 cursor-pointer group">
              
              <!-- Hidden checkbox -->
              <input type="checkbox" name="is_done" <?= $task['is_done'] ? 'checked' : '' ?> class="hidden peer">

              <!-- Custom check-circle -->
              <i class="fas fa-check-circle text-2xl text-gray-300 dark:text-dark-border transition-colors peer-checked:text-green-500 dark:peer-checked:text-dark-accent-green"></i>

              <div class="flex-1">
                <span class="text-sm font-medium text-gray-700 dark:text-dark-text-primary group-hover:text-green-600 dark:group-hover:text-dark-accent-green transition">
                  Mark as Completed
                </span>
                <p class="text-xs text-gray-500 dark:text-dark-text-muted mt-0.5">
                  Check this box if you've finished this task
                </p>
              </div>
            </label>
          </div>

          <!-- Buttons -->
          <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-dark-border">
            <a href="index.php" class="flex items-center gap-2 text-gray-600 dark:text-dark-text-secondary hover:text-gray-800 dark:hover:text-dark-text-primary transition font-medium">
              <i class="fas fa-arrow-left"></i>
              Cancel
            </a>
            <button 
              type="submit" 
              class="bg-gradient-to-r from-yellow-500 to-orange-500 dark:from-dark-accent-yellow dark:to-orange-600 hover:from-yellow-600 hover:to-orange-600 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2">
              <i class="fas fa-save"></i>
              Update Task
            </button>
          </div>
        </form>
    </div>
  </div>
</div>
