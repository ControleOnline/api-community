<?php

namespace App\Library\Company;

use App\Library\Company\Entity\Company;
use App\Library\Company\Exception\ProviderRequestException;
use App\Library\Company\ReceitaWS\ReceitaWSProvider;

class CompanyProviderBalancer
{
    /**
     * Execution order. Must change only if you
     * want to change the priority
     */
    private $providers = [
        'receitaws'     => ReceitaWSProvider::class,
    ];

    private $currentProvider = null;

    public function search(string $postalCode): Company
    {
        try {

            if ($this->currentProvider === null) {
                $this->currentProvider = current($this->providers);
                $this->currentProvider = new $this->currentProvider;
            }

            return $this->currentProvider->getCnpj($postalCode);
        } catch (\Exception $e) {
            if ($e instanceof ProviderRequestException) {
                $this->setNextProvider();

                return $this->search($postalCode);
            }
        }
    }

    public function getProviderCodeName(): string
    {
        return key($this->providers);
    }

    private function setNextProvider(): void
    {
        $nextProvider = next($this->providers);

        if ($nextProvider === false) {
            throw new \Exception('Company services are not available');
        }

        $this->currentProvider = new $nextProvider;
    }
}
