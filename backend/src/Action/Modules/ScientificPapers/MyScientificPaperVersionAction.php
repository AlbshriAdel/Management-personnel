<?php

namespace App\Action\Modules\ScientificPapers;

use App\Attribute\ModuleAttribute;
use App\Entity\Modules\ScientificPapers\MyScientificPaperVersion;
use App\Repository\Modules\ScientificPapers\MyScientificPaperRepository;
use App\Response\Base\BaseResponse;
use App\Services\Module\ModulesService;
use App\Services\RequestService;
use App\Services\System\EnvReader;
use App\Services\TypeProcessor\ArrayHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/module/scientific-papers/{paperId}/versions", name: "module.scientific_papers.versions.")]
#[ModuleAttribute(values: ["name" => ModulesService::MODULE_NAME_SCIENTIFIC_PAPERS])]
class MyScientificPaperVersionAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MyScientificPaperRepository $paperRepository,
    ) {
    }

    #[Route("", name: "new", methods: [Request::METHOD_POST])]
    public function new(int $paperId, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $dataArray = RequestService::tryFromJsonBody($request);
        $name = ArrayHandler::get($dataArray, 'name', allowEmpty: false);

        $version = new MyScientificPaperVersion();
        $version->setPaper($paper);
        $version->setName($name);

        $this->em->persist($version);
        $this->em->flush();

        // Create directory for version files
        $basePath = $this->getVersionBasePath($paperId, $version->getId());
        (new Filesystem())->mkdir($basePath, 0755);

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    #[Route("/{id}", name: "remove", methods: [Request::METHOD_DELETE])]
    public function remove(int $paperId, int $id): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $version = $this->em->getRepository(MyScientificPaperVersion::class)->find($id);
        if (!$version || $version->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        // Remove directory and files
        $basePath = $this->getVersionBasePath($paperId, $id);
        if (is_dir($basePath)) {
            (new Filesystem())->remove($basePath);
        }

        $this->em->remove($version);
        $this->em->flush();

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    private function getVersionBasePath(int $paperId, int $versionId): string
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadDir = EnvReader::getScientificPapersUploadDir();

        return $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
            . $uploadDir . DIRECTORY_SEPARATOR . $paperId . DIRECTORY_SEPARATOR . $versionId;
    }
}
