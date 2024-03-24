<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\People;

class AdminPeopleDocumentsAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(EntityManagerInterface $manager, Security $security)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(People $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT    => 'createDocument',
                Request::METHOD_DELETE => 'deleteDocument',
                Request::METHOD_GET    => 'getDocuments'  ,
            ];

            $payload   = json_decode($this->request->getContent(), true);
            $operation = $methods[$request->getMethod()];
            $result    = $this->$operation($data, $payload);

            return new JsonResponse([
                'response' => [
                    'data'    => $result,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);

        } catch (\Exception $e) {

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);

        }
    }

    private function createDocument(People $people, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($people->getId());
            $doctype = $this->manager->getRepository(DocumentType::class)->find($payload['type']);
            if ($doctype === null) {
                throw new \InvalidArgumentException('Document type not found');
            }

            $document = $this->manager->getRepository(Document::class)
                ->findOneBy([
                    'document'     => $payload['document'],
                    'documentType' => $doctype,
                ]);
            if ($document instanceof Document) {
                throw new \InvalidArgumentException('O documento já está em uso');
            }

            $document = $this->manager->getRepository(Document::class)
                ->findOneBy([
                    'documentType' => $doctype,
                    'people'       => $company,
                ]);
            if ($document instanceof Document) {
                throw new \InvalidArgumentException('Este tipo de documento já foi cadastrado');
            }

            $document = new Document();
            $document->setDocument    ($payload['document']);
            $document->setDocumentType($doctype);
            $document->setPeople      ($company);

            $this->manager->persist($document);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $document->getId()
            ];

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deleteDocument(People $people, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Document id is not defined');
            }

            $company   = $this->manager->getRepository(People::class)->find($people->getId());            
            $document = $this->manager->getRepository(Document::class)->findOneBy(['id' => $payload['id'], 'people' => $company]);
            if (!$document instanceof Document) {
                throw new \InvalidArgumentException('People document was not found');
            }

            $this->manager->remove($document);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getDocuments(People $people, ?array $payload = null): array
    {
        $members   = [];
        $company   = $this->manager->getRepository(People::class )->find($people->getId());
        $documents = $this->manager->getRepository(Document::class)->findBy(['people' => $company]);

        foreach ($documents as $document) {
            $members[] = [
                'id'       => $document->getId(),
                'type'     => $document->getDocumentType()->getDocumentType(),
                'document' => $document->getDocument(),
            ];
        }

        return [
            'members' => $members,
            'total'   => count($members),
        ];
    }
}
