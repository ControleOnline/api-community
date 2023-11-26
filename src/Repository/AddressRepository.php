<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findOneByCityStateCountryOfPeople(string $city, string $state, string $country, People $people): ?Address
    {
      $result = $this->createQueryBuilder('a')
                  ->innerJoin         ('a.street'  , 's')
                  ->innerJoin         ('s.district', 'd')
                  ->innerJoin         ('d.city'    , 'c')
                  ->innerJoin         ('c.state'   , 'e')
                  ->innerJoin         ('e.country' , 'u')

                  ->andWhere          ('c.city        = :city'   )
                  ->andWhere          ('e.uf          = :state'  )
                  ->andWhere          ('u.countryname = :country')
                  ->andWhere          ('a.people      = :people' )

                  ->setParameter      ('city'   , $city   )
                  ->setParameter      ('state'  , $state  )
                  ->setParameter      ('country', $country)
                  ->setParameter      ('people' , $people)

                  ->getQuery()
                  ->getResult()
              ;

      if (empty($result))
        return null;

      return $result[0];
    }

    public function findPeopleAddressBy(People $people, array $criteria): ?Address
    {
      $result = $this->createQueryBuilder('a')
                  ->innerJoin         ('a.street'  , 's')
                  ->innerJoin         ('s.district', 'd')
                  ->innerJoin         ('d.city'    , 'c')
                  ->innerJoin         ('c.state'   , 'e')
                  ->innerJoin         ('e.country' , 'u')

                  ->andWhere          ('a.people      = :people'  )
                  ->andWhere          ('u.countryname = :country' )
                  ->andWhere          ('(e.uf = :state OR e.state = :state)')
                  ->andWhere          ('c.city        = :city'    )
                  ->andWhere          ('d.district    = :district')
                  ->andWhere          ('s.street      = :street'  )
                  ->andWhere          ('a.number      = :number'  )

                  ->setParameter      ('people'  , $people)
                  ->setParameter      ('country' , $criteria['country'] )
                  ->setParameter      ('state'   , $criteria['state']   )
                  ->setParameter      ('city'    , $criteria['city']    )
                  ->setParameter      ('district', $criteria['district'])
                  ->setParameter      ('street'  , $criteria['street']  )
                  ->setParameter      ('number'  , $criteria['number']  )

                  ->getQuery()
                  ->getResult()
              ;

      if (empty($result))
        return null;

      return $result[0];
    }
}
