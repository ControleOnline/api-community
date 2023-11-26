<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\MyContract;
use App\Entity\MyContractPeople;
use App\Entity\People;
use App\Entity\PeopleTeam;
use App\Entity\PeopleProfessional;
use App\Entity\SchoolTeamSchedule;
use App\Entity\SchoolProfessionalWeekly;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class CreateTeamScheduleAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager  = null;

    /**
     * People Students
     *
     * @var array
     */
    private $students  = [];

    /**
     * Professional people
     *
     * @var \App\Entity\People
     */
    private $professional   = null;

    /**
     * Start time
     *
     * @var \DateTime
     */
    private $startTime = null;

    /**
     * End time
     *
     * @var \DateTime
     */
    private $endTime   = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(MyContract $data, Request $request): MyContract
    {
        $contract = $this->manager->getRepository(Contract::class)->find($data->getId());
        $payload  = json_decode($request->getContent(), true);

        // validate payload

        $this->validateData($data, $payload);

        $provider = $this->getMyProvider($request);

        // create team

        $team = new Team;

        $team->setCompanyTeam($provider);
        $team->setContract   ($contract);
        $team->setType       ($payload['teamType']);

        $this->manager->persist($team);

        // create people team (students and professional)

        $peopleTeamProfessional = new PeopleTeam;

        $peopleTeamProfessional->setTeam      ($team);
        $peopleTeamProfessional->setPeople    ($this->professional);
        $peopleTeamProfessional->setPeopleType('professional');
        $peopleTeamProfessional->setEnable    (true);

        $this->manager->persist($peopleTeamProfessional);

        foreach ($this->students as $student) {
          $peopleTeamStudent = new PeopleTeam;

          $peopleTeamStudent->setTeam      ($team);
          $peopleTeamStudent->setPeople    ($student);
          $peopleTeamStudent->setPeopleType('student');
          $peopleTeamStudent->setEnable    (true);

          $this->manager->persist($peopleTeamStudent);
        }

        // create team schedule

        $teamSchedule = new SchoolTeamSchedule;

        $teamSchedule->setTeam         ($team);
        $teamSchedule->setPeopleProfessional(
            $this->manager->getRepository(PeopleProfessional::class)
                ->findOneBy([
                    'professional' => $this->professional,
                    'company' => $provider
                ])
        );
        $teamSchedule->setWeekDay  ($payload['weekDay']);
        $teamSchedule->setStartTime($this->startTime);
        $teamSchedule->setEndTime  ($this->endTime);

        $this->manager->persist($teamSchedule);

        return $data;
    }

    private function validateData(MyContract $contract, array $data): void
    {
        if (!isset($data['teamType']) || empty($data['teamType']))
            throw new \Exception('Team type is not defined', 400);
        else {
            if (!in_array($data['teamType'], ['school', 'ead', 'company']))
                throw new \Exception('Team type is not valid', 400);
        }

        if (!isset($data['students']) || !is_array($data['students']))
            throw new \Exception('Students is not defined', 400);
        else {
            foreach ($data['students'] as $student) {
                if (isset($student['peopleContractId'])) {
                    /**
                     * @var \App\Entity\MyContractPeople $contractPeople
                     */
                    $contractPeople = $this->manager->getRepository(MyContractPeople::class)
                        ->findOneBy([
                            'id'         => $student['peopleContractId'],
                            'contract'   => $contract,
                            'peopleType' => 'Beneficiary',
                        ]);
                    if ($contractPeople === null)
                        throw new \Exception('People contract id is not valid', 404);

                    $this->students[] = $contractPeople->getPeople();
                }
            }

            if (empty($this->students))
                throw new \Exception('There is no students', 400);
        }

        if (!isset($data['peopleProfessionalId']))
            throw new \Exception('People professional id is not defined', 400);
        else {
            /**
             * @var \App\Entity\PeopleProfessional $peopleProfessional
             */
            $peopleProfessional = $this->manager->getRepository(PeopleProfessional::class)->find($data['peopleProfessionalId']);

            if ($peopleProfessional === null)
                throw new \Exception(sprintf('People professional "%s" not found', $data['peopleProfessionalId']), 400);

            $this->professional = $peopleProfessional->getProfessional();
        }

        if (!isset($data['weekDay']))
            throw new \Exception('Weekday is not defined', 400);
        else {
            if (!in_array($data['weekDay'], SchoolProfessionalWeekly::WEEK_DAYS))
                throw new \Exception('Weekday is not valid', 400);
        }

        if (!isset($data['startTime']))
            throw new \Exception('StartTime is not defined', 400);
        else {
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['startTime']) !== 1)
                throw new \Exception('StartTime is not valid', 400);

            $this->startTime = \DateTime::createFromFormat('H:i:s', $data['startTime']);
        }

        if (!isset($data['endTime']))
            throw new \Exception('EndTime is not defined', 400);
        else {
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['endTime']) !== 1)
                throw new \Exception('EndTime is not valid', 400);

            $this->endTime = \DateTime::createFromFormat('H:i:s', $data['endTime']);
        }

        if ($this->startTime > $this->endTime)
            throw new \Exception('StartTime can not be greater than EndTime', 400);
    }

    private function getMyProvider(Request $request): People
    {
        $providerId = $request->query->get('myProvider', null);

        if ($providerId === null)
            throw new \Exception('Provider Id is not defined');

        $provider = $this->manager->getRepository(People::class)->find($providerId);

        if ($provider === null)
            throw new \Exception('Provider not found');

        return $provider;
    }
}
