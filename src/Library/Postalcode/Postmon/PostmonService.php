<?php
namespace App\Library\Postalcode\Postmon;

use App\Library\Postalcode\Entity\Address;
use GuzzleHttp\Client;
use App\Library\Postalcode\Exception\InvalidParameterException;
use App\Library\Postalcode\Exception\ProviderRequestException;
use App\Library\Postalcode\PostalcodeService;

class PostmonService implements PostalcodeService
{
  private $endpoint = 'https://api.postmon.com.br/v1/cep';

  public function __construct()
  {

  }

  public function query(string $postalCode): Address
  {
    if (!$this->isCEP($postalCode)) {
      throw new InvalidParameterException('CEP string is not valid. Acceptable format: 16058741');
    }

    $result = $this->search($postalCode);

    return (new Address)
      ->setCountry   ('Brasil')
      ->setState     (isset($result->estado_info) ? $result->estado_info->nome : '')
      ->setUF        (isset($result->estado) ? $result->estado : '')
      ->setCity      (isset($result->cidade) ? $result->cidade : '')
      ->setDistrict  (isset($result->bairro) ? $result->bairro : '')
      ->setStreet    (isset($result->logradouro) ? $result->logradouro : '')
      ->setNumber    ('')
      ->setPostalCode(isset($result->cep) ? $result->cep : '')
      ->setComplement(isset($result->complemento) ? $result->complemento : '')
    ;
  }

  private function search(string $cep, string $format = 'json'): object
  {
    try {
      $client   = new Client(['verify' => false]);
      $response = $client->request('GET',sprintf('%s/%s?format=%s', $this->endpoint, $cep, $format));
      $result   = json_decode($response->getBody());

      if (isset($result->cep)) {
        return $result;
      }

      throw new ProviderRequestException('Postmon response format error');
    } catch (\Exception $e) {
      throw new ProviderRequestException($e->getMessage());
    }
  }

  private function isCEP(string $input): bool
  {
    return preg_match('/^\d{8}$/', $input) === 1;
  }
}
