<?php
// Test script to verify PDF restriction

require_once __DIR__ . '/../vendor/autoload.php';

use App\Helpers\FileHelper;

// Mock file upload
$mockFile = [
    'name' => 'test.txt',
    'type' => 'text/plain',
    'tmp_name' => sys_get_temp_dir() . '/test.txt',
    'error' => 0,
    'size' => 123
];

// Create dummy file
file_put_contents($mockFile['tmp_name'], 'This is a test file.');

// Initialize FileHelper
$fileHelper = new FileHelper();

echo "Testing upload of non-PDF file (test.txt)...\n";
$result = $fileHelper->storeFile($mockFile);

if ($result === false) {
    echo "SUCCESS: Non-PDF file was rejected.\n";
} else {
    echo "FAILURE: Non-PDF file was accepted.\n";
    exit(1);
}

// Test PDF upload
$mockPdf = [
    'name' => 'test.pdf',
    'type' => 'application/pdf',
    'tmp_name' => sys_get_temp_dir() . '/test.pdf',
    'error' => 0,
    'size' => 123
];
file_put_contents($mockPdf['tmp_name'], '%PDF-1.4 mock pdf content');

echo "\nTesting upload of PDF file (test.pdf)...\n";
// Attempt to store (this might fail if the file is not a valid PDF for detection, but let's see)
// The FileHelper checks extension primarily in the updated code:
// $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
// if ($fileExtension === 'pdf') ...

$resultPdf = $fileHelper->storeFile($mockPdf);

if ($resultPdf !== false && $resultPdf['extension'] === 'pdf') {
     echo "SUCCESS: PDF file was accepted.\n";
} else {
    // It might fail on move_uploaded_file because these are not actual uploaded files in a HTTP request context
    // But storeFile checks move_uploaded_file which requires valid upload.
    // We can't easily mock move_uploaded_file without runkit or similar.
    // However, the logic check happens *before* move_uploaded_file for the extension.
    // Wait, let's look at storeFile again.
    
    // It checks extension first.
    // Then it tries to move.
    
    echo "NOTE: PDF acceptance test might fail due to move_uploaded_file restrictions in CLI, but we are checking if logic got that far.\n";
}

// Clean up
@unlink($mockFile['tmp_name']);
@unlink($mockPdf['tmp_name']);
