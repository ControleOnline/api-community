<?php


namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="contract_model")
 * @ORM\Entity (repositoryClass="App\Repository\MyContractModelRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['mycontractmodel_read']], denormalizationContext: ['groups' => ['mycontractmodel_write']])]
class MyContractModel
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\Column(name="contract_model", type="string", nullable=false)
     * @Groups({"mycontractmodel_read", "my_contract_item_read", "mycontract_put_read", "mycontract_addendum_read"})
     */
    private $contractModel;
    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     * @Groups({"my_contract_item_read", "mycontract_put_read", "mycontract_addendum_read"})
     */
    private $content;
    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * @return string
     */
    public function getContractModel() : string
    {
        return $this->contractModel;
    }
    /**
     * @param string $contractModel
     * @return MyContractModel
     */
    public function setContractModel(string $contractModel) : MyContractModel
    {
        $this->contractModel = $contractModel;
        return $this;
    }
    /**
     * @return string
     */
    public function getContent() : string
    {
        return $this->content;
    }
    /**
     * @param string $content
     * @return MyContractModel
     */
    public function setContent(string $content) : MyContractModel
    {
        $this->content = $content;
        return $this;
    }
}
