<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\WordPressService;

class PostsAction
{
    private $wp = null;    

    public function __construct(WordPressService $wp)
    {
      $this->wp = $wp;      
    }

    public function __invoke(array $data, Request $request): JsonResponse
    {
      try {

        $items = [];
        $categories = $request->get('categories', [332,332]);
        $include    = $request->get('include', false);
        $status    = $request->get('status', false);

        if ($categories)
            $input['categories'] = $categories;
        if ($include)
            $input['include'] = $include;
        if ($status)
            $input['status'] = $status;
        
        $items = $this->wp->search($input);        

        return new JsonResponse([
          'response' => [
            'data'    => $items,
            'count'   => count($items),
            'error'   => '',
            'success' => true,
          ],
        ]);

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
}
