<?php

namespace App\Controller;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\ParticularsType;
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
