<?php

namespace Tests\Unit\Helpers;

use App\Helpers\FileHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Upload validation only: accepting a file ends in move_uploaded_file(), which needs a real HTTP upload.
 */
class FileHelperTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = tempnam(sys_get_temp_dir(), 'elib');
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function rejectedUploads(): array
    {
        return [
            'not a pdf' => ['notes.txt', 'plain text', 'Only PDF files are supported.'],
            'pdf header, wrong extension' => ['book.pdf.php', '%PDF-1.4', 'Only PDF files are supported.'],
            'renamed to .pdf' => ['renamed.pdf', '<?php echo 1;', 'File is not a valid PDF.'],
            'uppercase extension, fake content' => ['BOOK.PDF', 'GIF89a', 'File is not a valid PDF.'],
        ];
    }

    #[DataProvider('rejectedUploads')]
    public function testRejectsAnythingButARealPdf(string $name, string $content, string $reason): void
    {
        file_put_contents($this->tmp, $content);
        $helper = new FileHelper();

        $result = $helper->storeFile(['name' => $name, 'type' => 'application/pdf', 'tmp_name' => $this->tmp, 'error' => UPLOAD_ERR_OK, 'size' => 10]);

        $this->assertFalse($result);
        $this->assertSame($reason, $helper->rejectReason);
    }

    public function testFailedUploadIsRejected(): void
    {
        $result = (new FileHelper())->storeFile(['name' => 'a.pdf', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_PARTIAL, 'size' => 0]);

        $this->assertFalse($result);
    }
}
