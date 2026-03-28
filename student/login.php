<?php
/**
 * Student Login Page
 * University Result Management System
 */
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';

// If already logged in as student, redirect
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($studentId) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (StudentID = ? OR Email = ?) AND Role = 'student'");
            $stmt->execute([$studentId, $studentId]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['Password'])) {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['student_id'] = $user['StudentID'];
                $_SESSION['name'] = $user['Name'];
                $_SESSION['role'] = $user['Role'];
                $_SESSION['class'] = $user['Class'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid Student ID/Email or Password';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — University Result Management</title>
    <meta name="description" content="Student login portal for University Result Management System">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-logo">
                    <div class="auth-logo-icon">🎓</div>
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to view your results</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="form-group">
                        <label class="form-label" for="student_id">Student ID or Email</label>
                        <div class="form-input-icon">
                            <span class="icon">👤</span>
                            <input type="text" 
                                   id="student_id" 
                                   name="student_id" 
                                   class="form-input" 
                                   placeholder="Enter Student ID or Email"
                                   value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="form-input-icon">
                            <span class="icon">🔒</span>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-input" 
                                   placeholder="Enter your password"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-full">
                        <span class="btn-text">Sign In</span>
                        <span class="spinner"></span>
                    </button>
                </form>

                <div style="text-align: center; margin-top: var(--space-6); padding-top: var(--space-4); border-top: 1px solid var(--surface-border);">
                    <a href="../admin/index.php" style="font-size: var(--font-sm); color: var(--text-muted);">
                        Admin Portal →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
