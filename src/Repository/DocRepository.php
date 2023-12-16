<?php

/** @noinspection UnsupportedStringOffsetOperationsInspection */

namespace App\Repository;

use ControleOnline\Entity\Filesb;
use App\Library\Utils\Formatter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Files|null find($id, $lockMode = null, $lockVersion = null)
 * @method Files|null findOneBy(array $criteria, array $orderBy = null)
 * @method Files[]    findAll()
 * @method Files[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocRepository extends ServiceEntityRepository
{
    public static $month = array('01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez');

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct($registry, Filesb::class);
    }

    /**
     * Transforma data Ex: '2019-10-01' em 'Out 2019'
     *
     * @param String $dateStr
     * @return string
     */
    public static function formatDateByName(string $dateStr): string
    {
        $tmp = explode('-', $dateStr);
        return self::$month[$tmp[1]] . ' ' . $tmp[0];
    }

    /**
     * Captura a parte da string após a última barra excluindo o caminho completo
     *
     * @param String $pathFile
     * @return string|null
     */
    public static function formatByLast(?string $pathFile): ?string
    {
        if (is_null($pathFile)) {
            return null;
        }
        preg_match("/(.*?)\/([^\/]*?)$/", $pathFile, $piece); // Pega o conteúdo após a última barra
        return $piece[2];
    }

    /**
     * Formata dados recuperados de 'getFilesCollection::method'
     *
     * @param $ret
     * @return array
     */
    private function hydrateFilesCollection($ret): array
    {
        foreach ($ret as $key => $val) {
            $ret[$key]['type'] = ($val['type'] === 'declaracao') ? 'Declaração' : 'Imposto';
            $ret[$key]['name'] = strtoupper($val['name']);
            $ret[$key]['file_name_guide'] = self::formatByLast($val['file_name_guide']);
            $ret[$key]['file_name_receipt'] = self::formatByLast($val['file_name_receipt']);
            $ret[$key]['date_period'] = self::formatDateByName($val['date_period']);
        }
        return $ret;
    }

    /**
     * Pega coleção da tabela 'docs'
     *
     * @param $companyId
     * @return array
     */
    public function getFilesCollection($companyId, $context): array
    {

        $sql = 'select at.id, at.type, at.name, p.name as company,  date_period, file_name_guide, file_name_receipt,
        at.status_id,s.status,s.real_status,s.color
        from docs as at 
        INNER JOIN status s ON s.id = at.status_id
        LEFT JOIN people p ON at.people_id = p.id
        where at.company_id = :companyId AND s.context = :context
        ';



        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('type', 'type', 'string');
        $rsm->addScalarResult('name', 'name', 'string');
        $rsm->addScalarResult('company', 'company', 'string');
        $rsm->addScalarResult('date_period', 'date_period', 'string');
        $rsm->addScalarResult('status_id', 'status_id', 'integer');
        $rsm->addScalarResult('status', 'status', 'string');
        $rsm->addScalarResult('real_status', 'real_status', 'string');
        $rsm->addScalarResult('color', 'color', 'string');
        $rsm->addScalarResult('file_name_guide', 'file_name_guide', 'string');
        $rsm->addScalarResult('file_name_receipt', 'file_name_receipt', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $nqu->setParameter('companyId', $companyId);
        $nqu->setParameter('context', $context);

        return $this->hydrateFilesCollection($nqu->getResult());
    }


    /**
     * Pega o 'document'(CNPJ) e 'name' nas tabelas 'people' e 'document' com base no ID
     *
     * @param string $id
     * @return array
     */
    public function getValidPeopleNameAndDocumentByID(string $id): array
    {
        $sql = "select distinct p.name,
        IF(CHAR_LENGTH(doc.document) = 14, doc.document, CONCAT('0', doc.document)) AS document
        from people as p LEFT JOIN document doc
        ON doc.id = (SELECT id FROM document WHERE document_type_id = 3 AND people_id = p.id LIMIT 1)
        where p.enable='1' and p.people_type='J' and p.id = '$id'
        limit 1;
        ";
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('name', 'name', 'string');
        $rsm->addScalarResult('document', 'document', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $ret = $nqu->getArrayResult();
        if (isset($ret[0]['document']) && strlen($ret[0]['document']) > 5) {
            $ret[0]['document'] = Formatter::document($ret[0]['document']);
        }
        return $ret;
    }

    /**
     * Pega o 'id' e 'name' na tabela 'people' com base no CNPJ 'document'
     *
     * @param string $document
     * @return array
     */
    public function getPeopleByDocumentTypeCNPJ(string $document): array
    {
        $sql = "select distinct p.id, p.name
        from people as p LEFT JOIN document doc
        ON doc.id = (SELECT id FROM document WHERE document_type_id = 3 AND people_id = p.id LIMIT 1)
        where p.enable='1' and p.people_type='J' and doc.document = '$document'
        limit 1;
        ";
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('name', 'name', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        return $nqu->getArrayResult();
    }

    // /**
    //  * @return Files[] Returns an array of Files objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Files
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
