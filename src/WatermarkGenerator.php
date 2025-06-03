<?php

namespace App;

class WatermarkGenerator
{
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private int $maxFileSize = 20 * 1024 * 1024; // 20MB

    /**
     * Generate watermark on image
     */
    public function addWatermark(string $imagePath, string $watermarkText): string
    {
        // Validate image
        $this->validateImage($imagePath);
        
        // Get image info
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        // Create image resource based on type
        $image = $this->createImageResource($imagePath, $type);
        if (!$image) {
            throw new \Exception('Failed to create image resource');
        }

        // Add watermark
        $this->drawWatermark($image, $watermarkText, $width, $height);

        // Return image as string
        ob_start();
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, null, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($image);
                break;
            case IMAGETYPE_GIF:
                imagegif($image);
                break;
        }
        $imageData = ob_get_clean();

        // Clean up
        imagedestroy($image);

        return $imageData;
    }

    /**
     * Validate uploaded image
     */
    private function validateImage(string $imagePath): void
    {
        if (!file_exists($imagePath)) {
            throw new \InvalidArgumentException('Image file not found');
        }

        $fileSize = filesize($imagePath);
        if ($fileSize > $this->maxFileSize) {
            throw new \InvalidArgumentException('Image file too large (max 10MB)');
        }

        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo || !in_array($imageInfo['mime'], $this->allowedTypes)) {
            throw new \InvalidArgumentException('Invalid image type. Only JPEG, PNG, and GIF are allowed');
        }
    }

    /**
     * Create image resource from file
     */
    private function createImageResource(string $imagePath, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($imagePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($imagePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($imagePath);
            default:
                return false;
        }
    }

    /**
     * Draw watermark on image
     */
    private function drawWatermark($image, string $text, int $width, int $height): void
    {
        // Calculate font size based on image dimensions
        $fontSize = max(12, min($width, $height) / 25);
        
        // Create colors
        $yellow = imagecolorallocate($image, 255, 255, 0); // Yellow background
        $black = imagecolorallocate($image, 0, 0, 0);      // Black text
        
        // Calculate text dimensions
        $textBox = imagettfbbox($fontSize, 0, $this->getFontPath(), $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        
        // Calculate position (center of image)
        $x = (int)(($width - $textWidth) / 2);
        $y = (int)(($height + $textHeight) / 2);
        
        // Add padding to background
        $padding = 10;
        $bgX1 = (int)($x - $padding);
        $bgY1 = (int)($y - $textHeight - $padding);
        $bgX2 = (int)($x + $textWidth + $padding);
        $bgY2 = (int)($y + $padding);
        
        // Draw yellow background rectangle
        imagefilledrectangle($image, $bgX1, $bgY1, $bgX2, $bgY2, $yellow);
        
        // Draw black border
        imagerectangle($image, $bgX1, $bgY1, $bgX2, $bgY2, $black);
        
        // Draw text
        imagettftext($image, $fontSize, 0, $x, $y, $black, $this->getFontPath(), $text);
    }

    /**
     * Get font path (fallback to built-in font if TTF not available)
     */
    private function getFontPath(): string
    {
        $font = __DIR__ . '/../fonts/DejaVuSans.ttf';
        if (file_exists($font)) {
            return $font;
        }
        // If no TTF font found, we'll use imagestring instead in a wrapper
        return '';
    }

    /**
     * Get MIME type for output
     */
    public function getMimeType(string $imagePath): string
    {
        $imageInfo = getimagesize($imagePath);
        return $imageInfo ? $imageInfo['mime'] : 'image/jpeg';
    }
}