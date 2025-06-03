<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class InvoiceGenerator
{
    private string $browserlessUrl;
    private Client $httpClient;

    public function __construct(string $browserlessUrl = 'http://browserless:3000')
    {
        $this->browserlessUrl = $browserlessUrl;
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10
        ]);
    }

    /**
     * Generate PDF from HTML content
     */
    public function generatePDF(string $htmlContent): string
    {
        try {
            $response = $this->httpClient->post($this->browserlessUrl . '/pdf', [
                'json' => [
                    'html' => $htmlContent,
                    'options' => [
                        'format' => 'A4',
                        'margin' => [
                            'top' => '20mm',
                            'right' => '20mm',
                            'bottom' => '20mm',
                            'left' => '20mm'
                        ],
                        'printBackground' => true,
                        'preferCSSPageSize' => true
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to generate PDF: HTTP ' . $response->getStatusCode());
            }

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new \Exception('Error connecting to Browserless: ' . $e->getMessage());
        }
    }

    /**
     * Get invoice HTML content
     */
    public function getInvoiceHTML(): string
    {
        $invoicePath = __DIR__ . '/../public/invoice.html';
        
        if (!file_exists($invoicePath)) {
            throw new \Exception('Invoice template not found');
        }

        ob_start();
        include $invoicePath;
        return ob_get_clean();
    }

    /**
     * Validate Browserless connection
     */
    public function validateConnection(): bool
    {
        try {
            $response = $this->httpClient->get($this->browserlessUrl . '/');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }
}

?>

