<?php

namespace App\Library\Tag\Html;

use ControleOnline\Entity\Order;
use App\Library\Tag\AbstractTag;
use Proner\PhpPimaco\Pimaco;
use Proner\PhpPimaco\Tag;

class HtmlClient extends AbstractTag
{
    public function getPdf(Order $orderData)
    {
        return $this->getPdfTagData($orderData);
    }

    protected function getPdfTagData(Order $orderData)
    {
        $params = $this->_getOrdersTemplateParams($orderData);
        $twigFile = 'tag/A4Tag.html.twig';
        $html = $this->twig->render($twigFile, $params);
        return $html;
    }
}
