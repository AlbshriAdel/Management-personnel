<?php

namespace App\Action\Modules\ScientificPapers;

use App\Attribute\ModuleAttribute;
use App\Repository\Modules\ScientificPapers\MyScientificPaperRepository;
use App\Repository\Modules\ScientificPapers\MyScientificPaperVersionRepository;
use App\Response\Base\BaseResponse;
use App\Services\Files\PathService;
use App\Services\Module\ModulesService;
use App\Services\RequestService;
use App\Services\System\EnvReader;
use App\Services\TypeProcessor\ArrayHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/module/scientific-papers/{paperId}/versions/{versionId}/files", name: "module.scientific_papers.files.")]
#[ModuleAttribute(values: ["name" => ModulesService::MODULE_NAME_SCIENTIFIC_PAPERS])]
class MyScientificPaperFilesAction extends AbstractController
{
    public function __construct(
        private readonly MyScientificPaperRepository $paperRepository,
        private readonly MyScientificPaperVersionRepository $versionRepository,
    ) {
    }

    #[Route("/upload-path", name: "upload_path", methods: [Request::METHOD_GET])]
    public function getUploadPath(int $paperId, int $versionId, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $version = $this->versionRepository->find($versionId);
        if (!$version || $version->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $subPath = $request->query->get('subPath', '');
        $fullPath = $this->getVersionPath($paperId, $versionId, $subPath);

        $response = BaseResponse::buildOkResponse();
        $response->setSingleRecordData([
            'uploadPath' => $fullPath,
            'uploadConfigId' => \App\Services\Files\Upload\FileUploadConfigurator::SCIENTIFIC_PAPERS_FILES_ID,
        ]);

        return $response->toJsonResponse();
    }

    #[Route("/list", name: "list", methods: [Request::METHOD_GET])]
    public function listFiles(int $paperId, int $versionId, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $version = $this->versionRepository->find($versionId);
        if (!$version || $version->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $subPath = $request->query->get('subPath', '');
        $dirPath = $this->getVersionPath($paperId, $versionId, $subPath);

        if (!is_dir($dirPath)) {
            $response = BaseResponse::buildOkResponse();
            $response->setAllRecordsData(['files' => [], 'folders' => []]);
            return $response->toJsonResponse();
        }

        $files = [];
        $folders = [];

        foreach (new \DirectoryIterator($dirPath) as $item) {
            if ($item->isDot()) {
                continue;
            }

            PathService::validatePathSafety($item->getFilename());

            if ($item->isDir()) {
                $folders[] = [
                    'name' => $item->getFilename(),
                    'path' => $subPath ? $subPath . DIRECTORY_SEPARATOR . $item->getFilename() : $item->getFilename(),
                ];
            } else {
                $filePath = $dirPath . DIRECTORY_SEPARATOR . $item->getFilename();
                $publicPath = EnvReader::getScientificPapersUploadDir() . '/' . $paperId . '/' . $versionId;
                if ($subPath !== '') {
                    $publicPath .= '/' . str_replace(DIRECTORY_SEPARATOR, '/', $subPath);
                }
                $publicPath .= '/' . $item->getFilename();
                $files[] = [
                    'name' => $item->getFilename(),
                    'path' => $filePath,
                    'publicPath' => $publicPath,
                    'size' => $item->getSize(),
                    'type' => $item->getExtension() ?: 'unknown',
                    'createdAt' => date('Y-m-d H:i:s', $item->getCTime()),
                ];
            }
        }

        $response = BaseResponse::buildOkResponse();
        $response->setSingleRecordData([
            'files' => $files,
            'folders' => $folders,
        ]);

        return $response->toJsonResponse();
    }

    #[Route("/folder", name: "create_folder", methods: [Request::METHOD_POST])]
    public function createFolder(int $paperId, int $versionId, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $version = $this->versionRepository->find($versionId);
        if (!$version || $version->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $dataArray = RequestService::tryFromJsonBody($request);
        $folderName = ArrayHandler::get($dataArray, 'folderName', allowEmpty: false);
        $parentPath = ArrayHandler::get($dataArray, 'parentPath', allowEmpty: true, defaultValue: '');

        PathService::validatePathSafety($folderName);
        PathService::validatePathSafety($parentPath);

        $fullPath = $this->getVersionPath($paperId, $versionId, $parentPath);
        $this->ensurePathWithinScientificPapers($fullPath);

        $newDirPath = $fullPath . DIRECTORY_SEPARATOR . $folderName;

        if (file_exists($newDirPath)) {
            return BaseResponse::buildBadRequestErrorResponse('Folder with this name already exists')->toJsonResponse();
        }

        if (!mkdir($newDirPath, 0755, true)) {
            return BaseResponse::buildInternalServerErrorResponse('Could not create folder')->toJsonResponse();
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    #[Route("/delete-file", name: "delete_file", methods: [Request::METHOD_POST])]
    public function deleteFile(int $paperId, int $versionId, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $version = $this->versionRepository->find($versionId);
        if (!$version || $version->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $dataArray = RequestService::tryFromJsonBody($request);
        $filePath = ArrayHandler::get($dataArray, 'filePath', allowEmpty: false);

        PathService::validatePathSafety($filePath);
        $this->ensurePathWithinScientificPapers($filePath);

        if (!is_file($filePath)) {
            return BaseResponse::buildBadRequestErrorResponse('File not found')->toJsonResponse();
        }

        if (!unlink($filePath)) {
            return BaseResponse::buildInternalServerErrorResponse('Could not delete file')->toJsonResponse();
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    private function getVersionPath(int $paperId, int $versionId, string $subPath = ''): string
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadDir = EnvReader::getScientificPapersUploadDir();
        $basePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
            . $uploadDir . DIRECTORY_SEPARATOR . $paperId . DIRECTORY_SEPARATOR . $versionId;

        if ($subPath !== '') {
            $basePath .= DIRECTORY_SEPARATOR . str_replace(['..', '/'], ['', DIRECTORY_SEPARATOR], $subPath);
        }

        return $basePath;
    }

    private function ensurePathWithinScientificPapers(string $path): void
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $allowedBase = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . EnvReader::getScientificPapersUploadDir();
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $normalizedBase = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedBase);

        if (!str_starts_with($normalizedPath, $normalizedBase)) {
            throw new \LogicException('Path is outside of scientific papers directory');
        }
    }
}
