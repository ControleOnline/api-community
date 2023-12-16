<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ImportRepository;

use ControleOnline\Entity\Import;

class GetImportsAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Import repository
     *
     * @var ImportRepository
     */
    private $imports = null;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->manager = $entityManager;
        $this->imports = $this->manager->getRepository(Import::class);
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // get params
            $search     = $request->query->get('search', null);
            $tableParam = $request->query->get('tableParam', null);

            $page  = $request->query->get('page', 1);
            $limit = $request->query->get('limit', 10);
            $status = $request->query->get('status', -1);
            $import_type = $request->query->get('import_type', null);


            $paginate = [
                'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
                'limit' => !is_numeric($limit) ? 10 : $limit
            ];

            /**
             * @var string $tableName
             */
            $tableName = $request->get('tableName', null);

            $search   = [
                'search'     => $search,
                'tableParam' => $tableParam,
                'status' => $status,
                'import_type' => $import_type,
            ];

            if (!empty($tableName)) {
                $search["tableName"] = $tableName;
            }

            $imports = $this->imports->getAllImports($search, $paginate);
            $total = $this->imports->getAllImports($search, null, true);

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        "imports" => $imports,
                        "total"   => $total
                    ],
                    'success' => true,
                ]
            ], 200);
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
