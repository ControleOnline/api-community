<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use ControleOnline\Entity\City;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\SchoolTeamSchedule;
use ControleOnline\Entity\Team;
use ControleOnline\Entity\Particulars;
use App\Library\Utils\Formatter;
use ControleOnline\Entity\MyContractProduct;
use App\Library\Provider\Signature\ContractDocument;

class GetContractDocumentAction extends ContractDocument
{
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;
  }

  public function __invoke(MyContract $data, Request $request)
  {
    $this->request = $request;

    $content  = $this->getContractContent($data);
    $response = new StreamedResponse(function () use ($content) {
      fputs(fopen('php://output', 'wb'), $content);
    });

    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    $response->headers->set(
      'Content-Disposition',
      HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, 'contract.html')
    );

    return $response;
  }
}
