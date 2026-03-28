<?php
/**
 * Student Dashboard
 * University Result Management System
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
requireStudentAuth();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

// Get all semesters with results for this student
$semStmt = $pdo->prepare("
    SELECT DISTINCT s.SemesterID, s.SemesterName 
    FROM results r 
    JOIN semesters s ON r.SemesterID = s.SemesterID 
    WHERE r.StudentID = ?
    ORDER BY s.SemesterID
");
$semStmt->execute([$studentId]);
$studentSemesters = $semStmt->fetchAll();

// Get latest result
$latestStmt = $pdo->prepare("
    SELECT r.*, s.SemesterName 
    FROM results r 
    JOIN semesters s ON r.SemesterID = s.SemesterID 
    WHERE r.StudentID = ?
    ORDER BY r.SemesterID DESC 
    LIMIT 1
");
$latestStmt->execute([$studentId]);
$latestResult = $latestStmt->fetch();

// Get all results count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM results WHERE StudentID = ?");
$countStmt->execute([$studentId]);
$totalResults = $countStmt->fetchColumn();

// Get best percentage
$bestStmt = $pdo->prepare("SELECT MAX(Percentage) FROM results WHERE StudentID = ?");
$bestStmt->execute([$studentId]);
$bestPercentage = $bestStmt->fetchColumn() ?? 0;

// Get average percentage
$avgStmt = $pdo->prepare("SELECT ROUND(AVG(Percentage), 2) FROM results WHERE StudentID = ?");
$avgStmt->execute([$studentId]);
$avgPercentage = $avgStmt->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — University Result Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar-overlay"></div>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="sidebar-brand-icon">🎓</div>
                    <div class="sidebar-brand-text">
                        UniResults
                        <span>Student Portal</span>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="sidebar-nav-label">Menu</div>
                <a href="dashboard.php" class="sidebar-link active">
                    <span class="link-icon">🏠</span> Dashboard
                </a>
                <a href="result.php" class="sidebar-link">
                    <span class="link-icon">📊</span> My Results
                </a>
                <a href="../exports/class_results.php" class="sidebar-link">
                    <span class="link-icon">📥</span> Class Results
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar"><?php echo getUserInitials(); ?></div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                        <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['student_id']); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="sidebar-link" style="margin-top: var(--space-2);">
                    <span class="link-icon">🚪</span> Sign Out
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn">☰</button>
                    <h2 class="topbar-title">Dashboard</h2>
                </div>
                <div class="topbar-right">
                    <a href="result.php" class="btn btn-primary btn-sm">📊 View Results</a>
                </div>
            </header>

            <div class="page-content">
                <!-- Welcome Banner -->
                <div class="welcome-banner animate-fade-in-up">
                    <h1>Hello, <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?> 👋</h1>
                    <p>Welcome to your academic dashboard. View your semester results and download reports.</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card indigo animate-fade-in-up delay-1">
                        <div class="stat-icon indigo">📅</div>
                        <div class="stat-info">
                            <div class="stat-label">Semesters</div>
                            <div class="stat-value"><?php echo count($studentSemesters); ?></div>
                        </div>
                    </div>
                    <div class="stat-card emerald animate-fade-in-up delay-2">
                        <div class="stat-icon emerald">📈</div>
                        <div class="stat-info">
                            <div class="stat-label">Best Score</div>
                            <div class="stat-value"><?php echo $bestPercentage; ?>%</div>
                        </div>
                    </div>
                    <div class="stat-card amber animate-fade-in-up delay-3">
                        <div class="stat-icon amber">📊</div>
                        <div class="stat-info">
                            <div class="stat-label">Average</div>
                            <div class="stat-value"><?php echo $avgPercentage; ?>%</div>
                        </div>
                    </div>
                    <div class="stat-card rose animate-fade-in-up delay-4">
                        <div class="stat-icon rose">📄</div>
                        <div class="stat-info">
                            <div class="stat-label">Total Results</div>
                            <div class="stat-value"><?php echo $totalResults; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Latest Result -->
                <?php if ($latestResult): ?>
                    <div class="card animate-fade-in-up delay-5">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Latest Result</h3>
                                <p class="card-subtitle"><?php echo htmlspecialchars($latestResult['SemesterName']); ?></p>
                            </div>
                            <a href="result.php?semester=<?php echo $latestResult['SemesterID']; ?>" class="btn btn-secondary btn-sm">View Details →</a>
                        </div>

                        <?php $subjects = json_decode($latestResult['Subjects'], true); ?>
                        <div class="result-meta">
                            <div class="result-meta-item">
                                <div class="meta-label">Total</div>
                                <div class="meta-value"><?php echo $latestResult['TotalMarks']; ?></div>
                            </div>
                            <div class="result-meta-item">
                                <div class="meta-label">Percentage</div>
                                <div class="meta-value"><?php echo $latestResult['Percentage']; ?>%</div>
                            </div>
                            <div class="result-meta-item">
                                <div class="meta-label">Grade</div>
                                <div class="meta-value"><?php echo htmlspecialchars($latestResult['Class']); ?></div>
                            </div>
                            <div class="result-meta-item">
                                <div class="meta-label">Subjects</div>
                                <div class="meta-value"><?php echo count($subjects); ?></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card animate-fade-in-up delay-5">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-text">No results available yet</div>
                            <div class="empty-state-sub">Your results will appear here once they are uploaded by the admin.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Access -->
                <?php if (!empty($studentSemesters)): ?>
                <div class="card animate-fade-in-up" style="margin-top: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title">Quick Access</h3>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-3);">
                        <?php foreach ($studentSemesters as $sem): ?>
                            <a href="result.php?semester=<?php echo $sem['SemesterID']; ?>" class="btn btn-secondary">
                                📅 <?php echo htmlspecialchars($sem['SemesterName']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
