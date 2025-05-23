<?php

namespace App\Helpers;

class FileHelper {
    
    private $filePath;
    private $thumbnailPath;
    private $fileType;
    private $fileExtension;

    /**
     * Constructor
     * 
     * @param string $filePath Path to the document file
     * @param string $thumbnailPath Optional thumbnail path
     */
    public function __construct($filePath = null, $thumbnailPath = null) {
        $this->filePath = $filePath;
        $this->thumbnailPath = $thumbnailPath;
        
        if ($filePath) {
            $this->detectFileType($filePath);
        }
    }
    
    /**
     * Detect the file type and extension from a file
     */
    private function detectFileType($filePath) {
        // Get file extension
        $this->fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Determine file type from extension
        switch ($this->fileExtension) {
            case 'pdf':
                $this->fileType = 'pdf';
                break;
            case 'ppt':
            case 'pptx':
                $this->fileType = 'powerpoint';
                break;
            case 'epub':
                $this->fileType = 'epub';
                break;
            case 'mobi':
            case 'azw':
            case 'azw3':
                $this->fileType = 'kindle';
                break;
            case 'djvu':
                $this->fileType = 'djvu';
                break;
            case 'doc':
            case 'docx':
                $this->fileType = 'word';
                break;
            default:
                $this->fileType = 'unknown';
        }
    }

    /**
     * Extracts a thumbnail image from the document
     */
    public function extractThumbnail($filePath, $outputPath, $format = 'jpg') {
        // If we don't know the file type yet, detect it
        if (empty($this->fileType)) {
            $this->detectFileType($filePath);
        }
        
        // Use appropriate method based on file type
        switch ($this->fileType) {
            case 'pdf':
                return $this->extractPdfThumbnail($filePath, $outputPath, $format);
                
            case 'powerpoint':
                return $this->extractPowerPointThumbnail($filePath, $outputPath, $format);
                
            case 'epub':
                return $this->extractEpubThumbnail($filePath, $outputPath, $format);
                
            case 'word':
                return $this->extractWordThumbnail($filePath, $outputPath, $format);
            case 'kindle':
            case 'djvu':
            default:
                // For formats we can't extract thumbnails from yet, use a format-specific placeholder
                return $this->useTypePlaceholder($outputPath, $this->fileType);
        }
    }
    

    /**
     * Extract thumbnail from Word document
     */
    private function extractWordThumbnail($docPath, $outputPath, $format = 'jpg') {
        // Create temp directory for conversion output
        $tempDir = sys_get_temp_dir() . '/docconvert_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        chmod($tempDir, 0777); // Ensure temp directory is writable
        
        // Create user profile directories for LibreOffice
        $userProfileDir = $tempDir . '/libreoffice_profile';
        if (!is_dir($userProfileDir)) {
            mkdir($userProfileDir, 0777, true);
        }

        $outputFileName = basename($docPath);
        $baseFileName = pathinfo($outputFileName, PATHINFO_FILENAME);
        
        error_log("Converting Word document to PDF: $docPath using temp dir: $tempDir");
        
        // Set environment variables to suppress javaldx warnings
        $environmentVars = array(
            'HOME' => $tempDir,
            'JAVALDX_IGNORE_CHECKS' => '1', // Suppress javaldx warnings
            'UserInstallation' => "file://$userProfileDir" // Custom profile path
        );
        
        // Build environment string
        $envString = '';
        foreach ($environmentVars as $key => $value) {
            $envString .= "$key=" . escapeshellarg($value) . " ";
        }
        
        // First try with all environment variables
        $libreOfficeCommand = "$envString libreoffice --headless --convert-to pdf --outdir " . escapeshellarg($tempDir) . ' ' . escapeshellarg($docPath) . " 2>&1";
        error_log("Executing: $libreOfficeCommand");
        
        exec($libreOfficeCommand, $output, $returnCode);
        error_log("LibreOffice conversion output: " . implode("\n", $output) . " (Return code: $returnCode)");
        
        // The PDF will have the same name as the input file but with .pdf extension
        $pdfPath = $tempDir . '/' . $baseFileName . '.pdf';
        
        // If the first attempt fails, try with simplified environment
        if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
            error_log("First conversion attempt failed, trying alternative method");
            
            // Alternative approach with environment variables
            $simpleCommand = "HOME=" . escapeshellarg($tempDir) . " libreoffice --headless --convert-to pdf --outdir " . escapeshellarg($tempDir) . ' ' . escapeshellarg($docPath) . " 2>/dev/null";
            error_log("Executing alternative command: $simpleCommand");
            
            exec($simpleCommand, $output2, $returnCode2);
            error_log("Alternative LibreOffice conversion output: " . implode("\n", $output2) . " (Return code: $returnCode2)");
        }
        
        error_log("Looking for converted PDF at: $pdfPath");
        
        if (file_exists($pdfPath) && is_readable($pdfPath)) {
            error_log("Word document successfully converted to PDF: $pdfPath");
            $thumbnail = $this->extractPdfThumbnail($pdfPath, $outputPath, $format);
            
            // Clean up temporary files
            @unlink($pdfPath);
            $this->removeDirectory($userProfileDir);
            @rmdir($tempDir);
            
            if ($thumbnail) {
                return $thumbnail;
            }
        }
        
        // If both conversion attempts failed, extract metadata and create a custom thumbnail
        if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
            error_log("Failed to convert Word document to PDF: $docPath (output file not found)");
            
            // Extract metadata from the Word document
            $metadata = $this->extractWordMetadata($docPath);
            
            // Create a custom thumbnail with document metadata
            if ($metadata && !empty($metadata['title'])) {
                error_log("Creating custom thumbnail from Word document metadata");
                
                // Clean up temporary directories before returning
                $this->removeDirectory($userProfileDir);
                @rmdir($tempDir);
                
                return $this->createMetadataThumbnail($outputPath, $metadata, 'word');
            }
            
            // Clean up temp directory
            $this->removeDirectory($userProfileDir);
            @rmdir($tempDir);
            
            // Fall back to word document placeholder
            error_log("Using Word document placeholder image for thumbnail");
            return $this->useTypePlaceholder($outputPath, 'word');
        }
    }
    
    /**
     * Recursively remove a directory and its contents
     *
     * @param string $dir Directory path to remove
     * @return bool Success or failure
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }

    /**
     * Extract thumbnail from PDF file
     */
    private function extractPdfThumbnail($pdfPath, $outputPath, $format = 'jpg') {
        // Check if Imagick is installed and available
        if (!extension_loaded('imagick') || !class_exists('\\Imagick')) {
            error_log("Imagick extension not available - using fallback image");
            return $this->useTypePlaceholder($outputPath, 'pdf');
        }

        try {
            // Check if the PDF exists and is readable
            if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
                error_log("PDF file not found or not readable: $pdfPath");
                return $this->useTypePlaceholder($outputPath, 'pdf');
            }
            
            error_log("Attempting to extract first page from PDF: $pdfPath");
            
            // Create Imagick instance
            $imagick = new \Imagick();
            
            // Set resolution for better quality
            $imagick->setResolution(150, 150); // Reduced resolution for better performance
            
            try {
                // Try to read the first page of the PDF with error handling
                $readResult = $imagick->readImage($pdfPath . '[0]');
                if (!$readResult) {
                    throw new \Exception("Failed to read the file");
                }
            } catch (\Exception $e) {
                error_log("Initial PDF read failed: " . $e->getMessage() . ". Trying alternative method...");
                
                // Try alternative approach with lower resolution
                $imagick->clear();
                $imagick->setResolution(72, 72);
                
                try {
                    if (!$imagick->readImage($pdfPath . '[0]')) {
                        throw new \Exception("Failed to read with alternative method");
                    }
                } catch (\Exception $e2) {
                    error_log("Alternative PDF read failed: " . $e2->getMessage());
                    return $this->useTypePlaceholder($outputPath, 'pdf');
                }
            }
            
            // Convert to the desired format
            $imagick->setImageFormat($format);
            
            // Optimize the image
            $imagick->setImageCompressionQuality(85);
            
            // Resize if necessary to avoid huge thumbnails
            $width = $imagick->getImageWidth();
            if ($width > 800) {
                $imagick->resizeImage(800, 0, \Imagick::FILTER_LANCZOS, 1);
            }
            
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                if (!@mkdir($outputDir, 0777, true)) {
                    error_log("Failed to create thumbnail directory: $outputDir");
                    return $this->useTypePlaceholder($outputPath, 'pdf');
                }
                // Explicitly set permissions after creation
                @chmod($outputDir, 0777);
            }
            
            error_log("Writing PDF thumbnail to: $outputPath");
            
            // Write the image to the output path
            $imagick->writeImage($outputPath);
            
            // Clear the Imagick object
            $imagick->clear();
            $imagick->destroy();
            
            if (file_exists($outputPath)) {
                error_log("PDF thumbnail created successfully");
                return true;
            } else {
                error_log("Thumbnail file not created - using fallback");
                return $this->useTypePlaceholder($outputPath, 'pdf');
            }
        } catch (\Exception $e) {
            error_log("PDF thumbnail extraction failed: " . $e->getMessage());
            return $this->useTypePlaceholder($outputPath, 'pdf');
        }
    }
    
    /**
     * Extract thumbnail from PowerPoint file
     */
    private function extractPowerPointThumbnail($pptPath, $outputPath, $format = 'jpg') {
        try {
            // Use type placeholder as default
            return $this->useTypePlaceholder($outputPath, 'powerpoint');
            
            // NOTE: For production, you'd implement PowerPoint thumbnail extraction here
            // This would typically involve a PHP library that can read PowerPoint
            // files or using a command-line tool via exec()
        } catch (\Exception $e) {
            error_log("PowerPoint thumbnail extraction failed: " . $e->getMessage());
            return $this->useTypePlaceholder($outputPath, 'powerpoint');
        }
    }
    
    /**
     * Extract thumbnail from EPUB file
     */
    private function extractEpubThumbnail($epubPath, $outputPath, $format = 'jpg') {
        try {
            // EPUBs are ZIP files with a specific structure
            $zip = new \ZipArchive();
            if ($zip->open($epubPath) === true) {
                
                // Try to find the cover image in the EPUB
                $coverFound = false;
                
                // Look for cover in meta-data
                $container = $zip->getFromName('META-INF/container.xml');
                if ($container) {
                    // Parse container XML to find content opf path
                    $xml = new \SimpleXMLElement($container);
                    $ns = $xml->getNamespaces(true);
                    $rootfile = $xml->rootfiles->rootfile['full-path'];
                    
                    if ($rootfile) {
                        // Get content.opf file
                        $contentOpf = $zip->getFromName($rootfile);
                        if ($contentOpf) {
                            // Look for cover image reference
                            $opfXml = new \SimpleXMLElement($contentOpf);
                            $opfNs = $opfXml->getNamespaces(true);
                            
                            // Different EPUBs may use different approaches to specify cover
                            // Try to find meta cover ID
                            $coverId = null;
                            foreach ($opfXml->metadata->meta as $meta) {
                                if ((string)$meta['name'] === 'cover') {
                                    $coverId = (string)$meta['content'];
                                    break;
                                }
                            }
                            
                            // If cover ID found, look for matching item
                            if ($coverId) {
                                foreach ($opfXml->manifest->item as $item) {
                                    if ((string)$item['id'] === $coverId) {
                                        $coverPath = (string)$item['href'];
                                        // Adjust path if needed
                                        $basedir = dirname($rootfile);
                                        if ($basedir != '.') {
                                            $coverPath = $basedir . '/' . $coverPath;
                                        }
                                        
                                        // Extract cover file
                                        $coverData = $zip->getFromName($coverPath);
                                        if ($coverData) {
                                            // Make sure output directory exists
                                            $outputDir = dirname($outputPath);
                                            if (!is_dir($outputDir)) {
                                                if (!@mkdir($outputDir, 0777, true)) {
                                                    error_log("Failed to create thumbnail directory: $outputDir");
                                                    break;
                                                }
                                                @chmod($outputDir, 0777);
                                            }
                                            
                                            // Save cover image
                                            if (file_put_contents($outputPath, $coverData)) {
                                                $coverFound = true;
                                                error_log("EPUB cover extracted successfully");
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                
                $zip->close();
                
                if ($coverFound) {
                    return true;
                }
            }
            
            // If we couldn't extract a cover, use placeholder
            return $this->useTypePlaceholder($outputPath, 'epub');
            
        } catch (\Exception $e) {
            error_log("EPUB thumbnail extraction failed: " . $e->getMessage());
            return $this->useTypePlaceholder($outputPath, 'epub');
        }
    }
    
    /**
     * Use a placeholder image for the specified file type
     */
    private function useTypePlaceholder($outputPath, $type = 'generic') {
        try {
            // Make sure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir) && !@mkdir($outputDir, 0777, true)) {
                error_log("Failed to create directory: $outputDir");
                return false;
            }
            
            // Determine which placeholder to use based on file type
            $placeholderFile = 'placeholder-book.jpg'; // Default placeholder
            
            switch ($type) {
                case 'pdf':
                    $placeholderFile = 'placeholder-pdf.jpg';
                    break;
                case 'powerpoint':
                    $placeholderFile = 'placeholder-powerpoint.jpg';
                    break;
                case 'epub':
                    $placeholderFile = 'placeholder-epub.jpg';
                    break;
                case 'kindle':
                    $placeholderFile = 'placeholder-kindle.jpg';
                    break;
                case 'djvu':
                    $placeholderFile = 'placeholder-djvu.jpg';
                    break;
                case 'word':
                    $placeholderFile = 'placeholder-word.jpg';
                    break;
            }
            
            // Path to placeholder file
            $placeholderPath = __DIR__ . '/../../public/assets/uploads/thumbnails/' . $placeholderFile;
            
            // If the specific placeholder doesn't exist, fall back to the generic one
            if (!file_exists($placeholderPath)) {
                $placeholderPath = __DIR__ . '/../../public/assets/uploads/thumbnails/placeholder-book.jpg';
            }
            
            // If even the generic placeholder doesn't exist, create a blank one
            if (!file_exists($placeholderPath)) {
                // Create a blank image
                $img = imagecreatetruecolor(200, 300);
                $bgColor = imagecolorallocate($img, 240, 240, 240);
                $textColor = imagecolorallocate($img, 50, 50, 50);
                imagefilledrectangle($img, 0, 0, 200, 300, $bgColor);
                
                // Add text
                $text = strtoupper($type);
                $fontFile = __DIR__ . '/../../public/assets/fonts/roboto/Roboto-Regular.ttf';
                if (!file_exists($fontFile)) {
                    // Use built-in font if TTF file doesn't exist
                    imagestring($img, 5, 50, 140, $text, $textColor);
                } else {
                    // Use custom font
                    imagettftext($img, 16, 0, 50, 150, $textColor, $fontFile, $text);
                }
                
                // Save the image to the placeholder path
                imagejpeg($img, $placeholderPath, 90);
                imagedestroy($img);
            }
            
            // Copy the placeholder to the output path
            if (file_exists($placeholderPath)) {
                return copy($placeholderPath, $outputPath);
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error using placeholder image: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets or creates a thumbnail for the document
     */
    public function getThumbnail() {
        // Use environment detection for Docker compatibility
        if (getenv('DOCKER_ENV') === 'true') {
            $uploadDir = '/var/www/html/public';
            $thumbnailDir = $uploadDir . '/assets/uploads/thumbnails';
            $webPath = '/assets/uploads/thumbnails';
        } else {
            // Local development path
            $projectRoot = dirname(dirname(dirname(__DIR__)));
            $uploadDir = $projectRoot . '/public';
            $thumbnailDir = $uploadDir . '/assets/uploads/thumbnails';
            $webPath = '/assets/uploads/thumbnails';
        }
        
        // Create the directory if it doesn't exist
        if (!is_dir($thumbnailDir)) {
            if (!@mkdir($thumbnailDir, 0777, true)) {
                error_log("Failed to create thumbnail directory: $thumbnailDir");
                return '/assets/uploads/thumbnails/placeholder-book.jpg';
            }
            // Set permissions explicitly
            @chmod($thumbnailDir, 0777);
        }
        
        // Generate a unique name for the thumbnail
        $thumbnailName = md5(basename($this->filePath)) . '.jpg';
        $thumbnailPath = $thumbnailDir . '/' . $thumbnailName;
        
        // Check if thumbnail already exists
        if (!file_exists($thumbnailPath)) {
            // Extract thumbnail from document
            if (!$this->extractThumbnail($this->filePath, $thumbnailPath)) {
                // Return a default image if extraction fails
                return '/assets/uploads/thumbnails/placeholder-book.jpg';
            }
        }
        
        return $webPath . '/' . $thumbnailName;
    }

    /**
     * Stores a document file with a proper name
     */
    public function storeFile($file) {
        try {
            // Check if the upload was successful
            if ($file['error'] !== UPLOAD_ERR_OK) {
                error_log("Upload error code: " . $file['error']);
                return false;
            }
            
            // Get file information
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];
            
            // Extract file extension
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Set file type based on extension
            $this->fileExtension = $fileExtension;
            $this->detectFileType($fileName);
            
            // Validate supported file types
            $supportedTypes = [
                'pdf', 'ppt', 'pptx', 'epub', 'mobi', 'azw', 'azw3', 'djvu', 'doc', 'docx'
            ];
            
            if (!in_array($fileExtension, $supportedTypes)) {
                error_log("Invalid file extension: $fileExtension");
                return false;
            }
            
            // Generate a unique name for the file
            $newFileName = uniqid('doc_') . '.' . $fileExtension;
            
            // Use environment detection for Docker compatibility
            if (getenv('DOCKER_ENV') === 'true') {
                $uploadDir = '/var/www/html/public';
                $uploadFileDir = $uploadDir . '/assets/uploads/documents/';
                $webPath = '/assets/uploads/documents';
            } else {
                // Local development path
                $projectRoot = dirname(dirname(dirname(__DIR__)));
                $uploadDir = $projectRoot . '/public';
                $uploadFileDir = $uploadDir . '/assets/uploads/documents/';
                $webPath = '/assets/uploads/documents';
            }
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadFileDir)) {
                if (!@mkdir($uploadFileDir, 0777, true)) {
                    error_log("Failed to create directory: $uploadFileDir");
                    return false;
                }
                // Set permissions explicitly
                @chmod($uploadFileDir, 0777);
            }
            
            // Destination path
            $dest_path = $uploadFileDir . $newFileName;
            
            // Move the file
            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                error_log("Failed to move file from $fileTmpPath to $dest_path");
                return false;
            }
            
            // Set the full server path for internal use
            $this->filePath = $dest_path;
            
            // Return web-accessible path
            return [
                'path' => $webPath . '/' . $newFileName,
                'type' => $this->fileType,
                'extension' => $fileExtension
            ];
        } catch (\Exception $e) {
            error_log("Error storing file: " . $e->getMessage());
            return false;
        }
    }

}