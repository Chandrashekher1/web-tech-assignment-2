<?php
/**
 * Admin — Manage Students
 * University Result Management System
 * 
 * View and manage student accounts.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$messageType = '';

// Handle student deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $deleteId = trim($_POST['delete_student']);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM results WHERE StudentID = ?")->execute([$deleteId]);
        $pdo->prepare("DELETE FROM users WHERE StudentID = ? AND Role = 'student'")->execute([$deleteId]);
        $pdo->commit();
        $message = "Student {$deleteId} and their results have been removed.";
        $messageType = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error deleting student: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $resetId = trim($_POST['reset_password']);
    try {
        $newPassword = password_hash($resetId, PASSWORD_DEFAULT); // Reset to StudentID
        $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE StudentID = ?");
        $stmt->execute([$newPassword, $resetId]);
        $message = "Password for {$resetId} has been reset to their Student ID.";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error resetting password: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch all students
$students = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM results r WHERE r.StudentID = u.StudentID) as ResultCount
    FROM users u 
    WHERE u.Role = 'student' 
    ORDER BY u.StudentID
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar-overlay"></div>

    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="sidebar-brand-icon">🎓</div>
                    <div class="sidebar-brand-text">
                        UniResults
                        <span>Admin Panel</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="sidebar-nav-label">Main</div>
                <a href="dashboard.php" class="sidebar-link">
                    <span class="link-icon">📊</span> Dashboard
                </a>
                <a href="upload.php" class="sidebar-link">
                    <span class="link-icon">📤</span> Upload Results
                </a>
                <div class="sidebar-nav-label">Management</div>
                <a href="manage_students.php" class="sidebar-link active">
                    <span class="link-icon">👥</span> Students
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                        <div class="sidebar-user-role">Administrator</div>
                    </div>
                </div>
                <a href="../student/logout.php" class="sidebar-link" style="margin-top: var(--space-2);">
                    <span class="link-icon">🚪</span> Sign Out
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn">☰</button>
                    <h2 class="topbar-title">Manage Students</h2>
                </div>
                <div class="topbar-right">
                    <span style="font-size: var(--font-sm); color: var(--text-secondary);">
                        <?php echo count($students); ?> student(s)
                    </span>
                </div>
            </header>

            <div class="page-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <span><?php echo $messageType === 'success' ? '✅' : '❌'; ?></span>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <div class="card animate-fade-in-up">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">All Students</h3>
                            <p class="card-subtitle">View and manage student accounts</p>
                        </div>
                    </div>

                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">👥</div>
                            <div class="empty-state-text">No students registered</div>
                            <div class="empty-state-sub">Students will appear here when results are uploaded or accounts are created.</div>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Class</th>
                                        <th>Results</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($s['StudentID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($s['Name']); ?></td>
                                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($s['Email'] ?? '—'); ?></td>
                                            <td><span class="badge badge-first"><?php echo htmlspecialchars($s['Class'] ?? '—'); ?></span></td>
                                            <td><?php echo $s['ResultCount']; ?></td>
                                            <td style="color: var(--text-muted); font-size: var(--font-xs);"><?php echo date('M j, Y', strtotime($s['CreatedAt'])); ?></td>
                                            <td>
                                                <div style="display: flex; gap: var(--space-2);">
                                                    <form method="POST" style="display: inline;" onsubmit="return confirmAction('Reset password for <?php echo htmlspecialchars($s['StudentID']); ?> to their Student ID?')">
                                                        <input type="hidden" name="reset_password" value="<?php echo htmlspecialchars($s['StudentID']); ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm" title="Reset Password">🔑</button>
                                                    </form>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirmAction('Delete student <?php echo htmlspecialchars($s['StudentID']); ?> and all their results? This cannot be undone.')">
                                                        <input type="hidden" name="delete_student" value="<?php echo htmlspecialchars($s['StudentID']); ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm" title="Delete" style="color: var(--error-400);">🗑️</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
