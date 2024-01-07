<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\ParticularsType;
use ControleOnline\Entity\File as File;
use App\Library\Utils\File as FileName;

class UploadPersonFilesAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security;

    private $appKernel;

    public function __construct(Security $security, EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->security  = $security;
        $this->manager   = $entityManager;
        $this->appKernel = $appKernel;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            if (!($file = $request->files->get('file'))) {
                throw new \InvalidArgumentException('The file was not uploaded');
            }

            // validate person

            if (($person = $request->request->get('customer', null)) === null) {
                throw new \InvalidArgumentException('Person id is not defined');
            }
            $company = $this->manager->getRepository(People::class)->find($person);
            if ($company === null) {
                throw new \InvalidArgumentException('Person was not found');
            }

            // validate type

            if (($type = $request->request->get('type', null)) === null) {
                throw new \InvalidArgumentException('File type is not defined');
            }
            $type = $this->manager->getRepository(ParticularsType::class)->find($type);
            if ($type === null) {
                throw new \InvalidArgumentException('File type was not found');
            }
            if ($type->getFieldType() !== 'file') {
                throw new \InvalidArgumentException('File type is not valid');
            }

            $this->manager->getConnection()->beginTransaction();

            // create or update particular

            if (($id = $request->request->get('id', null)) !== null) {
                $particular = $this->manager->getRepository(Particulars::class)->find($id);
                if ($particular === null) {
                    throw new \InvalidArgumentException('File was not found');
                }

                if ($particular->getPeople() !== $company) {
                    throw new \InvalidArgumentException('Access denied');
                }
            }
            else {
                $particular = new Particulars();
                $particular->setPeople($company);
                $particular->setType  ($type);
            }

            // create or update stored file

            $fileUrl  = sprintf('/user/profile-file/id/%s/%s', $company->getId(), $file->getClientOriginalName());

            if (!empty($particular->getId())) {
                $fileValue  = @json_decode($particular->getValue());
                if (is_object($fileValue)) {
                    $storedFile = $this->manager->getRepository(File::class)->find($fileValue->file);
                    if ($storedFile === null) {
                        throw new \InvalidArgumentException('Stored file was not found');
                    }

                    $storedFile->setUrl ($fileUrl);
                    $storedFile->setContent($file->getContent());

                }
            }
            else {
                $storedFile = new File();
                $storedFile->setUrl ($fileUrl);
                $storedFile->setContent($file->getContent());

                $this->manager->persist($storedFile);

                $this->manager->flush();
            }

            // update particular value with the new file

            $value  = '{';
            $value .= '"file":'  . $storedFile->getId() . ',';
            $value .= '"name":"' . $file->getClientOriginalName() . '"';
            $value .= '}';

            $particular->setValue($value);

            $this->manager->persist($particular);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'id'   => $particular->getId(),
                        'type' => [
                            'id' => $type->getId()
                        ]
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

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
}
