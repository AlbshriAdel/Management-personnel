<?php

namespace App\Action\Modules\ScientificPapers;

use App\Attribute\ModuleAttribute;
use App\Entity\Modules\ScientificPapers\MyScientificPaper;
use App\Repository\Modules\ScientificPapers\MyScientificPaperRepository;
use App\Response\Base\BaseResponse;
use App\Services\Module\ModulesService;
use App\Services\RequestService;
use App\Services\TypeProcessor\ArrayHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/module/scientific-papers", name: "module.scientific_papers.")]
#[ModuleAttribute(values: ["name" => ModulesService::MODULE_NAME_SCIENTIFIC_PAPERS])]
class MyScientificPapersAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MyScientificPaperRepository $paperRepository,
    ) {
    }

    #[Route("", name: "new", methods: [Request::METHOD_POST])]
    public function new(Request $request): JsonResponse
    {
        return $this->createOrUpdate($request)->toJsonResponse();
    }

    #[Route("/all", name: "get_all", methods: [Request::METHOD_GET])]
    public function getAll(): JsonResponse
    {
        $allPapers = $this->paperRepository->getAllNotDeleted();

        $entriesData = [];
        foreach ($allPapers as $paper) {
            $entriesData[] = [
                'id' => $paper->getId(),
                'title' => $paper->getTitle(),
                'abstract' => $paper->getAbstract(),
                'status' => $paper->getStatus(),
                'createdAt' => $paper->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $paper->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        $response = BaseResponse::buildOkResponse();
        $response->setAllRecordsData($entriesData);

        return $response->toJsonResponse();
    }

    #[Route("/{id}", name: "get_one", methods: [Request::METHOD_GET])]
    public function getOne(int $id): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($id);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $checklistData = [];
        foreach ($paper->getChecklistItems() as $item) {
            $checklistData[] = [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'completed' => $item->isCompleted(),
                'sortOrder' => $item->getSortOrder(),
            ];
        }

        $versionsData = [];
        foreach ($paper->getVersions() as $version) {
            $versionsData[] = [
                'id' => $version->getId(),
                'name' => $version->getName(),
                'createdAt' => $version->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        $data = [
            'id' => $paper->getId(),
            'title' => $paper->getTitle(),
            'abstract' => $paper->getAbstract(),
            'status' => $paper->getStatus(),
            'createdAt' => $paper->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $paper->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'checklistItems' => $checklistData,
            'versions' => $versionsData,
        ];

        $response = BaseResponse::buildOkResponse();
        $response->setSingleRecordData($data);

        return $response->toJsonResponse();
    }

    #[Route("/{id}", name: "update", methods: [Request::METHOD_PATCH])]
    public function update(int $id, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($id);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        return $this->createOrUpdate($request, $paper)->toJsonResponse();
    }

    #[Route("/{id}", name: "remove", methods: [Request::METHOD_DELETE])]
    public function remove(int $id): JsonResponse
    {
        $paper = $this->paperRepository->find($id);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $paper->setDeleted(true);
        $paper->setUpdatedAt(new \DateTime());
        $this->em->persist($paper);
        $this->em->flush();

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    private function createOrUpdate(Request $request, ?MyScientificPaper $paper = null): BaseResponse
    {
        $isNew = $paper === null;
        if ($isNew) {
            $paper = new MyScientificPaper();
        }

        $dataArray = RequestService::tryFromJsonBody($request);
        $title = ArrayHandler::get($dataArray, 'title', allowEmpty: false);
        $abstract = ArrayHandler::get($dataArray, 'abstract', allowEmpty: true, defaultValue: '');
        $status = ArrayHandler::get($dataArray, 'status', allowEmpty: true, defaultValue: MyScientificPaper::STATUS_IN_PROGRESS);

        $paper->setTitle($title);
        $paper->setAbstract($abstract ?: null);
        if (in_array($status, MyScientificPaper::STATUSES, true)) {
            $paper->setStatus($status);
        }
        $paper->setUpdatedAt(new \DateTime());

        $this->em->persist($paper);
        $this->em->flush();

        return BaseResponse::buildOkResponse();
    }
}
