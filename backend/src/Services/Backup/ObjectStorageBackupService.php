<?php

namespace App\Services\Backup;

use App\Services\System\EnvReader;
use Aws\S3\S3Client;
use Exception;

/**
 * Uploads backup files to S3-compatible object storage (AWS S3, MinIO, DigitalOcean Spaces).
 */
class ObjectStorageBackupService
{
    public function __construct()
    {
    }

    public function isConfigured(): bool
    {
        if (!EnvReader::isBackupObjectStorageEnabled()) {
            return false;
        }
        $key = EnvReader::getBackupS3AccessKey();
        $secret = EnvReader::getBackupS3SecretKey();
        return !empty($key) && !empty($secret);
    }

    /**
     * Upload a local file to object storage.
     *
     * @param string $localFilePath Absolute path to the file
     * @param string|null $remoteKey Optional key (path) in the bucket. Defaults to path prefix + filename.
     * @return string The full S3 URI of the uploaded file
     * @throws Exception
     */
    public function uploadFile(string $localFilePath, ?string $remoteKey = null): string
    {
        if (!$this->isConfigured()) {
            throw new Exception('Object storage backup is not configured or enabled.');
        }

        if (!file_exists($localFilePath) || !is_readable($localFilePath)) {
            throw new Exception("Local file does not exist or is not readable: {$localFilePath}");
        }

        $client = $this->createS3Client();
        $bucket = EnvReader::getBackupS3Bucket();
        $prefix = rtrim(EnvReader::getBackupS3PathPrefix(), '/');
        $key = $remoteKey ?? $prefix . '/' . basename($localFilePath);

        $client->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'SourceFile' => $localFilePath,
        ]);

        return "s3://{$bucket}/{$key}";
    }

    private function createS3Client(): S3Client
    {
        $config = [
            'version' => 'latest',
            'region'  => EnvReader::getBackupS3Region(),
            'credentials' => [
                'key'    => EnvReader::getBackupS3AccessKey(),
                'secret' => EnvReader::getBackupS3SecretKey(),
            ],
        ];

        $endpoint = EnvReader::getBackupS3Endpoint();
        if (!empty($endpoint) && !str_contains($endpoint, 'amazonaws.com')) {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }

        return new S3Client($config);
    }
}
