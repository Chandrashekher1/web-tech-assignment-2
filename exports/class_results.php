<?php
/**
 * Class Results Export (Excel)
 * University Result Management System
 * 
 * Generates an Excel file of all results for the student's class
 * using PhpSpreadsheet. Students can only access their own class results.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../student/login.php');
    exit;
}

$pdo = getDBConnection();
$studentId = $_SESSION['student_id'];
$studentClass = $_SESSION['class'] ?? '';

// Get the student's class if not in session
if (empty($studentClass)) {
    $stmt = $pdo->prepare("SELECT Class FROM users WHERE StudentID = ?");
    $stmt->execute([$studentId]);
    $user = $stmt->fetch();
    $studentClass = $user['Class'] ?? '';
}

// Get semesters
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY SemesterID")->fetchAll();

$selectedSemester = intval($_GET['semester'] ?? 0);
$download = isset($_GET['download']);

// Fetch class results
$classResults = [];
if ($selectedSemester > 0) {
    // Get all results for students in the same class, for the selected semester
    $stmt = $pdo->prepare("
        SELECT r.*, s.SemesterName, u.Class as StudentClass
        FROM results r 
        JOIN semesters s ON r.SemesterID = s.SemesterID 
        LEFT JOIN users u ON r.StudentID = u.StudentID
        WHERE r.SemesterID = ? AND (u.Class = ? OR u.Class IS NULL)
        ORDER BY r.Percentage DESC
    ");
    $stmt->execute([$selectedSemester, $studentClass]);
    $classResults = $stmt->fetchAll();
}

// Handle Excel download
if ($download && !empty($classResults)) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Class Results');
            
            // Header styling
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            ];
            
            // Title row
            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'Class Results — ' . ($classResults[0]['SemesterName'] ?? '') . ' — Class: ' . $studentClass);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Get all unique subjects
            $allSubjects = [];
            foreach ($classResults as $r) {
                $subs = json_decode($r['Subjects'], true);
                foreach (array_keys($subs) as $sub) {
                    if (!in_array($sub, $allSubjects)) {
                        $allSubjects[] = $sub;
                    }
                }
            }
            
            // Column headers
            $row = 3;
            $col = 'A';
            $headers = ['Rank', 'Student ID', 'Name'];
            $headers = array_merge($headers, $allSubjects);
            $headers = array_merge($headers, ['Total', 'Percentage', 'Class']);
            
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }
            $lastCol = chr(ord('A') + count($headers) - 1);
            if (count($headers) > 26) {
                // Handle columns beyond Z
                $lastCol = 'A' . chr(ord('A') + count($headers) - 27);
            }
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($headerStyle);
            
            // Data rows
            $rank = 1;
            foreach ($classResults as $r) {
                $row++;
                $col = 'A';
                $subs = json_decode($r['Subjects'], true);
                
                $sheet->setCellValue($col++ . $row, $rank++);
                $sheet->setCellValue($col++ . $row, $r['StudentID']);
                $sheet->setCellValue($col++ . $row, $r['Name']);
                
                foreach ($allSubjects as $sub) {
                    $sheet->setCellValue($col++ . $row, $subs[$sub] ?? '-');
                }
                
                $sheet->setCellValue($col++ . $row, $r['TotalMarks']);
                $sheet->setCellValue($col++ . $row, $r['Percentage'] . '%');
                $sheet->setCellValue($col++ . $row, $r['Class']);
                
                // Alternate row colors
                if ($rank % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F0F2FF');
                }
            }
            
            // Border for all data
            $sheet->getStyle('A3:' . $lastCol . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Output
            $semName = str_replace(' ', '_', $classResults[0]['SemesterName'] ?? 'Results');
            $fileName = 'Class_Results_' . $studentClass . '_' . $semName . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
        }
    }
    
    // Fallback: CSV download
    $semName = !empty($classResults) ? str_replace(' ', '_', $classResults[0]['SemesterName']) : 'Results';
    $fileName = 'Class_Results_' . $studentClass . '_' . $semName . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    
    $output = fopen('php://output', 'w');
    
    // Get subjects from first result
    $allSubjects = [];
    foreach ($classResults as $r) {
        $subs = json_decode($r['Subjects'], true);
        foreach (array_keys($subs) as $sub) {
            if (!in_array($sub, $allSubjects)) $allSubjects[] = $sub;
        }
    }
    
    // Header
    $header = array_merge(['Rank', 'Student ID', 'Name'], $allSubjects, ['Total', 'Percentage', 'Class']);
    fputcsv($output, $header);
    
    // Data
    $rank = 1;
    foreach ($classResults as $r) {
        $subs = json_decode($r['Subjects'], true);
        $row = [$rank++, $r['StudentID'], $r['Name']];
        foreach ($allSubjects as $sub) {
            $row[] = $subs[$sub] ?? '-';
        }
        $row[] = $r['TotalMarks'];
        $row[] = $r['Percentage'] . '%';
        $row[] = $r['Class'];
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Get semester name for display
$semesterName = '';
if ($selectedSemester > 0) {
    foreach ($semesters as $s) {
        if ($s['SemesterID'] == $selectedSemester) {
            $semesterName = $s['SemesterName'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Results — University Result Management</title>
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
                <a href="../student/dashboard.php" class="sidebar-link">
                    <span class="link-icon">🏠</span> Dashboard
                </a>
                <a href="../student/result.php" class="sidebar-link">
                    <span class="link-icon">📊</span> My Results
                </a>
                <a href="class_results.php" class="sidebar-link active">
                    <span class="link-icon">📥</span> Class Results
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        <?php
                            $parts = explode(' ', $_SESSION['name']);
                            echo strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                        ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                        <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['student_id']); ?></div>
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
                    <h2 class="topbar-title">Class Results</h2>
                </div>
                <?php if ($selectedSemester > 0 && !empty($classResults)): ?>
                <div class="topbar-right">
                    <a href="?semester=<?php echo $selectedSemester; ?>&download=1" class="btn btn-success btn-sm">
                        📥 Download Excel
                    </a>
                </div>
                <?php endif; ?>
            </header>

            <div class="page-content">
                <!-- Semester Selection -->
                <div class="card animate-fade-in-up" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <h3 class="card-title">Select Semester</h3>
                    </div>
                    <form method="GET" style="display: flex; gap: var(--space-4); align-items: end; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-input" onchange="this.form.submit()">
                                <option value="">Choose semester...</option>
                                <?php foreach ($semesters as $sem): ?>
                                    <option value="<?php echo $sem['SemesterID']; ?>" <?php echo $sem['SemesterID'] == $selectedSemester ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sem['SemesterName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <?php if ($studentClass): ?>
                        <p style="margin-top: var(--space-3); font-size: var(--font-sm); color: var(--text-muted);">
                            Showing results for class: <strong style="color: var(--primary-400);"><?php echo htmlspecialchars($studentClass); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Results Table -->
                <?php if ($selectedSemester > 0 && !empty($classResults)): ?>
                    <div class="card animate-fade-in-up delay-2">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title"><?php echo htmlspecialchars($semesterName); ?> — Class <?php echo htmlspecialchars($studentClass); ?></h3>
                                <p class="card-subtitle"><?php echo count($classResults); ?> student(s) • Ranked by percentage</p>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Total</th>
                                        <th>Percentage</th>
                                        <th>Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; foreach ($classResults as $r): ?>
                                        <?php
                                            $isMe = ($r['StudentID'] === $studentId);
                                            $badgeClass = 'badge-pass';
                                            if ($r['Class'] === 'Distinction') $badgeClass = 'badge-distinction';
                                            elseif ($r['Class'] === 'First Class') $badgeClass = 'badge-first';
                                            elseif ($r['Class'] === 'Second Class') $badgeClass = 'badge-second';
                                            elseif ($r['Class'] === 'Fail') $badgeClass = 'badge-fail';
                                        ?>
                                        <tr style="<?php echo $isMe ? 'background: rgba(99, 102, 241, 0.08);' : ''; ?>">
                                            <td><strong><?php echo $rank++; ?></strong></td>
                                            <td><?php echo htmlspecialchars($r['StudentID']); ?> <?php echo $isMe ? '⭐' : ''; ?></td>
                                            <td><strong><?php echo htmlspecialchars($r['Name']); ?></strong></td>
                                            <td><?php echo $r['TotalMarks']; ?></td>
                                            <td><?php echo $r['Percentage']; ?>%</td>
                                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($r['Class']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif ($selectedSemester > 0): ?>
                    <div class="card animate-fade-in-up">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-text">No results found</div>
                            <div class="empty-state-sub">No results available for your class in this semester.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
