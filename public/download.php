<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\InvoiceGenerator;

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="factura-' . date('Y-m-d') . '.pdf"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    $invoiceGenerator = new InvoiceGenerator();
    
    // Check if Browserless is available
    if (!$invoiceGenerator->validateConnection()) {
        throw new Exception('Browserless service is not available. Please ensure Docker container is running.');
    }
    
    // Get HTML content
    $htmlContent = $invoiceGenerator->getInvoiceHTML();
    
    // Generate PDF
    $pdfContent = $invoiceGenerator->generatePDF($htmlContent);
    
    // Output PDF
    echo $pdfContent;
    
} catch (Exception $e) {
    // Reset headers for error display
    header_remove();
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<h1>Eroare la generarea PDF</h1>';
    echo '<p>Mesaj eroare: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<h2>Verificări necesare:</h2>';
    echo '<ul>';
    echo '<li>Asigură-te că Docker este pornit</li>';
    echo '<li>Rulează: <code>docker-compose up -d</code></li>';
    echo '<li>Verifică că browserless este disponibil la http://localhost:3000</li>';
    echo '</ul>';
    echo '<a href="index.html">← Înapoi la meniu principal</a>';
}


?>