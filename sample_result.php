<?php
/**
 * Sample Result PDF Generator
 * University Result Management System
 * 
 * Run this script to generate a sample PDF file that can be uploaded
 * through the admin panel for testing.
 * 
 * Usage (from command line): php sample_result.php
 * This will create a file: sample_results.pdf in the uploads/ directory
 * 
 * If TCPDF is not installed, it will create a text file with the expected format.
 */

$sampleData = <<<EOT
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
Chemistry: 75
English: 88
Computer Science: 80
Total: 403
Percentage: 80.60
Class: First Class
---
StudentID: STU003
Name: Rohan Gupta
Mathematics: 65
Physics: 70
Chemistry: 60
English: 72
Computer Science: 68
Total: 335
Percentage: 67.00
Class: Second Class
---
StudentID: STU004
Name: Ananya Reddy
Mathematics: 95
Physics: 91
Chemistry: 93
English: 89
Computer Science: 97
Total: 465
Percentage: 93.00
Class: Distinction
---
StudentID: STU005
Name: Vikram Singh
Mathematics: 55
Physics: 60
Chemistry: 58
English: 62
Computer Science: 50
Total: 285
Percentage: 57.00
Class: Pass Class
EOT;

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Try to generate a proper PDF using TCPDF
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    
    if (class_exists('TCPDF')) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('UniResults Sample Generator');
        $pdf->SetTitle('Sample Results');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        $pdf->SetFont('courier', '', 11);
        
        $lines = explode("\n", $sampleData);
        foreach ($lines as $line) {
            $pdf->Cell(0, 6, $line, 0, 1);
        }
        
        $outputPath = $uploadDir . 'sample_results.pdf';
        $pdf->Output($outputPath, 'F');
        echo "✅ Sample PDF created at: {$outputPath}\n";
        echo "Upload this file through the admin panel to test PDF parsing.\n";
        exit;
    }
}

// Fallback: save as text file with .txt extension showing expected format
$outputPath = $uploadDir . 'sample_results_format.txt';
file_put_contents($outputPath, $sampleData);
echo "📄 Sample format saved at: {$outputPath}\n";
echo "Note: TCPDF not installed. Run 'composer install' first to generate a proper PDF.\n";
echo "You can still view this file to understand the expected PDF text format.\n";
echo "\n--- Expected Format ---\n\n";
echo $sampleData . "\n";
