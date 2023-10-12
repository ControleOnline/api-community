<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetProviderDataPerInvoiceId extends AbstractController
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    /**
     * @param $invoiceId
     * @return array
     * @throws Exception
     */
    public function repoSql($invoiceId): ?array
    {
        $ret = array();
        // ---- Pega o campo "config.config_value" pelo "order_invoice.invoice_id"
        // ---- Retorno atualmente é 'itau' ou 'inter' dependendo da empresa ('provider_id') que está ligada ao invoiceId
        $sql = "select distinct cf.config_value, o.payer_people_id, o.provider_id
        from order_invoice as oi
                 left join orders o on o.id = oi.order_id
                 left join config as cf on cf.people_id = o.provider_id
        where cf.config_key = 'payment_type'
          and oi.invoice_id = '$invoiceId'
        limit 1
        ";
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('config_value', 'config_value', 'string');
        $rsm->addScalarResult('payer_people_id', 'payer_people_id', 'string');
        $rsm->addScalarResult('provider_id', 'provider_id', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $result = $nqu->getResult();
        if (count($result) > 0) {
            $ret['config_value'] = $result[0]['config_value'];
            $ret['payer_people_id'] = $result[0]['payer_people_id'];
            $ret['provider_id'] = $result[0]['provider_id'];
        }
        return $ret;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $invoiceId = $request->get('id', null);
            $result = $this->repoSql($invoiceId);
            if (empty($result)) {
                throw new Exception("O beneficiário não possui uma 'config_key' com um 'payment_type' definido como 'inter' ou 'itau'");
            }
            $ret['response']['data']['invoice_id'] = $invoiceId;
            $ret['response']['data']['provider_id'] = $result['provider_id'];
            $ret['response']['data']['payer_people_id'] = $result['payer_people_id'];
            $ret['response']['data']['config_value'] = $result['config_value'];
            $ret['response']['data']['total'] = 1;
            $ret['response']['data']['success'] = true;
        } catch (Exception $e) {
            $ret['response']['data'] = [];
            $ret['response']['success'] = false;
            $ret['response']['message'] = $e->getMessage();
        }
        return new JsonResponse($ret, 200);
    }

}
