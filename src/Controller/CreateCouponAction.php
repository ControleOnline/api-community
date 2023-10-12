<?php

namespace App\Controller;


use App\Entity\People;
use App\Entity\DiscountCoupon;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Container\ContainerInterface;

use App\Service\PeopleService;
use App\Service\UserCompanyService;

class CreateCouponAction
{

  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  private $currentRequest = null;


  private $payload        = null;



  private $security       = null;

  private $container      = null;

  private $people         = null;

  private $company        = null;

  public function __construct(
    EntityManagerInterface $manager,
    RequestStack           $request,
    Security               $security,
    ContainerInterface     $container,
    PeopleService          $peopleService,
    UserCompanyService     $company
  ) {
    $this->manager  = $manager;
    $this->currentRequest = $request->getCurrentRequest();
    $this->security       = $security;
    $this->container      = $container;
    $this->people         = $peopleService;
    $this->company        = $company;
  }

  public function index(): ?array
  {
    try {

      $this->manager->getConnection()->beginTransaction();

      $discountEndDate  = $this->payload->discountEndDate ? \DateTime::createFromFormat('Y-m-d', $this->payload->discountEndDate) : null;
      $discountStartDate  = $this->payload->discountStartDate ? \DateTime::createFromFormat('Y-m-d', $this->payload->discountStartDate) : null;

      $type  = $this->payload->type;
      $amount  = $this->payload->amount ?: 0;
      $company  = $this->manager->getRepository(People::class)->find($this->payload->company);
      $client  = $this->payload->client ? $this->manager->getRepository(People::class)->find($this->payload->client) : null;

      if ($company === null) {
        throw new \Exception('Company was not found');
      }
      if ($amount <= 0) {
        throw new \Exception('Amount is invalid');
      }
      if (!$type) {
        throw new \Exception('Type is invalid');
      }

      $discountCoupon = new DiscountCoupon();
      $discountCoupon->setCode($this->generateCode());
      $discountCoupon->setClient($client);
      $discountCoupon->setCompany($company);
      $discountCoupon->setDiscountStartDate($discountStartDate);
      $discountCoupon->setDiscountEndDate($discountEndDate);
      $discountCoupon->setDiscountDate(\DateTime::createFromFormat('Y-m-d', date('Y-m-d')));
      $discountCoupon->setType($type);
      $discountCoupon->setValue($amount);
      $discountCoupon->setCreator($this->security->getUser()->getPeople());


      $this->manager->persist($discountCoupon);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return [
        'id' => $discountCoupon->getId(),
      ];
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }

      throw new \Exception($e->getMessage());
    }
  }

  public function generateCode($lenght = 10)
  {
    // uniqid gives 10 chars, but you could adjust it to your needs.
    if (function_exists("random_bytes")) {
      $bytes = random_bytes(ceil($lenght / 2));
    } elseif (function_exists("openssl_random_pseudo_bytes")) {
      $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
    } else {
      throw new \Exception("no cryptographically secure random function available");
    }
    return strtoupper(substr(bin2hex($bytes), 0, $lenght));
  }



  public function __invoke(Request $request): JsonResponse
  {

    $this->payload = json_decode($request->getContent());

    try {
      $output   = $this->index();

      if ($output === null) {
        return $this->response([]);
      }

      return $this->response([
        'response' => [
          'data'    => $output,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }

      return $this->response([
        'response' => [
          'data'    => null,
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ], 500);
    }
  }





  protected function response(array $output, int $code = 200): JsonResponse
  {
    return new JsonResponse($output, $code);
  }
}
