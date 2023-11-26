<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\AdminPersonCompaniesAction;
use App\Controller\AdminPersonFilesAction;
use App\Controller\AdminPersonUsersAction;
use App\Controller\CreateContactAction;
use App\Controller\CreatePeopleCustomerAction;
use App\Controller\CreateProfessionalAction;
use App\Controller\DownloadPersonFileAction;
use App\Controller\GetClientCompanyAction;
use App\Controller\GetCloseProfessionalsAction;
use App\Controller\GetCustomerCollectionAction;
use App\Controller\GetDefaultCompanyAction;
use App\Controller\GetMyCompaniesAction;
use App\Controller\GetMySaleCompaniesAction;
use App\Controller\GetPeopleMeAction;
use App\Controller\GetProfessionalCollectionAction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\SearchClassesPeopleAction;
use App\Controller\SearchContactAction;
use App\Controller\SearchContactCompanyAction;
use App\Controller\SearchCustomerSalesmanAction;
use App\Controller\SearchLessonsPeopleAction;
use App\Controller\SearchPeopleAction;
use App\Controller\SearchTasksPeopleAction;
use App\Controller\UpdatePeopleProfileAction;
use App\Controller\VerifyPeopleStatusAction;
use stdClass;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\PeopleRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [

        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/contact',
            controller: \App\Controller\SearchContactAction::class
        ),

        new Put(
            security: 'is_granted(\'edit\', object)',
            uriTemplate: '/people/{id}/profile/{component}',
            requirements: ['component' => '^(phone|address|email|user|document|employee)+$'],
            controller: \App\Controller\UpdatePeopleProfileAction::class
        ),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/status',
            controller: \App\Controller\VerifyPeopleStatusAction::class
        ),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/classes',
            controller: \App\Controller\SearchClassesPeopleAction::class
        ),

        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/{id}/lessons',
            controller: \App\Controller\SearchLessonsPeopleAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/change-status',
            controller: \App\Controller\ChangeStatusAction::class,
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)'
        ),
        new Get(
            uriTemplate: '/customers/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/customers/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonUsersAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonUsersAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonUsersAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminCustomerSalesmanAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminCustomerSalesmanAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/salesman',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminCustomerSalesmanAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/files/{fileId}',
            requirements: ['id' => '^\\d+$', 'fileId' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\DownloadPersonFileAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/customers/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonCompaniesAction::class
        ),
        new Put(
            uriTemplate: '/customers/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonCompaniesAction::class
        ),
        new Delete(
            uriTemplate: '/customers/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonCompaniesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/professionals/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Delete(
            uriTemplate: '/professionals/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Get(
            uriTemplate: '/professionals/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ),
        new Put(
            uriTemplate: '/professionals/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/files/{fileId}',
            requirements: ['id' => '^\\d+$', 'fileId' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\DownloadPersonFileAction::class
        ),

        new Delete(
            uriTemplate: '/professionals/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonUsersAction::class
        ),

        new Put(
            uriTemplate: '/professionals/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonUsersAction::class
        ),

        new Delete(
            uriTemplate: '/professionals/{id}/users',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonUsersAction::class
        ),

        new Get(
            uriTemplate: '/professionals/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonCompaniesAction::class
        ),

        new Put(
            uriTemplate: '/professionals/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonCompaniesAction::class
        ),

        new Delete(
            uriTemplate: '/professionals/{id}/companies',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonCompaniesAction::class
        ),

        new GetCollection(
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people'
        ),

        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/people/company/default',
            controller: \App\Controller\GetDefaultCompanyAction::class
        ),

        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/customer',
            controller: \App\Controller\CreatePeopleCustomerAction::class
        ),

        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/customers',
            controller: \App\Controller\GetCustomerCollectionAction::class
        ),

        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/customers/search-salesman',
            controller: \App\Controller\SearchCustomerSalesmanAction::class
        ),
        new Post(
            uriTemplate: '/customers/files',
            security: 'is_granted(\'ROLE_CLIENT\')',
            controller: \App\Controller\UploadPersonFilesAction::class,
            deserialize: false
        ),
        new Post(
            uriTemplate: '/people/contact',
            controller: \App\Controller\CreateContactAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/companies/my',
            controller: \App\Controller\GetMyCompaniesAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/my-sale-companies',
            controller: \App\Controller\GetMySaleCompaniesAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people-search',
            controller: \App\Controller\SearchPeopleAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/contact',
            controller: \App\Controller\SearchContactCompanyAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/client-company',
            controller: \App\Controller\GetClientCompanyAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/people/me',
            controller: \App\Controller\GetPeopleMeAction::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/tasks/people',
            controller: \App\Controller\SearchTasksPeopleAction::class
        ),
        new GetCollection(
            uriTemplate: '/people/professionals/close/{lat}/{lng}',
            openapiContext: [],
            controller: \App\Controller\GetCloseProfessionalsAction::class
        ),
        new Post(
            uriTemplate: '/professionals',
            controller: \App\Controller\CreateProfessionalAction::class,
            securityPostDenormalize: 'is_granted(\'create\', object)'
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/professionals',
            controller: \App\Controller\GetProfessionalCollectionAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    security: 'is_granted(\'ROLE_CLIENT\')',
    normalizationContext: ['groups' => ['people_read']],
    denormalizationContext: ['groups' => ['people_write']]
)]
class People extends Person
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"pruduct_read","school_class:item:get","hardware_read", "people:people_company:subresource", "people_student:collection:get",
     *     "people_professional:collection:get", "task_read", "task_interaction_read","coupon_read","logistic_read","notifications_read","people_provider_read"})
     */
    private $id;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;
    /**
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $icms = 1;
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({
     *     "order_read", "document_read", "email_read", "people_read",
     *     "invoice_read", "client_read", "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", "school_class:item:get",
     *     "people:people_company:subresource", "people_student:collection:get",
     *     "people_professional:collection:get", "school_professional_weekly_read", "school_team_schedule_read",
     *     "school_team_schedule_read", "task_read", "task_interaction_read","coupon_read", "logistic_read",
     *     "queue_read","hardware_read","notifications_read","people_provider_read"
     * })
     */
    private $name;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $registerDate;
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({
     *     "order_read", "document_read", "email_read", "people_read", "invoice_read",
     *     "client_read", "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", "people:people_company:subresource",
     *     "school_professional_weekly_read", "school_team_schedule_read", "school_team_schedule_read",
     *     "people_professional:collection:get", "task_read", "task_interaction_read","coupon_read","logistic_read",
     *     "pruduct_read","queue_read","hardware_read","notifications_read","people_provider_read"
     * })
     */
    private $alias;
    /**
     * @var string
     *
     * @ORM\Column(name="other_informations", type="json",  nullable=true)
     * @Groups({
     *     "order_read", "document_read", "email_read", "people_read", "invoice_read",
     *     "client_read", "order_detail_status_read", "mycontract_read",
     *     "my_contract_item_read", "mycontractpeople_read", "people:people_company:subresource",
     *     "school_professional_weekly_read", "school_team_schedule_read", "school_team_schedule_read",
     *     "people_professional:collection:get", "task_read", "task_interaction_read","coupon_read"
     * }) 
     */
    private $otherInformations;
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Groups({"pruduct_read","hardware_read","people_read", "my_contract_item_read", "mycontractpeople_read", "task_read", "task_interaction_read"})
     */
    private $peopleType = 'F';
    /**
     * @ORM\Column(type="float", nullable=false)
     * @Groups({"people_read"})
     */
    private $billing = 0;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\File", inversedBy="people")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * })
     * @Groups({"people_read","hardware_read"})
     */
    private $file;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Config", mappedBy="people")
     * @ORM\OrderBy({"config_key" = "ASC"})
     */
    private $config;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="alternative_image", referencedColumnName="id")
     * })
     */
    private $alternativeFile;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\File")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="background_image", referencedColumnName="id")
     * })
     */
    private $backgroundFile;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language", inversedBy="people")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="language_id", referencedColumnName="id")
     * })
     */
    private $language;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleEmployee", mappedBy="company")
     * @ORM\OrderBy({"employee" = "ASC"})
     */
    private $peopleEmployee;
    /**
     * @var \Collection
     * @ORM\OneToMany (targetEntity="App\Entity\PeopleEmployee", mappedBy="employee")
     * @ORM\OrderBy ({"company" = "ASC"})
     */
    private $peopleCompany;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleSalesman", mappedBy="company")
     * @ORM\OrderBy({"salesman" = "ASC"})
     */
    private $peopleSalesman;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleFranchisee", mappedBy="franchisee")
     * @ORM\OrderBy({"franchisee" = "ASC"})
     */
    private $peopleFranchisee;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleFranchisee", mappedBy="franchisor")
     * @ORM\OrderBy({"franchisor" = "ASC"})
     */
    private $peopleFranchisor;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\User", mappedBy="people")
     * @ORM\OrderBy({"username" = "ASC"})
     * @Groups({"people_read"})
     */
    private $user;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Document", mappedBy="people")
     * @Groups({"people_read", "task_interaction_read"})
     */
    private $document;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Address", mappedBy="people")
     * @ORM\OrderBy({"nickname" = "ASC"})
     * @Groups({"people_read", "logistic_read"})
     */
    private $address;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Phone", mappedBy="people")
     * @Groups({"people_read", "client_read",  "task_interaction_read"})
     */
    private $phone;
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Email", mappedBy="people")
     * @Groups({"people_read", "get_contracts", "client_read", "task_interaction_read"})
     */
    private $email;
    /**
     * Many Peoples have Many Contracts.
     *
     * @ORM\OneToMany (targetEntity="App\Entity\ContractPeople", mappedBy="people")
     */
    private $contractsPeople;
    /**
     * Many Peoples have Many Teams.
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Team")
     * @ORM\JoinTable(name="people_team",
     *     joinColumns={@ORM\JoinColumn(name="people_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="team_id", referencedColumnName="id")}
     * )
     */
    private $teams;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"people_read"})
     */
    private $billingDays;
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Groups({"people_read", "my_contract_item_read", "mycontractpeople_read"})
     */
    private $paymentTerm;
    /**
     * @ORM\OneToOne(targetEntity=PeopleStudent::class, mappedBy="student", cascade={"persist", "remove"})
     */
    private $peopleStudent;
    /**
     * @ORM\OneToMany(targetEntity=PeopleStudent::class, mappedBy="company")
     */
    private $peopleStudents;
    /**
     * @ORM\OneToOne(targetEntity=PeopleProfessional::class, mappedBy="professional", cascade={"persist", "remove"})
     */
    private $peopleProfessional;
    /**
     * @ORM\OneToMany(targetEntity=PeopleTeam::class, mappedBy="people")
     */
    private $peopleTeams;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     * @Groups({"people_read", "my_contract_item_read", "mycontractpeople_read"})
     */
    private $foundationDate = null;
    /**
     * @Groups({"people_read", "my_contract_item_read", "mycontractpeople_read"})
     */
    private $averageRating = 4;
    public function __construct()
    {
        $this->enable = 0;
        $this->icms = 1;
        $this->billing = 0;
        $this->registerDate =
            new \DateTime('now');
        $this->peopleEmployee =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->config =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->peopleCompany =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->peopleSalesman =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->peopleFranchisee =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->peopleFranchisor =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->user =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->document =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->address =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->email =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->phone =
            new \Doctrine\Common\Collections\ArrayCollection();
        $this->billingDays = 'daily';
        $this->paymentTerm = 1;
        $this->peopleStudents =
            new ArrayCollection();
        $this->peopleTeams =
            new ArrayCollection();
        $this->otherInformations = json_encode(
            new stdClass()
        );
    }
    public function getId()
    {
        return $this->id;
    }
    public function getAverageRating()
    {
        return $this->averageRating;
    }
    public function setAverageRating($averageRating)
    {
        $this->averageRating = $averageRating;
        return $this;
    }
    public function getIcms()
    {
        return $this->icms;
    }
    public function setIcms($icms)
    {
        $this->icms = $icms ?: 0;
        return $this;
    }
    public function getEnabled()
    {
        return $this->enable;
    }
    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;
        return $this;
    }
    public function setPeopleType($people_type)
    {
        $this->peopleType = $people_type;
        return $this;
    }
    public function getPeopleType()
    {
        return $this->peopleType;
    }
    /**
     * Set name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get name.
     */
    public function getName(): string
    {
        return strtoupper($this->name);
    }
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    public function getAlias()
    {
        return strtoupper($this->alias);
    }
    public function setFile(File $file = null)
    {
        $this->file = $file;
        return $this;
    }
    public function getFile()
    {
        return $this->file;
    }
    public function setAlternativeFile(File $alternative_file = null)
    {
        $this->alternativeFile = $alternative_file;
        return $this;
    }
    public function getAlternativeFile()
    {
        return $this->alternativeFile;
    }
    public function getBackgroundFile()
    {
        return $this->backgroundFile;
    }
    public function setBackgroundFile(File $backgroundFile = null)
    {
        $this->backgroundFile = $backgroundFile;
        return $this;
    }
    public function setLanguage(Language $language = null)
    {
        $this->language = $language;
        return $this;
    }
    public function getLanguage()
    {
        return $this->language;
    }
    public function setBilling($billing)
    {
        $this->billing = $billing;
        return $this;
    }
    public function getBilling()
    {
        return $this->billing;
    }
    public function getRegisterDate(): \DateTimeInterface
    {
        return $this->registerDate;
    }
    public function setRegisterDate(\DateTimeInterface $registerDate): self
    {
        $this->registerDate = $registerDate;
        return $this;
    }
    /**
     * Add document.
     *
     * @return People
     */
    public function addDocument(\App\Entity\Document $document)
    {
        $this->document[] = $document;
        return $this;
    }
    /**
     * Add peopleEmployee.
     *
     * @return People
     */
    public function addPeopleEmployee(\App\Entity\PeopleEmployee $peopleEmployee)
    {
        $this->peopleEmployee[] = $peopleEmployee;
        return $this;
    }
    /**
     * Remove peopleEmployee.
     */
    public function removePeopleEmployee(\App\Entity\PeopleEmployee $peopleEmployee)
    {
        $this->peopleEmployee->removeElement($peopleEmployee);
    }
    /**
     * Get peopleEmployee.
     *
     * @return Collection
     */
    public function getPeopleEmployee()
    {
        return $this->peopleEmployee;
    }
    /**
     * Add peopleCompany.
     *
     * @return People
     */
    public function addPeopleCompany(\App\Entity\PeopleEmployee $peopleCompany)
    {
        $this->peopleCompany[] = $peopleCompany;
        return $this;
    }
    /**
     * Remove peopleCompany.
     *
     * @param \Core\Entity\PeopleCompany $peopleCompany
     */
    public function removePeopleCompany(\App\Entity\PeopleEmployee $peopleCompany)
    {
        $this->peopleCompany->removeElement($peopleCompany);
    }
    /**
     * Get peopleCompany.
     *
     * @return Collection
     */
    public function getPeopleCompany()
    {
        return $this->peopleCompany;
    }
    /**
     * Get peopleFranchisor.
     *
     * @return Collection
     */
    public function getPeopleFranchisor()
    {
        return $this->peopleFranchisor;
    }
    /**
     * Get peopleFranchisee.
     *
     * @return Collection
     */
    public function getPeopleFranchisee()
    {
        return $this->peopleFranchisee;
    }
    /**
     * Get peopleSalesman.
     *
     * @return Collection
     */
    public function getPeopleSalesman()
    {
        return $this->peopleSalesman;
    }
    /**
     * Add user.
     *
     * @return People
     */
    public function addUser(\ControleOnline\Entity\User $user)
    {
        $this->user[] = $user;
        return $this;
    }
    /**
     * Remove user.
     */
    public function removeUser(\ControleOnline\Entity\User $user)
    {
        $this->user->removeElement($user);
    }
    /**
     * Get user.
     *
     * @return Collection
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * Get document.
     *
     * @return Collection
     */
    public function getDocument()
    {
        return $this->document;
    }
    /**
     * Get address.
     *
     * @return Collection
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * Get document.
     *
     * @return Collection
     */
    public function getPhone()
    {
        return $this->phone;
    }
    /**
     * Get email.
     *
     * @return Collection
     */
    public function getEmail()
    {
        return $this->email;
    }
    public function getContractsPeople(): Collection
    {
        return $this->contractsPeople;
    }
    public function getTeams(): Collection
    {
        return $this->teams;
    }
    public function getUpcomingClass($date): SchoolClass
    {
        foreach ($this->getTeams() as $team) {
            $schoolClass = $team->getSchoolClass($date);
            if ('Scheduled' === $schoolClass->getSchoolClassStatus()->getLessonStatus()) {
                return $schoolClass;
            }
        }
        return
            new SchoolClass();
    }
    public function getClasses(): Collection
    {
        $classes =
            new ArrayCollection();
        foreach ($this->getTeams() as $team) {
            $classes->add($team->getSchoolClass());
        }
        return $classes;
    }
    public function getLessons(): Collection
    {
        $lessons =
            new ArrayCollection();
        foreach ($this->getClasses() as $class) {
            foreach ($class->getLessons() as $lesson) {
                $lessons->add($lesson);
            }
        }
        return $lessons;
    }
    public function setBillingDays(string $billingDays): self
    {
        $this->billingDays = $billingDays;
        return $this;
    }
    public function getBillingDays(): string
    {
        return $this->billingDays;
    }
    public function setPaymentTerm(int $paymentTerm): self
    {
        $this->paymentTerm = $paymentTerm;
        return $this;
    }
    public function getPaymentTerm(): int
    {
        return $this->paymentTerm;
    }
    public function getPeopleStudent(): ?PeopleStudent
    {
        return $this->peopleStudent;
    }
    public function setPeopleStudent(PeopleStudent $peopleStudent): self
    {
        $this->peopleStudent = $peopleStudent;
        // set the owning side of the relation if necessary
        if ($peopleStudent->getStudent() !== $this) {
            $peopleStudent->setStudent($this);
        }
        return $this;
    }
    /**
     * @return Collection|PeopleStudent[]
     */
    public function getPeopleStudents(): Collection
    {
        return $this->peopleStudents;
    }
    public function addPeopleStudent(PeopleStudent $peopleStudent): self
    {
        if (!$this->peopleStudents->contains($peopleStudent)) {
            $this->peopleStudents[] = $peopleStudent;
            $peopleStudent->setCompany($this);
        }
        return $this;
    }
    public function removePeopleStudent(PeopleStudent $peopleStudent): self
    {
        if ($this->peopleStudents->removeElement($peopleStudent)) {
            // set the owning side to null (unless already changed)
            if ($peopleStudent->getCompany() === $this) {
                $peopleStudent->setCompany(null);
            }
        }
        return $this;
    }
    public function getPeopleProfessional(): ?PeopleProfessional
    {
        return $this->peopleProfessional;
    }
    public function setPeopleProfessional(PeopleProfessional $peopleProfessional): self
    {
        $this->peopleProfessional = $peopleProfessional;
        // set the owning side of the relation if necessary
        if ($peopleProfessional->getProfessional() !== $this) {
            $peopleProfessional->setProfessional($this);
        }
        return $this;
    }
    /**
     * @return Collection|PeopleTeam[]
     */
    public function getPeopleTeams(): Collection
    {
        return $this->peopleTeams;
    }
    public function addPeopleTeam(PeopleTeam $peopleTeam): self
    {
        if (!$this->peopleTeams->contains($peopleTeam)) {
            $this->peopleTeams[] = $peopleTeam;
            $peopleTeam->setPeople($this);
        }
        return $this;
    }
    public function removePeopleTeam(PeopleTeam $peopleTeam): self
    {
        if ($this->peopleTeams->removeElement($peopleTeam)) {
            // set the owning side to null (unless already changed)
            if ($peopleTeam->getPeople() === $this) {
                $peopleTeam->setPeople(null);
            }
        }
        return $this;
    }
    public function getFoundationDate(): ?\DateTime
    {
        return $this->foundationDate;
    }
    public function setFoundationDate(\DateTimeInterface $date): self
    {
        $this->foundationDate = $date;
        return $this;
    }
    public function getFullName(): string
    {
        if ($this->getPeopleType() == 'F') {
            return trim(preg_replace('/[^A-Za-z\s]/', '', sprintf('%s %s', $this->getName(), $this->getAlias())));
        }
        return trim(preg_replace('/[^A-Za-z\s]/', '', $this->getName()));
    }
    public function isPerson(): bool
    {
        return $this->getPeopleType() == 'F';
    }
    public function getOneEmail(): ?Email
    {
        if (($email = $this->getEmail()->first()) === false) {
            return null;
        }
        return $email;
    }
    public function getOneDocument(): ?Document
    {
        $documents = $this->getDocument()->filter(function ($peopleDocument) {
            if ($peopleDocument->getPeople()->getPeopleType() == 'F') {
                return $peopleDocument->getDocumentType()->getDocumentType() == 'CPF';
            }
            return $peopleDocument->getDocumentType()->getDocumentType() == 'CNPJ';
        });
        return ($document = $documents->first()) === false ? null : $document;
    }
    public function getBirthdayAsString(): ?string
    {
        if ($this->getFoundationDate() instanceof \DateTimeInterface) {
            return $this->getFoundationDate()->format('Y-m-d');
        }
        return null;
    }
    /**
     * Get otherInformations
     *
     * @return stdClass
     */
    public function getOtherInformations($decode = false)
    {
        return $decode ? (object) json_decode(is_array($this->otherInformations) ? json_encode($this->otherInformations) : $this->otherInformations) : $this->otherInformations;
    }
    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function addOtherInformations($key, $value)
    {
        $otherInformations = $this->getOtherInformations(true);
        $otherInformations->{$key} = $value;
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }
    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function setOtherInformations(stdClass $otherInformations)
    {
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }
    /**
     * Add Config.
     *
     * @return People
     */
    public function addConfig(\App\Entity\Config $config)
    {
        $this->config[] = $config;
        return $this;
    }
    /**
     * Remove Config.
     */
    public function removeConfig(\App\Entity\Config $config)
    {
        $this->config->removeElement($config);
    }
    /**
     * Get config.
     *
     * @return Collection
     */
    public function getConfig()
    {
        return $this->config;
    }
}
