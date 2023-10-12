<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
/**
 * Import
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="imports")
 * @ORM\Entity (repositoryClass="App\Repository\ImportRepository")
 */
#[ApiResource(operations: [new Delete(controller: \App\Controller\DeleteImportAction::class, uriTemplate: 'import/{id}', security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: 'imports', controller: \App\Controller\GetImportsAction::class, openapiContext: []), new Post(uriTemplate: 'import', controller: \App\Controller\ImportAction::class, security: 'is_granted(\'ROLE_CLIENT\')', deserialize: false, openapiContext: ['consumes' => ['multipart/form-data']])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class Import
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string",  nullable=false)
     */
    private $status;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string",  nullable=false)
     */
    private $name;
    /**
     * @var integer
     *
     * @ORM\Column(name="file_id", type="integer",  nullable=false)
     */
    private $fileId;
    /**
     * @var integer
     *
     * @ORM\Column(name="file_format", type="string",  nullable=false)
     */
    private $fileFormat;
    /**
     * @var integer
     *
     * @ORM\Column(name="people_id", type="integer",  nullable=false)
     */
    private $peopleId;
    /**
     * @var string
     *
     * @ORM\Column(name="feedback", type="string",  nullable=true)
     */
    private $feedback;
    /**
     * @var string
     *
     * @ORM\Column(name="import_type", type="string",  nullable=true)
     */
    private $importType;
    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="upload_date", type="datetime",  nullable=false, columnDefinition="DATETIME")
     */
    private $uploadDate;
    public function __construct()
    {
        $this->status = 'waiting';
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * Set status
     *
     * @param string $status
     * @return self
     */
    public function setStatus($status) : self
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Get status
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }
    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name) : self
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get name
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * Set fileId
     *
     * @param integer $file_id
     * @return self
     */
    public function setFileId($file_id) : self
    {
        $this->fileId = $file_id;
        return $this;
    }
    /**
     * Get fileId
     *
     * @return integer
     */
    public function getFileId() : int
    {
        return $this->fileId;
    }
    /**
     * Set fileFormat
     *
     * @param integer $file_format
     * @return self
     */
    public function setFileFormat($file_format) : self
    {
        $this->fileFormat = $file_format;
        return $this;
    }
    /**
     * Get fileFormat
     *
     * @return integer
     */
    public function getFileFormat() : string
    {
        return $this->fileFormat;
    }
    /**
     * Set peopleId
     *
     * @param integer $people_id
     * @return self
     */
    public function setPeopleId($people_id) : self
    {
        $this->peopleId = $people_id;
        return $this;
    }
    /**
     * Get peopleId
     *
     * @return integer
     */
    public function getPeopleId() : int
    {
        return $this->peopleId;
    }
    /**
     * Set importType
     *
     * @param string $importType
     * @return self
     */
    public function setImportType($importType) : self
    {
        $this->importType = $importType;
        return $this;
    }
    /**
     * Get importType
     *
     * @return string
     */
    public function getImportType() : string
    {
        return $this->importType;
    }
    /**
     * Set feedback
     *
     * @param string $feedback
     * @return self
     */
    public function setFeedback($feedback) : self
    {
        $this->feedback = $feedback;
        return $this;
    }
    /**
     * Get feedback
     *
     * @return string
     */
    public function getFeedback() : int
    {
        return $this->feedback;
    }
    /**
     * Get uploadDate
     *
     * @return \DateTimeInterface
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }
}
// create table imports (
//     id int primary key auto_increment,
//     file_name varchar(255) not null,
//     status varchar(255) not null,
//     name varchar(255) not null,
//     file_id int not null,
//     people_id int not null,
//     file_format int not null DEFAULT 0,
//     feedback LONGTEXT,
//     upload_date datetime not null DEFAULT CURRENT_TIMESTAMP,
//     foreign key (file_id) references file (id),
//     foreign key (people_id) references people (id)
// )