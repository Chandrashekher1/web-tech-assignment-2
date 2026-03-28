<?php
/**
 * Admin Dashboard
 * University Result Management System
 * 
 * Shows overview stats and quick actions for the admin.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();

// Fetch stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE Role = 'student'")->fetchColumn();
$totalResults = $pdo->query("SELECT COUNT(*) FROM results")->fetchColumn();
$totalSemesters = $pdo->query("SELECT COUNT(*) FROM semesters")->fetchColumn();
$avgPercentage = $pdo->query("SELECT ROUND(AVG(Percentage), 2) FROM results")->fetchColumn() ?? 0;

// Recent uploads
$recentResults = $pdo->query("
    SELECT r.*, s.SemesterName 
    FROM results r 
    JOIN semesters s ON r.SemesterID = s.SemesterID 
    ORDER BY r.UploadedAt DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — University Result Management</title>
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
                        <span>Admin Panel</span>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="sidebar-nav-label">Main</div>
                <a href="dashboard.php" class="sidebar-link active">
                    <span class="link-icon">📊</span> Dashboard
                </a>
                <a href="upload.php" class="sidebar-link">
                    <span class="link-icon">📤</span> Upload Results
                </a>

                <div class="sidebar-nav-label">Management</div>
                <a href="manage_students.php" class="sidebar-link">
                    <span class="link-icon">👥</span> Students
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
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

        <!-- Main -->
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn">☰</button>
                    <h2 class="topbar-title">Dashboard</h2>
                </div>
                <div class="topbar-right">
                    <a href="upload.php" class="btn btn-primary btn-sm">
                        📤 Upload PDF
                    </a>
                </div>
            </header>

            <div class="page-content">
                <!-- Welcome -->
                <div class="welcome-banner animate-fade-in-up">
                    <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?> 👋</h1>
                    <p>Here's an overview of the result management system</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card indigo animate-fade-in-up delay-1">
                        <div class="stat-icon indigo">👥</div>
                        <div class="stat-info">
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value"><?php echo $totalStudents; ?></div>
                        </div>
                    </div>
                    <div class="stat-card emerald animate-fade-in-up delay-2">
                        <div class="stat-icon emerald">📄</div>
                        <div class="stat-info">
                            <div class="stat-label">Results Uploaded</div>
                            <div class="stat-value"><?php echo $totalResults; ?></div>
                        </div>
                    </div>
                    <div class="stat-card amber animate-fade-in-up delay-3">
                        <div class="stat-icon amber">📅</div>
                        <div class="stat-info">
                            <div class="stat-label">Semesters</div>
                            <div class="stat-value"><?php echo $totalSemesters; ?></div>
                        </div>
                    </div>
                    <div class="stat-card rose animate-fade-in-up delay-4">
                        <div class="stat-icon rose">📈</div>
                        <div class="stat-info">
                            <div class="stat-label">Avg. Percentage</div>
                            <div class="stat-value"><?php echo $avgPercentage; ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results Table -->
                <div class="card animate-fade-in-up delay-5">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Recent Results</h3>
                            <p class="card-subtitle">Last 10 uploaded results</p>
                        </div>
                        <a href="upload.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>

                    <?php if (empty($recentResults)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-text">No results uploaded yet</div>
                            <div class="empty-state-sub">Upload a PDF to get started</div>
                            <a href="upload.php" class="btn btn-primary">Upload First PDF</a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Semester</th>
                                        <th>Total</th>
                                        <th>Percentage</th>
                                        <th>Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentResults as $r): ?>
                                        <?php
                                            $badgeClass = 'badge-pass';
                                            if ($r['Class'] === 'Distinction') $badgeClass = 'badge-distinction';
                                            elseif ($r['Class'] === 'First Class') $badgeClass = 'badge-first';
                                            elseif ($r['Class'] === 'Second Class') $badgeClass = 'badge-second';
                                            elseif ($r['Class'] === 'Fail') $badgeClass = 'badge-fail';
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($r['StudentID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($r['Name']); ?></td>
                                            <td><?php echo htmlspecialchars($r['SemesterName']); ?></td>
                                            <td><?php echo $r['TotalMarks']; ?></td>
                                            <td><?php echo $r['Percentage']; ?>%</td>
                                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($r['Class']); ?></span></td>
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
