<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected GoogleClient $client;
    protected GoogleDrive $driveService;

    public function __construct()
    {
        $credentialsPath = storage_path('app/private/google/service-account.json');

        if (!file_exists($credentialsPath)) {
            throw new \Exception('Google Service Account credentials file not found.');
        }

        $this->client = new GoogleClient();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope(GoogleDrive::DRIVE_FILE);

        $this->driveService = new GoogleDrive($this->client);
    }

    public function uploadFile(string $filePath, string $fileName, string $folderId): string
    {
        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [$folderId],
        ]);

        $content = file_get_contents($filePath);

        $file = $this->driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/sql',
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);

        return $file->id;
    }

    public function testConnection(string $folderId): array
    {
        try {
            $results = $this->driveService->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed = false",
                'pageSize' => 1,
                'fields' => 'files(id, name)',
            ]);

            return [
                'success' => true,
                'message' => 'Connected to folder successfully. Found ' . count($results->getFiles()) . ' existing file(s).',
            ];
        } catch (\Google\Service\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, '404')) {
                return ['success' => false, 'message' => 'Folder not found. Make sure the folder ID is correct and shared with the service account.'];
            }
            if (str_contains($message, '403')) {
                return ['success' => false, 'message' => 'Access denied. Share the folder with the service account email as Editor.'];
            }
            return ['success' => false, 'message' => $message];
        }
    }
}
