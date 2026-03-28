<?php
/**
 * View Result Page
 * University Result Management System
 * 
 * Displays student results with semester filter,
 * subject-wise marks with visual bars, and download options.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
requireStudentAuth();

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];

// Get all semesters
$allSemesters = $pdo->query("SELECT * FROM semesters ORDER BY SemesterID")->fetchAll();

// Get semesters with results for this student
$semStmt = $pdo->prepare("
    SELECT DISTINCT s.SemesterID, s.SemesterName 
    FROM results r 
    JOIN semesters s ON r.SemesterID = s.SemesterID 
    WHERE r.StudentID = ?
    ORDER BY s.SemesterID
");
$semStmt->execute([$studentId]);
$studentSemesters = $semStmt->fetchAll();

// Get selected semester (default to latest)
$selectedSemester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
if ($selectedSemester === 0 && !empty($studentSemesters)) {
    $selectedSemester = $studentSemesters[count($studentSemesters) - 1]['SemesterID'];
}

// Fetch result for selected semester
$result = null;
$subjects = [];
$semesterName = '';

if ($selectedSemester > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, s.SemesterName 
        FROM results r 
        JOIN semesters s ON r.SemesterID = s.SemesterID 
        WHERE r.StudentID = ? AND r.SemesterID = ?
    ");
    $stmt->execute([$studentId, $selectedSemester]);
    $result = $stmt->fetch();

    if ($result) {
        $subjects = json_decode($result['Subjects'], true);
        $semesterName = $result['SemesterName'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results — University Result Management</title>
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
                <a href="dashboard.php" class="sidebar-link">
                    <span class="link-icon">🏠</span> Dashboard
                </a>
                <a href="result.php" class="sidebar-link active">
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
                    <h2 class="topbar-title">My Results</h2>
                </div>
            </header>

            <div class="page-content">
                <!-- Semester Tabs -->
                <?php if (!empty($studentSemesters)): ?>
                    <div class="semester-tabs animate-fade-in-up">
                        <?php foreach ($studentSemesters as $sem): ?>
                            <a href="?semester=<?php echo $sem['SemesterID']; ?>" 
                               class="semester-tab <?php echo $sem['SemesterID'] == $selectedSemester ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($sem['SemesterName']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($result): ?>
                    <!-- Result Card -->
                    <div class="result-card animate-fade-in-up delay-1">
                        <div class="result-card-header">
                            <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                            <p>Student ID: <?php echo htmlspecialchars($studentId); ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($semesterName); ?></p>
                        </div>

                        <div class="result-card-body">
                            <!-- Summary Stats -->
                            <div class="result-meta">
                                <div class="result-meta-item">
                                    <div class="meta-label">Total Marks</div>
                                    <div class="meta-value"><?php echo $result['TotalMarks']; ?></div>
                                </div>
                                <div class="result-meta-item">
                                    <div class="meta-label">Percentage</div>
                                    <div class="meta-value" style="color: <?php 
                                        echo $result['Percentage'] >= 85 ? 'var(--primary-400)' : 
                                            ($result['Percentage'] >= 70 ? 'var(--accent-400)' : 
                                            ($result['Percentage'] >= 55 ? 'var(--warning-500)' : 'var(--error-400)')); 
                                    ?>"><?php echo $result['Percentage']; ?>%</div>
                                </div>
                                <div class="result-meta-item">
                                    <div class="meta-label">Class</div>
                                    <?php
                                        $badgeClass = 'badge-pass';
                                        if ($result['Class'] === 'Distinction') $badgeClass = 'badge-distinction';
                                        elseif ($result['Class'] === 'First Class') $badgeClass = 'badge-first';
                                        elseif ($result['Class'] === 'Second Class') $badgeClass = 'badge-second';
                                        elseif ($result['Class'] === 'Fail') $badgeClass = 'badge-fail';
                                    ?>
                                    <div class="meta-value"><span class="badge <?php echo $badgeClass; ?>" style="font-size: var(--font-base);"><?php echo htmlspecialchars($result['Class']); ?></span></div>
                                </div>
                                <div class="result-meta-item">
                                    <div class="meta-label">Subjects</div>
                                    <div class="meta-value"><?php echo count($subjects); ?></div>
                                </div>
                            </div>

                            <!-- Subject-wise Marks Table -->
                            <div class="table-container" style="margin-top: var(--space-4);">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Subject</th>
                                            <th style="width: 120px;">Marks</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; foreach ($subjects as $subject => $marks): ?>
                                            <?php
                                                $fillClass = 'high';
                                                if ($marks < 40) $fillClass = 'fail';
                                                elseif ($marks < 60) $fillClass = 'low';
                                                elseif ($marks < 80) $fillClass = 'medium';
                                            ?>
                                            <tr>
                                                <td style="color: var(--text-muted);"><?php echo $i++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($subject); ?></strong></td>
                                                <td>
                                                    <span class="marks-value"><?php echo $marks; ?></span>
                                                </td>
                                                <td>
                                                    <div class="marks-bar-container">
                                                        <div class="marks-bar">
                                                            <div class="marks-fill <?php echo $fillClass; ?>" data-width="<?php echo $marks; ?>" style="width: 0%;"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="result-actions">
                            <a href="../exports/download_pdf.php?semester=<?php echo $selectedSemester; ?>" class="btn btn-primary">
                                📄 Download as PDF
                            </a>
                            <a href="../exports/class_results.php?semester=<?php echo $selectedSemester; ?>&download=1" class="btn btn-success">
                                📊 Download Class Results (Excel)
                            </a>
                        </div>
                    </div>

                <?php elseif (empty($studentSemesters)): ?>
                    <div class="card animate-fade-in-up">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-text">No results available</div>
                            <div class="empty-state-sub">Your results will appear here once they are uploaded by the admin.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card animate-fade-in-up">
                        <div class="empty-state">
                            <div class="empty-state-icon">🔍</div>
                            <div class="empty-state-text">No result for this semester</div>
                            <div class="empty-state-sub">Please select a different semester from the tabs above.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
