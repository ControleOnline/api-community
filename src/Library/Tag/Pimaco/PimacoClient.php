<?php

namespace App\Library\Tag\Pimaco;

use App\Entity\SalesOrder;
use App\Library\Tag\AbstractTag;
use Proner\PhpPimaco\Pimaco;
use Proner\PhpPimaco\Tag;

class PimacoClient extends AbstractTag
{
    public function getPdf(SalesOrder $orderData)
    {
        return $this->getPdfTagData($orderData);
    }

    protected function getPdfTagData(SalesOrder $orderData)
    {

        $people = $orderData->getProvider();

        $logo = 'https://api.controleonline.com/files/2/image.png';//$this->getPeopleFilePath($people);


        $pimaco = new Pimaco('A4361');

        $tag = new Tag(); 
        $tag->setSize(100);       
        $tag->setPadding(0);
        $tag->img($logo)->setHeight(40)->setAlign('right');
        $tag->setBorder(0.1);
        $tag->barcode('0001', 'TYPE_CODE_128')->setWidth(2.2)->setMargin(array(0, 2, 1, 0))->br();
        $tag->p('0001 - Produto de Teste 1')->setSize(3)->br();
        $tag->p('R$: 9,90')->b()->setSize(5);
        $pimaco->addTag($tag);

        $tag = new Tag();        
        $tag->setSize(100);
        $tag->setPadding(0);
        $tag->img($logo)->setHeight(40)->setAlign('right');
        $tag->setBorder(0.1);
        $tag->barcode('0003', 'TYPE_CODE_128')->setWidth(2.2)->setMargin(array(0, 2, 1, 0))->br();
        $tag->p('0003 - Produto de Teste 3')->setSize(3)->br();
        $tag->p('R$: 29,90')->b()->setSize(5);
        $pimaco->addTag($tag);

        return $pimaco->render();
    }
}
