<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\People;
use App\Entity\PeopleDomain;
use App\Entity\PeopleEmployee;
use App\Entity\PeopleFranchisee;
use App\Entity\PeopleSalesman;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class VerifyPeopleStatusAction extends AbstractController
{
    private $currentUser = null;

    public function __construct(Security $security)
    {
        $this->currentUser = $security->getUser();
    }

    public function __invoke(People $data, Request $request): JsonResponse
    {
        try {
            $type = 'user';

            if (null !== $data->getPeopleStudent()) {

              $type = 'student';

            } elseif (null !== $data->getPeopleProfessional()) {

              $type = 'professional';

            } else {

              $mainCompany = $this->getMainCompany();

              // is it salesman?

              $peopleCompany = $data->getPeopleCompany()->first();
              $isSalesman = false;
              if ($peopleCompany !== false) {
                //return $this->json(['error' => 'People without companies'], 400);
              
              $myCompany  = $peopleCompany->getCompany();
              $isSalesman = $mainCompany->getPeopleSalesman()
                  ->exists(
                      function($key, PeopleSalesman $peopleSalesman) use ($myCompany) {
                          return $peopleSalesman->getSalesman() === $myCompany;
                      }
                  );
                }
              if ($isSalesman) {
                $type = 'salesman';
              }
              else {

                // is it admin?

                $isAdmin = $this->getDoctrine()
                  ->getRepository(PeopleFranchisee::class)
                  ->findOneBy([
                    'franchisee' => $this->currentUser->getPeople()
                  ]) !== null;

                if ($isAdmin) {
                  $type = 'franchisee';
                }
                else {

                  // is it super admin?

                  // $isSuper = $mainCompany->getPeopleEmployee()
                  //     ->exists(
                  //         function ($key, PeopleEmployee $peopleEmployee) use ($data) {
                  //             return $peopleEmployee->getEmployee() === $data;
                  //         }
                  //     );
                  // if ($isSuper) {
                  //   $type = 'super';
                  // }
                }
              }
            }

            if (!isset($type)) {
                return $this->json(['error' => 'Unauthorized access'], 401);
            }

            $activeContracts = $this->getDoctrine()->getRepository(Contract::class)
              ->getActiveContracts($data->getId());

            return $this->json([
                'response' => [
                    'data' => [
                        'type' => $type,
                        'active' => ($activeContracts) > 0,
                    ],
                    'error' => '',
                    'success' => true,
                ],
            ]);

        }
        catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retorna a people da empresa principal segundo o dominio da api
     *
     * @return People
     */
    private function getMainCompany(): People
    {
        $domain  = $_SERVER['HTTP_HOST'];
        $company = $this->getDoctrine()->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

        if ($company === null)
            throw new \Exception(
                sprintf('Main company "%s" not found', $domain)
            );

        return $company->getPeople();
    }
}
