<?php

namespace App\Controller;

use ControleOnline\Library\Postalcode\PostalcodeProviderBalancer;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\State;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AddressGeoController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route(path: '/postal-codes/{cep}', name: 'lookup_postal_code', methods: ['GET'])]
    public function lookupPostalCode(string $cep): JsonResponse
    {
        $digits = preg_replace('/\D+/', '', $cep) ?? '';
        if (strlen($digits) !== 8) {
            return new JsonResponse(['title' => 'Invalid CEP', 'detail' => 'CEP must have 8 digits', 'status' => 400], 400);
        }

        try {
            $balancer = new PostalcodeProviderBalancer();
            $address = $balancer->search($digits);
            $provider = $balancer->getProviderCodeName();
            if (method_exists($address, 'setProvider') && !$address->getProvider()) {
                $address->setProvider($provider);
            }

            $payload = method_exists($address, 'toArray') ? $address->toArray() : [];
            $payload['provider'] = $payload['provider'] ?? $provider;
            $payload['cep'] = $digits;

            $lat = $payload['latitude'] ?? null;
            $lng = $payload['longitude'] ?? null;
            $gmapsKey = $_ENV['GMAPS_KEY'] ?? getenv('GMAPS_KEY') ?: null;
            $payload['map'] = null;
            $payload['facade'] = null;
            $payload['hasMapsKey'] = (bool) $gmapsKey;

            if ($gmapsKey && $lat !== null && $lng !== null) {
                $payload['map'] = [
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lng,
                    'staticUrl' => sprintf(
                        'https://maps.googleapis.com/maps/api/staticmap?center=%s,%s&zoom=16&size=640x360&maptype=roadmap&markers=color:red%%7C%s,%s&key=%s',
                        $lat, $lng, $lat, $lng, $gmapsKey
                    ),
                ];
                $payload['facade'] = [
                    'streetViewUrl' => sprintf(
                        'https://maps.googleapis.com/maps/api/streetview?size=640x360&location=%s,%s&fov=80&heading=0&pitch=0&key=%s',
                        $lat, $lng, $gmapsKey
                    ),
                ];
            } elseif ($gmapsKey) {
                $q = urlencode(trim(sprintf('%s, %s, %s, %s, Brasil', $payload['street'] ?? '', $payload['district'] ?? '', $payload['city'] ?? '', $payload['uf'] ?? '')));
                if ($q !== '') {
                    $payload['map'] = [
                        'staticUrl' => sprintf(
                            'https://maps.googleapis.com/maps/api/staticmap?center=%s&zoom=16&size=640x360&maptype=roadmap&key=%s',
                            $q, $gmapsKey
                        ),
                    ];
                }
            }

            return new JsonResponse($payload, 200);
        } catch (\Throwable $e) {
            return new JsonResponse(['title' => 'Postal code lookup failed', 'detail' => $e->getMessage(), 'status' => 502], 502);
        }
    }

    #[Route(path: '/address-geo/countries', name: 'address_geo_countries', methods: ['GET'])]
    public function countries(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $qb = $this->em->createQueryBuilder()
            ->select('c.id, c.countrycode AS code, c.countryname AS name')
            ->from(Country::class, 'c')
            ->orderBy('c.countryname', 'ASC');
        if ($q !== '') {
            $qb->andWhere('c.countryname LIKE :q OR c.countrycode LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        $items = $qb->getQuery()->getArrayResult();
        return new JsonResponse(['member' => $items, 'totalItems' => count($items)]);
    }

    #[Route(path: '/address-geo/states', name: 'address_geo_states', methods: ['GET'])]
    public function states(Request $request): JsonResponse
    {
        $country = trim((string) $request->query->get('country', 'BR'));
        $qb = $this->em->createQueryBuilder()
            ->select('s.id, s.state AS name, s.uf AS uf, c.countrycode AS countryCode')
            ->from(State::class, 's')
            ->join('s.country', 'c')
            ->orderBy('s.state', 'ASC');
        if ($country !== '') {
            $qb->andWhere('c.countrycode = :cc OR c.isoalpha3 = :cc')
                ->setParameter('cc', strtoupper($country));
        }
        $items = $qb->getQuery()->getArrayResult();
        return new JsonResponse(['member' => $items, 'totalItems' => count($items)]);
    }
}
