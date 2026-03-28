<?php
/**
 * Download Individual Result as PDF
 * University Result Management System
 * 
 * Generates a formatted PDF of the student's result for a given semester
 * using TCPDF library. Falls back to HTML-to-PDF if TCPDF not installed.
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
$semesterId = intval($_GET['semester'] ?? 0);

if ($semesterId <= 0) {
    die('Invalid semester');
}

// Fetch result
$stmt = $pdo->prepare("
    SELECT r.*, s.SemesterName 
    FROM results r 
    JOIN semesters s ON r.SemesterID = s.SemesterID 
    WHERE r.StudentID = ? AND r.SemesterID = ?
");
$stmt->execute([$studentId, $semesterId]);
$result = $stmt->fetch();

if (!$result) {
    die('No result found for this semester');
}

$subjects = json_decode($result['Subjects'], true);

// Try TCPDF first
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    
    if (class_exists('TCPDF')) {
        // Generate PDF using TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        $pdf->SetCreator('UniResults');
        $pdf->SetAuthor('University Result Management System');
        $pdf->SetTitle('Result - ' . $result['Name'] . ' - ' . $result['SemesterName']);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(63, 81, 181);
        $pdf->Cell(0, 12, 'University Result Management System', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, 'Academic Result Statement', 0, 1, 'C');
        
        $pdf->Ln(5);
        $pdf->SetDrawColor(63, 81, 181);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(8);
        
        // Student Info
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(40, 7, 'Student ID:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(70, 7, $result['StudentID'], 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(30, 7, 'Semester:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, $result['SemesterName'], 0, 1);
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(40, 7, 'Student Name:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(70, 7, $result['Name'], 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(30, 7, 'Class:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, $result['Class'], 0, 1);
        
        $pdf->Ln(8);
        
        // Marks Table Header
        $pdf->SetFillColor(63, 81, 181);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(15, 9, '#', 1, 0, 'C', true);
        $pdf->Cell(100, 9, 'Subject', 1, 0, 'L', true);
        $pdf->Cell(35, 9, 'Marks', 1, 0, 'C', true);
        $pdf->Cell(30, 9, 'Status', 1, 1, 'C', true);
        
        // Marks Table Body
        $pdf->SetTextColor(50, 50, 50);
        $pdf->SetFont('helvetica', '', 10);
        $i = 1;
        $fill = false;
        foreach ($subjects as $subjectName => $marks) {
            if ($fill) {
                $pdf->SetFillColor(240, 242, 255);
            }
            $status = $marks >= 40 ? 'PASS' : 'FAIL';
            $pdf->Cell(15, 8, $i++, 1, 0, 'C', $fill);
            $pdf->Cell(100, 8, $subjectName, 1, 0, 'L', $fill);
            $pdf->Cell(35, 8, $marks . '/100', 1, 0, 'C', $fill);
            $pdf->Cell(30, 8, $status, 1, 1, 'C', $fill);
            $fill = !$fill;
        }
        
        $pdf->Ln(5);
        
        // Summary
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(240, 242, 255);
        $pdf->Cell(115, 9, 'Total Marks', 1, 0, 'R', true);
        $pdf->Cell(65, 9, $result['TotalMarks'] . '/' . (count($subjects) * 100), 1, 1, 'C', true);
        
        $pdf->Cell(115, 9, 'Percentage', 1, 0, 'R', true);
        $pdf->Cell(65, 9, $result['Percentage'] . '%', 1, 1, 'C', true);
        
        $pdf->Cell(115, 9, 'Result', 1, 0, 'R', true);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(65, 9, $result['Class'], 1, 1, 'C', true);
        
        $pdf->Ln(15);
        
        // Footer
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 6, 'Generated on ' . date('F j, Y \a\t h:i A'), 0, 1, 'C');
        $pdf->Cell(0, 6, 'This is a computer-generated document. No signature required.', 0, 1, 'C');
        
        // Output
        $fileName = 'Result_' . $result['StudentID'] . '_' . str_replace(' ', '_', $result['SemesterName']) . '.pdf';
        $pdf->Output($fileName, 'D');
        exit;
    }
}

// Fallback: Generate HTML-based downloadable page
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result - <?php echo htmlspecialchars($result['Name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #333; padding: 40px; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4338ca; padding-bottom: 20px; }
        .header h1 { font-size: 24px; color: #4338ca; margin-bottom: 5px; }
        .header p { color: #666; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 25px; }
        .info-item { padding: 8px 0; }
        .info-item label { color: #888; font-size: 12px; display: block; text-transform: uppercase; letter-spacing: 1px; }
        .info-item strong { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th { background: #4338ca; color: white; padding: 10px 15px; text-align: left; font-size: 13px; }
        td { padding: 10px 15px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f8f9ff; }
        .summary { background: #f0f2ff; padding: 20px; border-radius: 8px; text-align: center; }
        .summary .total { font-size: 28px; font-weight: bold; color: #4338ca; }
        .summary .label { color: #888; font-size: 12px; text-transform: uppercase; }
        .print-btn { display: block; margin: 30px auto 0; padding: 12px 30px; background: #4338ca; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; }
        .print-btn:hover { background: #3730a3; }
        @media print { .print-btn { display: none; } }
        .footer { text-align: center; margin-top: 30px; color: #aaa; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 University Result Management System</h1>
        <p>Academic Result Statement</p>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <label>Student ID</label>
            <strong><?php echo htmlspecialchars($result['StudentID']); ?></strong>
        </div>
        <div class="info-item">
            <label>Semester</label>
            <strong><?php echo htmlspecialchars($result['SemesterName']); ?></strong>
        </div>
        <div class="info-item">
            <label>Student Name</label>
            <strong><?php echo htmlspecialchars($result['Name']); ?></strong>
        </div>
        <div class="info-item">
            <label>Class</label>
            <strong><?php echo htmlspecialchars($result['Class']); ?></strong>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($subjects as $subName => $marks): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($subName); ?></td>
                    <td><?php echo $marks; ?>/100</td>
                    <td><?php echo $marks >= 40 ? '✅ Pass' : '❌ Fail'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="label">Total Marks</div>
        <div class="total"><?php echo $result['TotalMarks']; ?>/<?php echo count($subjects) * 100; ?></div>
        <div style="margin-top: 10px;">
            <span class="label">Percentage: </span>
            <strong><?php echo $result['Percentage']; ?>%</strong>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <span class="label">Result: </span>
            <strong><?php echo htmlspecialchars($result['Class']); ?></strong>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>

    <div class="footer">
        <p>Generated on <?php echo date('F j, Y \a\t h:i A'); ?></p>
        <p>This is a computer-generated document.</p>
    </div>
</body>
</html>
