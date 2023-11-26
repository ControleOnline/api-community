<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\Particulars;
use App\Entity\ParticularsType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

class GetPeopleMeAction
{

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(Security $security)
    {
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'response' => [
                'data'    => [
                    'peopleId'  => $this->currentUser->getPeople()->getId(),
                    'companyId' => $this->getMyCompanyId(),
                ],
                'count'   => 1,
                'error'   => '',
                'success' => true,
            ],
        ]);
    }

    private function getMyCompanyId(): ?int
    {
        $companies = $this->currentUser->getPeople()->getPeopleCompany();

        if ($companies->first() === false)
            return null;

        $myCompany = $companies->first()->getCompany();

        return $myCompany->getId();
    }
}
