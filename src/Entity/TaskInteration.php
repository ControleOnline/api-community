<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use stdClass;
/**
 * TaskInteration
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity ()
 * @ORM\Table (name="task_interations")
 */
#[ApiResource(operations: [new Put(security: 'is_granted(\'ROLE_CLIENT\')', denormalizationContext: ['groups' => ['task_interaction_write']]), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(uriTemplate: 'task_interations/task/{task_id}', controller: \App\Controller\CreateTaskInteractionAction::class, security: 'is_granted(\'ROLE_CLIENT\')', deserialize: false, openapiContext: ['consumes' => ['multipart/form-data']])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')', normalizationContext: ['groups' => ['task_interaction_read']], denormalizationContext: ['groups' => ['task_interaction_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['task' => 'exact', 'task.id' => 'exact', 'task.taskFor' => 'exact', 'registeredBy' => 'exact', 'type' => 'exact', 'visibility' => 'exact', 'read' => 'exact'])]
class TaskInteration
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"task_interaction_read"})
     */
    private $id;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"task_interaction_read"})
     */
    private $type;
    /**
     *
     * @ORM\Column(name="visibility",type="string", length=50, nullable=false)
     * @Groups({"task_interaction_read"})
     */
    private $visibility;
    /**
     *
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"task_interaction_read"})
     */
    private $body;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="registered_by_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"task_interaction_read"})
     */
    private $registeredBy;
    /**
     * @var \App\Entity\Task
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Task")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="task_id", referencedColumnName="id", nullable=true)
     * })
     * 
     * @Groups({"task_interaction_read"})
     */
    private $task;
    /**
     * @var \App\Entity\File
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=true)
     * })
     * @Groups({"task_interaction_read"})
     */
    private $file;
    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="created_at", type="datetime",  nullable=false, columnDefinition="DATETIME")
     * @Groups({"task_interaction_read"})
     */
    private $createdAt;
    /**
     * @ORM\Column(name="`read`", type="integer",  nullable=false,)
     * @Groups({"task_interaction_read","task_interaction_write"})
     */
    private $read;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->body = json_encode(new stdClass());
        $this->visibility = 'private';
    }
    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * Set the value of type
     */
    public function setType($type) : self
    {
        $this->type = $type;
        return $this;
    }
    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * Set body
     *
     * @param string $body
     * @return TaskInteration
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    /**
     * Get the value of registeredBy
     */
    public function getRegisteredBy()
    {
        return $this->registeredBy;
    }
    /**
     * Set the value of registeredBy
     */
    public function setRegisteredBy($registeredBy) : self
    {
        $this->registeredBy = $registeredBy;
        return $this;
    }
    /**
     * Get the value of task
     */
    public function getTask()
    {
        return $this->task;
    }
    /**
     * Set the value of task
     */
    public function setTask($task) : self
    {
        $this->task = $task;
        return $this;
    }
    /**
     * Get the value of file
     */
    public function getFile()
    {
        return $this->file;
    }
    /**
     * Set the value of file
     */
    public function setFile($file) : self
    {
        $this->file = $file;
        return $this;
    }
    /**
     * Get the value of createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    /**
     * Get the value of visibility
     */
    public function getVisibility()
    {
        return $this->visibility;
    }
    /**
     * Set the value of visibility
     */
    public function setVisibility($visibility) : self
    {
        $this->visibility = $visibility;
        return $this;
    }
    /**
     * Get the value of read
     */
    public function getRead()
    {
        return $this->read;
    }
    /**
     * Set the value of read
     */
    public function setRead($read)
    {
        $this->read = $read;
        return $this;
    }
}
