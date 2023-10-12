<?php

namespace App\Handler;

use App\Entity\Email;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;

use App\Entity\PasswordRecovery;
use App\Entity\People;
use App\Entity\PeopleDomain;
use ControleOnline\Entity\User;
use App\Repository\ConfigRepository;
use App\Library\Utils\Hasher;

class PasswordRecoveryHandler implements MessageHandlerInterface
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  /**
   * Config repository
   *
   * @var \App\Repository\ConfigRepository
   */
  private $config;

  /**
   * Twig render
   *
   * @var \Twig\Environment
   */
  private $twig;

  public function __construct(EntityManagerInterface $manager, ConfigRepository $config, Environment $twig)
  {
    $this->manager = $manager;
    $this->config  = $config;
    $this->twig    = $twig;
  }

  public function __invoke(PasswordRecovery $recovery)
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      $this->recoveryPassword($recovery);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => null,
          'count'   => 1,
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      $this->manager->getConnection()->rollBack();

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getCode() >= 100 && $e->getCode() <= 103 ? $e->getMessage() : 'Não foi possivel recuperar sua senha. Tente novamente',
          'success' => false,
        ],
      ]);
    }
  }

  private function recoveryPassword(PasswordRecovery $recovery)
  {
    /**
     * @var \App\Entity\Email
     */
    $email = $this->manager->getRepository(Email::class)->findOneBy(['email' => $recovery->email]);

    if ($email === null)
      throw new \Exception('O email informado não está cadastrado', 100);

    /**
     * @var \ControleOnline\Entity\User
     */
    $user  = $this->manager->getRepository(User::class)
      ->findOneBy([
        'username' => $recovery->username,
        'people'   => $email->getPeople()
      ]);

    if ($user === null)
      throw new \Exception(
        sprintf('Nome de usuário "%s" não foi encontrado', $recovery->username), 101
      );

    // save lost password

    /*
    $password = $this->getHash(md5($recovery->username . microtime()));

    $user->setLostPassword($this->getHash($password));
    */

    $password = sprintf('%s%s', Hasher::getRandomString(28), md5(microtime()));

    $user->setLostPassword($password);

    $this->manager->persist($user);

    // send email

    $this->sendMail(
      $email->getEmail(), ['hash' => $password, 'lost_password' => $user->getLostPassword()]);
  }

  private function sendMail(string $emailTo, array $params): void
  {
    $formID  = 18;
    $company = $this->getCompany($this->getDomain());
    if ($company === null)
      throw new \Exception('Company domain not found', 102);

    $config  = $this->config->getMauticConfigByPeople($company);
    if ($config === null)
      throw new \Exception('Company config not found', 103);

    $params['mauticform[formId]'] = $formID;
    $params['mauticform[f_key]']  = $config['mautic-o-auth2-public-key'];
    $params['mauticform[body]']   = $this->twig->render('email/lost-password.html.twig', $params);
    $params['mauticform[email]']  = $emailTo;

    (new \GuzzleHttp\Client())
      ->post($config['mautic-url'] . '/form/submit?formId=' . $formID, array('form_params' => $params));
  }

  private function getHash(string $string): string
  {
    return crypt($string, null);
  }

  private function getCompany(string $domain): ?People
  {
    $company = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      return null;

    return $company->getPeople();
  }

  private function getDomain($domain = null): string
  {
    return $domain ?: $_SERVER['HTTP_HOST'];
  }  
}
