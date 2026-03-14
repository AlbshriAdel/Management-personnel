<?php

namespace App\Action\Modules\ScientificPapers;

use App\Attribute\ModuleAttribute;
use App\Entity\Modules\ScientificPapers\MyScientificPaperChecklistItem;
use App\Repository\Modules\ScientificPapers\MyScientificPaperRepository;
use App\Repository\Modules\ScientificPapers\MyScientificPaperChecklistItemRepository;
use App\Response\Base\BaseResponse;
use App\Services\Module\ModulesService;
use App\Services\RequestService;
use App\Services\TypeProcessor\ArrayHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/module/scientific-papers/{paperId}/checklist", name: "module.scientific_papers.checklist.")]
#[ModuleAttribute(values: ["name" => ModulesService::MODULE_NAME_SCIENTIFIC_PAPERS])]
class MyScientificPaperChecklistAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MyScientificPaperRepository $paperRepository,
        private readonly MyScientificPaperChecklistItemRepository $checklistRepository,
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
        $title = ArrayHandler::get($dataArray, 'title', allowEmpty: false);
        $sortOrder = (int) ArrayHandler::get($dataArray, 'sortOrder', allowEmpty: true, defaultValue: 0);

        $item = new MyScientificPaperChecklistItem();
        $item->setPaper($paper);
        $item->setTitle($title);
        $item->setSortOrder($sortOrder);

        $this->em->persist($item);
        $this->em->flush();

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    #[Route("/{id}", name: "update", methods: [Request::METHOD_PATCH])]
    public function update(int $paperId, int $id, Request $request): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $item = $this->checklistRepository->find($id);
        if (!$item || $item->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $dataArray = RequestService::tryFromJsonBody($request);
        if (isset($dataArray['title'])) {
            $item->setTitle($dataArray['title']);
        }
        if (isset($dataArray['completed'])) {
            $item->setCompleted((bool) $dataArray['completed']);
        }
        if (isset($dataArray['sortOrder'])) {
            $item->setSortOrder((int) $dataArray['sortOrder']);
        }

        $this->em->persist($item);
        $this->em->flush();

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    #[Route("/{id}", name: "remove", methods: [Request::METHOD_DELETE])]
    public function remove(int $paperId, int $id): JsonResponse
    {
        $paper = $this->paperRepository->findOneById($paperId);
        if (!$paper) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $item = $this->checklistRepository->find($id);
        if (!$item || $item->getPaper()?->getId() !== $paper->getId()) {
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $this->em->remove($item);
        $this->em->flush();

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }
}
