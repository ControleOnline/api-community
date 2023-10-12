<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Library\Utils\ViaCEP;
use App\Entity\GeoPlace;

class ViaCEPService
{

  public function __construct(RequestStack $request)
  {
    $this->rq = $request->getCurrentRequest();
  }

  public function search(string $cep): ?GeoPlace
  {
    if (!$this->isCEP($cep))
      return null;

    $result = ViaCEP::search($cep);
    if ($result === null)
      return null;

    // Av. Paulista - Bela Vista, São Paulo - SP, 01310-100, Brasil

    $geoplace              = new GeoPlace();
    $geoplace->id          = $result->ibge;
    $geoplace->description = sprintf('%s - %s, %s, %s, Brasil', $result->logradouro, $result->localidade, $result->cep, $result->uf);
    $geoplace->country     = 'Brasil';
    $geoplace->state       = $result->uf;
    $geoplace->city        = $result->localidade;
    $geoplace->district    = $result->bairro;
    $geoplace->street      = $result->logradouro;
    $geoplace->number      = '';
    $geoplace->postal_code = $cep;

    return $geoplace;
  }

  public function isCEP(string $input): bool
  {
    return preg_match('/^\d{8}$/', $input) === 1;
  }
}
