<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\WatermarkGenerator;

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

try {
    // Validate form submission
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['text'])) {
        throw new Exception('Watermark text is required');
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed: ' . ($_FILES['image']['error'] ?? 'No file uploaded'));
    }

    $watermarkText = trim($_POST['text']);
    if (strlen($watermarkText) > 100) {
        throw new Exception('Watermark text too long (max 100 characters)');
    }

    // Move uploaded file
    $uploadedFile = $_FILES['image'];
    $tempPath = $uploadedFile['tmp_name'];
    $originalName = $uploadedFile['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Generate unique filename
    $filename = uniqid() . '.' . $extension;
    $imagePath = $uploadsDir . '/' . $filename;
    
    if (!move_uploaded_file($tempPath, $imagePath)) {
        throw new Exception('Failed to save uploaded file');
    }

    // Generate watermark
    $generator = new WatermarkGenerator();
    $imageData = $generator->addWatermark($imagePath, $watermarkText);
    $mimeType = $generator->getMimeType($imagePath);

    // Clean up uploaded file
    unlink($imagePath);

    // Set headers and output image
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="watermarked-image.' . $extension . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $imageData;

} catch (Exception $e) {
    // Clean up on error
    if (isset($imagePath) && file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    // Display error page
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Eroare la procesarea imaginii</h1>';
    echo '<p>Mesaj eroare: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="watermark-form.html">← Încearcă din nou</a>';
}

?>