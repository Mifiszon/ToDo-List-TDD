<?php

/**
 * File upload service tests.
 */

namespace App\Tests\Service;

use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileUploadServiceTest.
 */
class FileUploadServiceTest extends KernelTestCase
{
    /**
     * File upload service.
     */
    private ?FileUploadService $fileUploadService;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->fileUploadService = $container->get(FileUploadService::class);
    }

    /**
     * Test upload.
     */
    public function testUpload(): void
    {
        // given
        $sourcePath = tempnam(sys_get_temp_dir(), 'test_file');
        file_put_contents($sourcePath, 'test content');
        $uploadedFile = new UploadedFile(
            $sourcePath,
            'original-name.png',
            'image/png',
            null,
            true
        );
        $expectedExtension = $uploadedFile->guessExtension();

        // when
        $resultFilename = $this->fileUploadService->upload($uploadedFile);

        // then
        $this->assertStringContainsString('original-name', $resultFilename);
        if (null !== $expectedExtension) {
            $this->assertStringEndsWith('.'.$expectedExtension, $resultFilename);
        }
        $targetPath = $this->fileUploadService->getTargetDirectory().'/'.$resultFilename;
        $this->assertFileExists($targetPath);

        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
    }

    /**
     * Test get target directory.
     */
    public function testGetTargetDirectory(): void
    {
        // when
        $result = $this->fileUploadService->getTargetDirectory();

        // then
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }
}
