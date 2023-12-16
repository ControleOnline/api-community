<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use ControleOnline\Entity\People;
use ControleOnline\Entity\SchoolClass;
use ControleOnline\Entity\PeopleProfessional;

class SearchClassesPeopleAction extends AbstractController
{
    public function __invoke(People $data, Request $request): JsonResponse
    {
      try {
        $from     = $request->query->get('from'  , null);
        $to       = $request->query->get('to'    , null);
        $date     = $request->query->get('date'  , null);
        $status   = $request->query->get('status', null);
        $page     = $request->query->get('page'  , 1);
        $limit    = $request->query->get('limit' , 10);
        $paginate = [
          'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
          'limit' => !is_numeric($limit) ? 10 : $limit
        ];
        $search   = [
          'from'       => $from,
          'to'         => $to,
          'date'       => $date,
          'status'     => $status,
          'professional_id' => $data->getId(),
          'company'    => -1,
        ];

        $professionalSchool = $this->getDoctrine()->getRepository(PeopleProfessional::class)
          ->findOneBy([
            'professional' => $data
          ]);
        if ($professionalSchool !== null) {
          $search['company'] = $professionalSchool->getCompany()->getId();
        }

        $repository = $this->getDoctrine()->getRepository(SchoolClass::class);

        return $this->json([
          'response' => [
            'data'    => $repository->getAllProfessionalClasses($search, $paginate),
            'count'   => $repository->getAllProfessionalClasses($search, null, true),
            'error'   => '',
            'success' => true,
          ],
        ]);
      } catch (\Doctrine\DBAL\Driver\Exception $e) {
          return $this->json($e->getMessage());
      } catch (\Exception $e) {
          return $this->json($e->getMessage());
      }
    }
}
