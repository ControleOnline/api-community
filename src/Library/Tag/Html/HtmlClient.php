<?php

namespace App\Library\Tag\Html;

use App\Entity\SalesOrder;
use App\Library\Tag\AbstractTag;
use Proner\PhpPimaco\Pimaco;
use Proner\PhpPimaco\Tag;

class HtmlClient extends AbstractTag
{
    public function getPdf(SalesOrder $orderData)
    {
        return $this->getPdfTagData($orderData);
    }

    protected function getPdfTagData(SalesOrder $orderData)
    {
        $params = $this->_getOrdersTemplateParams($orderData);
        $twigFile = 'tag/A4Tag.html.twig';
        $html = $this->twig->render($twigFile, $params);
        return $html;
    }
}
