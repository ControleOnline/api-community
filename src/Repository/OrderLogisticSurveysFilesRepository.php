<?php

namespace App\Repository;

use App\Entity\OrderLogisticSurveysFiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderLogisticSurveysFiles|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderLogisticSurveysFiles|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderLogisticSurveysFiles[]    findAll()
 * @method OrderLogisticSurveysFiles[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderLogisticSurveysFilesRepository extends ServiceEntityRepository
{

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager;

    private $surveyId;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct($registry, OrderLogisticSurveysFiles::class);
    }

    public function getAllPhotosFromSurveyId($surveyId): array
    {

        $this->surveyId = $surveyId;

        $sql = "select tsf.id, tsf.region, tsf.breakdown from order_logistic_surveys_files as tsf
                where tsf.order_logistic_surveys_id = :surveyIdParam
                order by tsf.id desc";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('region', 'region', 'string');
        $rsm->addScalarResult('breakdown', 'breakdown', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $nqu->setParameter('surveyIdParam', $surveyId);

        return $this->hydratePhotoGallery($nqu->getResult());

    }

    private function hydratePhotoGallery($ret): array
    {

        if ((!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') ||
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === '443')) {
            $server_request_scheme = 'https';
        } else {
            $server_request_scheme = 'http';
        }

        $serverName = $_SERVER["SERVER_NAME"];
        $baseUrl = $server_request_scheme . '://' . $serverName . '/';

        foreach ($ret as $key => $val) {
            $id = $val['id'];
            $ret[$key]['path_real_size'] = $baseUrl . "order_logistic_surveys/{$this->surveyId}/{$id}/viewphoto/realsize";
            $ret[$key]['path_thumb'] = $baseUrl . "order_logistic_surveys/{$this->surveyId}/{$id}/viewphoto/thumb";
        }

        return $ret;

    }

    // /**
    //  * @return TasksSurveysFiles[] Returns an array of TasksSurveysFiles objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TasksSurveysFiles
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
