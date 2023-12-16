<?php

namespace App\Controller;

use ControleOnline\Entity\SchoolProfessionalWeekly;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class GetAvailableProfessionalsAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Request $request)
    {
        $this->request = $request;

        $weekDay    = $this->request->query->get('weekDay'  , null);
        $startTime  = $this->request->query->get('startTime', null);
        $endTime    = $this->request->query->get('endTime'  , null);

        // validate params

        if (empty($weekDay))
            throw new \Exception('Weekday is not defined', 400);
        else {
            if (!in_array($weekDay, SchoolProfessionalWeekly::WEEK_DAYS))
                throw new \Exception('Weekday is not valid', 400);
        }

        if (empty($startTime))
            throw new \Exception('StartTime is not defined', 400);
        else {
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTime) !== 1)
                throw new \Exception('StartTime is not valid', 400);
        }

        if (empty($endTime))
            throw new \Exception('EndTime is not defined', 400);
        else {
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $endTime) !== 1)
                throw new \Exception('EndTime is not valid', 400);
        }

        if (\DateTime::createFromFormat('H:m:i', $startTime) > \DateTime::createFromFormat('H:m:i', $endTime))
            throw new \Exception('StartTime can not be greater than EndTime', 400);

        /**
         * @var \ControleOnline\Repository\SchoolProfessionalWeeklyRepository
         */
        $repository = $this->manager->getRepository(SchoolProfessionalWeekly::class);

        return $repository->getAvailableProfessionals($weekDay, $startTime, $endTime);
    }
}
