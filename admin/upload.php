<?php
/**
 * Admin Upload Page
 * University Result Management System
 * 
 * Handles PDF upload, text extraction using Smalot/PdfParser,
 * parsing of student result data, and database insertion.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY SemesterID")->fetchAll();

$message = '';
$messageType = '';
$parsedResults = [];

// Handle PDF upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semesterId = intval($_POST['semester_id'] ?? 0);

    if ($semesterId <= 0) {
        $message = 'Please select a semester';
        $messageType = 'error';
    } elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please upload a valid file';
        $messageType = 'error';
    } else {
        $file = $_FILES['pdf_file'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate file type
        if ($fileExt !== 'pdf') {
            $message = 'Only PDF files are allowed';
            $messageType = 'error';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $message = 'File size must be less than 5 MB';
            $messageType = 'error';
        } else {
            // Move uploaded file
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $uploadPath = $uploadDir . uniqid('result_') . '_' . $fileName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Try to parse PDF
                try {
                    // Check if composer autoload exists
                    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
                    if (file_exists($autoloadPath)) {
                        require_once $autoloadPath;
                        
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($uploadPath);
                        $text = $pdf->getText();

                        // Parse the extracted text
                        $parsedResults = parseResultText($text);

                        if (!empty($parsedResults)) {
                            $inserted = 0;
                            $pdo->beginTransaction();

                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO results (StudentID, Name, SemesterID, Subjects, TotalMarks, Percentage, Class)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE 
                                        Subjects = VALUES(Subjects),
                                        TotalMarks = VALUES(TotalMarks),
                                        Percentage = VALUES(Percentage),
                                        Class = VALUES(Class)
                                ");

                                foreach ($parsedResults as $result) {
                                    $subjects = json_encode($result['subjects']);
                                    $stmt->execute([
                                        $result['student_id'],
                                        $result['name'],
                                        $semesterId,
                                        $subjects,
                                        $result['total'],
                                        $result['percentage'],
                                        $result['class']
                                    ]);

                                    // Also ensure student exists in users table
                                    $checkUser = $pdo->prepare("SELECT UserID FROM users WHERE StudentID = ?");
                                    $checkUser->execute([$result['student_id']]);
                                    if (!$checkUser->fetch()) {
                                        $insertUser = $pdo->prepare("
                                            INSERT INTO users (StudentID, Name, Password, Role, Class)
                                            VALUES (?, ?, ?, 'student', ?)
                                        ");
                                        // Default password is the student ID
                                        $hashedPw = password_hash($result['student_id'], PASSWORD_DEFAULT);
                                        $insertUser->execute([
                                            $result['student_id'],
                                            $result['name'],
                                            $hashedPw,
                                            $result['class'] ?? 'General'
                                        ]);
                                    }

                                    $inserted++;
                                }

                                $pdo->commit();
                                $message = "Successfully imported {$inserted} result(s) from PDF";
                                $messageType = 'success';
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                throw $e;
                            }
                        } else {
                            $message = 'Could not parse any results from the PDF. Please ensure the PDF follows the expected format. Check the README for the sample format.';
                            $messageType = 'warning';
                        }
                    } else {
                        // Composer not installed — show manual entry fallback
                        $message = 'PDF parsing library not installed. Run "composer install" in the project root. You can also add results manually below.';
                        $messageType = 'warning';
                    }
                } catch (Exception $e) {
                    error_log("PDF parse error: " . $e->getMessage());
                    $message = 'Error parsing PDF: ' . htmlspecialchars($e->getMessage());
                    $messageType = 'error';
                }
            } else {
                $message = 'Failed to upload file';
                $messageType = 'error';
            }
        }
    }
}

// Handle manual result entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_entry'])) {
    $studentId = trim($_POST['m_student_id'] ?? '');
    $name = trim($_POST['m_name'] ?? '');
    $semesterId = intval($_POST['m_semester_id'] ?? 0);
    $subjectsRaw = trim($_POST['m_subjects'] ?? '');
    $totalMarks = floatval($_POST['m_total'] ?? 0);
    $percentage = floatval($_POST['m_percentage'] ?? 0);
    $class = trim($_POST['m_class'] ?? '');

    if (empty($studentId) || empty($name) || $semesterId <= 0) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } else {
        try {
            // Parse subjects (format: "Subject1:90, Subject2:85")
            $subjects = [];
            $pairs = explode(',', $subjectsRaw);
            foreach ($pairs as $pair) {
                $parts = explode(':', trim($pair));
                if (count($parts) === 2) {
                    $subjects[trim($parts[0])] = intval(trim($parts[1]));
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO results (StudentID, Name, SemesterID, Subjects, TotalMarks, Percentage, Class)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$studentId, $name, $semesterId, json_encode($subjects), $totalMarks, $percentage, $class]);

            // Ensure user exists
            $checkUser = $pdo->prepare("SELECT UserID FROM users WHERE StudentID = ?");
            $checkUser->execute([$studentId]);
            if (!$checkUser->fetch()) {
                $insertUser = $pdo->prepare("INSERT INTO users (StudentID, Name, Password, Role) VALUES (?, ?, ?, 'student')");
                $insertUser->execute([$studentId, $name, password_hash($studentId, PASSWORD_DEFAULT)]);
            }

            $message = 'Result added successfully';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}

/**
 * Parse extracted PDF text into structured result data.
 * 
 * Expected PDF format (each student block):
 * StudentID: STU001
 * Name: John Doe
 * Mathematics: 90
 * Physics: 85
 * Chemistry: 78
 * English: 92
 * Computer Science: 88
 * Total: 433
 * Percentage: 86.60
 * Class: First Class
 * ---
 * 
 * @param string $text Extracted PDF text
 * @return array Parsed results
 */
function parseResultText(string $text): array {
    $results = [];
    
    // Split by separator lines or double newlines
    $blocks = preg_split('/(-{3,}|\n{3,})/', $text);
    
    foreach ($blocks as $block) {
        $block = trim($block);
        if (empty($block)) continue;
        
        $lines = explode("\n", $block);
        $result = [
            'student_id' => '',
            'name' => '',
            'subjects' => [],
            'total' => 0,
            'percentage' => 0,
            'class' => ''
        ];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Match key: value pattern
            if (preg_match('/^(.+?):\s*(.+)$/', $line, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);
                
                $keyLower = strtolower($key);
                
                if ($keyLower === 'studentid' || $keyLower === 'student id' || $keyLower === 'student_id') {
                    $result['student_id'] = $value;
                } elseif ($keyLower === 'name' || $keyLower === 'student name') {
                    $result['name'] = $value;
                } elseif ($keyLower === 'total' || $keyLower === 'totalmarks' || $keyLower === 'total marks') {
                    $result['total'] = floatval($value);
                } elseif ($keyLower === 'percentage' || $keyLower === 'percent') {
                    $result['percentage'] = floatval($value);
                } elseif ($keyLower === 'class' || $keyLower === 'grade' || $keyLower === 'division') {
                    $result['class'] = $value;
                } else {
                    // Must be a subject
                    if (is_numeric($value)) {
                        $result['subjects'][$key] = intval($value);
                    }
                }
            }
        }
        
        // Only add if we have enough data
        if (!empty($result['student_id']) && !empty($result['name']) && !empty($result['subjects'])) {
            // Auto-calculate total and percentage if not given
            if ($result['total'] == 0) {
                $result['total'] = array_sum($result['subjects']);
            }
            if ($result['percentage'] == 0 && count($result['subjects']) > 0) {
                $result['percentage'] = round($result['total'] / (count($result['subjects']) * 100) * 100, 2);
            }
            if (empty($result['class'])) {
                if ($result['percentage'] >= 85) $result['class'] = 'Distinction';
                elseif ($result['percentage'] >= 70) $result['class'] = 'First Class';
                elseif ($result['percentage'] >= 55) $result['class'] = 'Second Class';
                elseif ($result['percentage'] >= 40) $result['class'] = 'Pass Class';
                else $result['class'] = 'Fail';
            }
            $results[] = $result;
        }
    }
    
    return $results;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results — Admin</title>
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
                <a href="dashboard.php" class="sidebar-link">
                    <span class="link-icon">📊</span> Dashboard
                </a>
                <a href="upload.php" class="sidebar-link active">
                    <span class="link-icon">📤</span> Upload Results
                </a>
                <div class="sidebar-nav-label">Management</div>
                <a href="manage_students.php" class="sidebar-link">
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

        <!-- Main -->
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn">☰</button>
                    <h2 class="topbar-title">Upload Results</h2>
                </div>
            </header>

            <div class="page-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <span><?php echo $messageType === 'success' ? '✅' : ($messageType === 'error' ? '❌' : '⚠️'); ?></span>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Upload PDF Card -->
                <div class="card animate-fade-in-up" style="margin-bottom: var(--space-6);">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Upload Result PDF</h3>
                            <p class="card-subtitle">Upload a structured PDF file to import student results</p>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" data-validate>
                        <div class="form-group">
                            <label class="form-label" for="semester_id">Select Semester</label>
                            <select name="semester_id" id="semester_id" class="form-input" required>
                                <option value="">Choose semester...</option>
                                <?php foreach ($semesters as $sem): ?>
                                    <option value="<?php echo $sem['SemesterID']; ?>"><?php echo htmlspecialchars($sem['SemesterName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Result PDF File</label>
                            <div class="file-upload-zone">
                                <span class="upload-icon">📄</span>
                                <div class="upload-text">
                                    <strong>Click to upload</strong> or drag and drop<br>
                                    PDF files only, max 5 MB
                                </div>
                                <input type="file" name="pdf_file" accept=".pdf" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="btn-text">📤 Upload & Parse PDF</span>
                            <span class="spinner"></span>
                        </button>
                    </form>
                </div>

                <!-- Manual Entry Card -->
                <div class="card animate-fade-in-up delay-2">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Manual Entry</h3>
                            <p class="card-subtitle">Add a single student result manually</p>
                        </div>
                    </div>

                    <form method="POST" data-validate>
                        <input type="hidden" name="manual_entry" value="1">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                            <div class="form-group">
                                <label class="form-label" for="m_student_id">Student ID</label>
                                <input type="text" id="m_student_id" name="m_student_id" class="form-input" placeholder="e.g. STU001" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="m_name">Student Name</label>
                                <input type="text" id="m_name" name="m_name" class="form-input" placeholder="e.g. John Doe" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="m_semester_id">Semester</label>
                            <select name="m_semester_id" id="m_semester_id" class="form-input" required>
                                <option value="">Choose semester...</option>
                                <?php foreach ($semesters as $sem): ?>
                                    <option value="<?php echo $sem['SemesterID']; ?>"><?php echo htmlspecialchars($sem['SemesterName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="m_subjects">Subjects & Marks</label>
                            <input type="text" id="m_subjects" name="m_subjects" class="form-input" 
                                   placeholder="Mathematics:90, Physics:85, Chemistry:78" required>
                            <small style="color: var(--text-muted); font-size: var(--font-xs); margin-top: var(--space-1); display: block;">
                                Format: Subject1:Marks, Subject2:Marks, ...
                            </small>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-4);">
                            <div class="form-group">
                                <label class="form-label" for="m_total">Total Marks</label>
                                <input type="number" id="m_total" name="m_total" class="form-input" placeholder="450" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="m_percentage">Percentage</label>
                                <input type="number" id="m_percentage" name="m_percentage" class="form-input" placeholder="90.00" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="m_class">Class/Grade</label>
                                <select name="m_class" id="m_class" class="form-input" required>
                                    <option value="">Select...</option>
                                    <option value="Distinction">Distinction</option>
                                    <option value="First Class">First Class</option>
                                    <option value="Second Class">Second Class</option>
                                    <option value="Pass Class">Pass Class</option>
                                    <option value="Fail">Fail</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg">
                            <span class="btn-text">✅ Add Result</span>
                            <span class="spinner"></span>
                        </button>
                    </form>
                </div>

                <!-- Expected PDF Format -->
                <div class="card animate-fade-in-up delay-3" style="margin-top: var(--space-6);">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Expected PDF Format</h3>
                            <p class="card-subtitle">Your PDF should contain text in this structure</p>
                        </div>
                    </div>
                    <pre style="background: var(--surface-elevated); padding: var(--space-4); border-radius: var(--radius-lg); font-size: var(--font-sm); color: var(--text-secondary); overflow-x: auto; line-height: 1.8;">
StudentID: STU001
Name: Aarav Sharma
Mathematics: 92
Physics: 88
Chemistry: 85
English: 90
Computer Science: 95
Total: 450
Percentage: 90.00
Class: Distinction
---
StudentID: STU002
Name: Priya Patel
Mathematics: 78
Physics: 82
...</pre>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
