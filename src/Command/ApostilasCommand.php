<?php

namespace App\Command;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Error;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use stdClass;
use ControleOnline\Service\DatabaseSwitchService;

class ApostilasCommand extends Command
{
  protected static $defaultName = 'app:apostilas';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  /**
   * Output
   *
   * @var OutputInterface
   */
  private $output = null;
  /**
   * client
   *
   * @var Client
   */
  private $client;
  /**
   * input
   *
   * @var InputInterface
   */
  /**
   * Entity manager
   *
   * @var DatabaseSwitchService
   */
  private $databaseSwitchService;
  private $input = null;
  private static $__basedir;
  private $result = null;
  private $urlOpcao = 'https://www.apostilasopcao.com.br';
  private $affiliateOpcaoPrefix = '?afiliado=';
  private $affiliateOpcao = 14970;
  private $urlNova = 'https://www.novaconcursos.com.br';
  private $affiliateNovaPrefix = '?acc=';
  private $affiliateNova = '8e9c6e1b8b274db0beabdb7a59772c64';
  private $website = 'https://www.estudeemude.com.br';
  private $wordpressUser = 'luizkim';
  private $wordpressPass = 'Y43E UXQT 2KB8 MIV8 BQ8Q Vyt9';
  private $force = false;
  private $targetName;


  public function __construct(EntityManagerInterface $entityManager, DatabaseSwitchService $databaseSwitchService)
  {
    $this->manager = $entityManager;
    $this->databaseSwitchService = $databaseSwitchService;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Copy Website.')
      ->setHelp('This command Copy Website.');

    $this->addArgument('target', InputArgument::REQUIRED, 'Target');
    $this->addArgument('force', InputArgument::OPTIONAL, 'Force');
  }

  protected function getAffiliate()
  {
    return  $this->{'affiliate' . ucfirst(strtolower($this->targetName)) . 'Prefix'}    .    $this->{'affiliate' . ucfirst(strtolower($this->targetName))};
  }

  protected function getUrl()
  {
    return  $this->{'url' . ucfirst(strtolower($this->targetName))};
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $domains = $this->databaseSwitchService->getAllDomains();
    foreach ($domains as $domain) {
      $this->databaseSwitchService->switchDatabaseByDomain($domain);

      $this->output = $output;
      $this->input = $input;
      $this->output->writeln([
        '',
        '=========================================',
        'Starting...',
        '=========================================',
        '',
      ]);
      $this->targetName = $this->input->getArgument('target');
      $this->force = $this->input->getArgument('force');

      try {


        $copy  = 'copy' . str_replace('-', '', ucwords(strtolower($this->targetName), '-'));
        if (method_exists($this, $copy) === false)
          throw new \Exception(sprintf('Notification target "%s" is not defined', $this->targetName));

        $this->output->writeln([
          '',
          '=========================================',
          sprintf('Notification target: %s', $this->targetName),
          '=========================================',
          '',
        ]);
      } catch (\Exception $e) {
        if ($this->manager->getConnection()->isTransactionActive())
          $this->manager->getConnection()->rollBack();

        $this->output->writeln([
          '',
          'Error: ' . $e->getMessage(),
          '',
        ]);
      }
      $this->$copy();
      $this->output->writeln([
        '',
        '=========================================',
        'End',
        '=========================================',
        '',
      ]);
    }
    return 0;
  }


  protected function getStates()
  {
    return [
      'TO', 'RR', 'RO', 'AC', 'AM', 'AP', 'PA', //Norte
      'MS', 'MT', 'DF', 'GO',  //Centro-Oeste      
      'MA', 'AL', 'PB', 'PE', 'PI', 'RN', 'SE', 'CE', 'BA', //Nordeste      
      'PR', 'SC', 'RS', // Sul
      'ES', 'RJ', 'MG', 'SP', // Sudeste
      'NACIONAL',
    ];
  }

  protected function extractState($state)
  {


    $uri = '/apostilas/' . strtolower($state);
    $this->result['uri'] = $uri;
    $this->result['url'] = $this->getUrl() . $this->result['uri'];
    $this->result['state'] = $state;
    $this->result['data'] = [];
    try {
      $response = $this->client->request(
        'GET',
        $uri, //['query' => ['p' => '2',]]
      );
      $this->processRequest($response);
    } catch (Exception $th) {
      $this->output->writeln(['Error: ' . $th->getMessage()]);
    }
    return $this->result;
  }

  protected function processRequest($response, $pagination = false)
  {
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML($response->getBody(), LIBXML_NOERROR);
    $finder = new DOMXPath($dom);



    if ($this->force) {
      $pagination = $finder->query("//*[contains(@class, 'i-next')]");
      if ($pagination->length > 0) {
        $this->result['uri'] = $pagination[0]->getAttribute('href');
        $this->result['url'] = $this->getUrl() . $this->result['uri'];
        try {
          $page = $this->client->request('GET', $this->result['uri']);
          $this->processRequest($page, true);
        } catch (Exception $th) {
          $this->output->writeln(['Error: ' . $th->getMessage()]);
        }
      }
    }

    if ($this->force == 'all' || $pagination || !$this->force) {
      $nodes = $finder->query("//*[contains(@class, 'coluna_cartoes')]");
      foreach ($nodes as $k => $element) {
        $nodes = $element->childNodes;
        foreach ($nodes as $node) {
          if (!empty($node->nodeValue) && $node->nodeType == XML_ELEMENT_NODE) {
            $id = trim($node->getAttribute('data-id'));
            $file = self::getBasedir('apostilas' . DIRECTORY_SEPARATOR . $this->result['state']) . $id . '.json';
            $this->result['data'][$id] = $this->getDetails($file);
            $this->result['data'][$id]['id']        =    trim($id);
            $this->result['data'][$id]['state']     =    trim($this->result['state']);
            $this->result['data'][$id]['price'] = [
              'printed'     =>    trim($node->getAttribute('data-price')),
              'digital'     =>    trim($node->getAttribute('data-price'))
            ];
            $this->result['data'][$id]['brand']     =    trim($node->getAttribute('data-brand'));
            $this->result['data'][$id]['category']  =    array_map('trim', explode('/', $node->getAttribute('data-category')));
            $this->result['data'][$id]['variant']   =    trim($node->getAttribute('data-variant'));
            $this->result['data'][$id]['list']      =    trim($node->getAttribute('data-list'));
            $this->result['data'][$id]['href']      =    trim($node->getAttribute('href'))  . $this->getAffiliate();
            $this->result['data'][$id]['img']       =    trim($node->getElementsByTagName('img')[0]->getAttribute('data-src')) .  $this->getAffiliate();
          }
        }
      }
    }


    return $this->result;
  }

  protected function getDetails($file)
  {
    if (is_file($file)) {
      return (array)json_decode(file_get_contents($file));
    }
    return [];
  }

  protected function processDetails($file)
  {
    $apostila = $this->getDetails($file);
    if ($apostila) {
      $page = null;
      try {
        $page = $this->client->request('GET', $apostila['href']);
        if ($page) {

          $dom = new DOMDocument();
          $dom->preserveWhiteSpace = false;
          $dom->loadHTML($page->getBody(), LIBXML_NOERROR);
          $finder = new DOMXPath($dom);

          $nodes = $finder->query("//*[contains(@class, 'dados_concurso')]");
          if ($nodes[0] instanceof DOMNode)
            $apostila['competition'] = utf8_decode($this->DOMinnerHTML($nodes[0]));

          $nodes = $finder->query("//*[contains(@class, 'pb-4')]");
          if ($nodes[0] instanceof DOMNode)
            $apostila['details'] = utf8_decode($this->DOMinnerHTML($nodes[0]));

          $nodes = $finder->query("//*[contains(@class, 'conteudo_topo_desktop')]");
          if ($nodes[0] instanceof DOMNode)
            $apostila['title'] = utf8_decode($this->DOMinnerHTML($nodes[0]));


          file_put_contents($file, json_encode($apostila));
        }
      } catch (Exception $th) {
        $this->output->writeln(['Error: ' . $th->getMessage()]);
      }
    }
  }


  protected function removeLazyload(DOMNode $element)
  {
    $links = $element->getElementsByTagName('img');
    foreach ($links as $link) {
      $img = $link->getAttribute('data-src');
      if ($img)
        $link->setAttribute('src', $img);
    }
    return $element;
  }

  protected function addPartnerParams(DOMNode $element)
  {
    $links = $element->getElementsByTagName('a');
    foreach ($links as $link) {
      $link->setAttribute('href', explode('?', $link->getAttribute('href'))[0]  . $this->getAffiliate());
      $link->setAttribute('class', $link->getAttribute('class') . ' affiliate-click conversion');
    }
    return $this->removeLazyload($element);
  }

  protected function DOMinnerHTML(DOMNode $element)
  {
    $innerHTML = "";
    $element = $this->addPartnerParams($element);
    $children  = $element->childNodes;
    foreach ($children as $child) {
      $innerHTML .= preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ',  $element->ownerDocument->saveHTML($child));
    }

    return $innerHTML;
  }


  protected function getFileContents($file)
  {
    if (is_file($file)) {
      return file_get_contents($file);
    } else {
      $this->output->writeln([
        '=========================================',
        'File Not Found: ' . $file,
        '=========================================',
      ]);
    }
  }

  protected function copyPublicar()
  {
    $this->output->writeln([
      '=========================================',
      'Processando informações ',
      'URL: ' . $this->website,
      '=========================================',
    ]);

    $base64 = base64_encode($this->wordpressUser . ':' . $this->wordpressPass);
    $this->client = new Client([
      'http_errors' => false,
      'base_uri' => $this->website,
      'verify' => false,
      'headers' => ['Content-Type' => 'application/json', "Accept" => "application/json", 'Authorization' => "Basic " . $base64],
    ]);

    try {
      //code...
      $data = [];
      $file = $this->getFileContents($this->getBasedir() . 'sitemap.json');
      if ($file) {
        $json_sitemap = json_decode($file);
        foreach ($json_sitemap as $map) {
          $urls = $this->getFileContents($this->getBasedir(null) . $map);
          if ($urls) {
            foreach (json_decode($urls) as $apostila) {
              if (isset($apostila->base) &&  isset($apostila->id)) {
                $url = '/' . $apostila->base . '/' . $apostila->id . '.html';
                $f = $this->getBasedir() . $apostila->state . DIRECTORY_SEPARATOR . $apostila->id . '.json';
                $details = $this->getFileContents($f);

                /**
                 * Se o arquivo json existe
                 */
                if ($details) {
                  $a = json_decode($details);
                  /**
                   * Se o arquivo json tem as informações
                   */
                  if ($a && isset($a->competition) && isset($a->details) && isset($a->img) && isset($a->title) && isset($apostila->id)) {
                    $data = [
                      'link' => $url,
                      'slug' => $apostila->id,
                      'title' => ucwords(strtolower($a->title)),
                      'content' => $this->createContent($a),
                      'status' => 'publish',
                      'featured_media_src_url' => $a->img,
                      //'categories' => $apostila->base . ',' . $apostila->state . ',' . implode(',', $a->category),
                      //'tags' => $apostila->base . ',' . $apostila->state . ',' . implode(',', $a->category)
                    ];

                    $post = $this->client->request('GET', '/wp-json/wp/v2/apostilas?slug=' . urlencode($apostila->id));
                    $json = json_decode($post->getBody()->getContents());
                    /**
                     * Se o post existe no wordpress
                     */
                    if (!empty($json)) {
                      if ($this->force) {
                        $this->output->writeln([
                          '=========================================',
                          'Post founded: ' . $url,
                          'FORCE UPDATE',
                          '=========================================',
                        ]);
                        $response = $this->client->put('/wp-json/wp/v2/apostilas/' . $json[0]->id, ['body' => json_encode($data)]);
                        //print_r(json_decode($response->getBody()));
                      } else {
                        $this->output->writeln([
                          '=========================================',
                          'Post founded: ' . $url,
                          '=========================================',
                        ]);
                      }
                    } else {

                      /**
                       * Se não existe no wordpress
                       */
                      $response = $this->client->post('/wp-json/wp/v2/apostilas/', ['body' => json_encode($data)]);
                      $this->output->writeln([
                        '=========================================',
                        'Creating Post: ' . $url,
                        //'Data:' . json_encode($data),
                        //'Result: ' . $response->getBody(),
                        '=========================================',
                        '',
                      ]);
                    }
                  }
                }
              }
            }
          }
        }
      }
    } catch (Exception $th) {
      $this->output->writeln(
        [
          '=========================================',
          'Error Post: ' . $url,
          'Data:' . json_encode($data),
          'Error: ' . $th->getMessage(),
          '=========================================',
        ]
      );
    } catch (Error $th) {
      $this->output->writeln(
        [
          '=========================================',
          'Error Post: ' . $url,
          'Data:' . json_encode($data),
          'Error: ' . $th->getMessage(),
          '=========================================',
        ]
      );
    } catch (ClientException $th) {
      $this->output->writeln(
        [
          '=========================================',
          'Error Post: ' . $url,
          'Data:' . json_encode($data),
          'Error: ' . $th->getMessage(),
          '=========================================',
        ]
      );
    }
  }




  protected function createContent(stdClass $apostila)
  {
    $html = '<div class="apostila">';
    $html .= '<div class="resumo">';
    $html .= '<div class="imagem">';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= '<img src="' . $apostila->img . '"/>';
    $html .= '</a>';
    $html .= '</div>';
    $html .= '<div class="concurso">';
    $html .= '<h1 class="titulo">';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= ucwords(strtolower($apostila->title));
    $html .= '</a>';
    $html .= '</h1>';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= $apostila->competition;
    $html .= '</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="comprar">';
    $html .= '<div class="price">';
    $html .= '<div class="price-printed">';
    $html .= 'Versão impressa: R$ ' . number_format($apostila->price->printed, 2, ',', '.');
    $html .= '</div>';
    $html .= '<div class="price-digital">';
    $html .= 'Versão digital: R$ ' . number_format($apostila->price->digital, 2, ',', '.');
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="botao-comprar">';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= 'Eu quero';
    $html .= '</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="detalhes">';
    $html .= '<h1 class="titulo">';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= ucwords(strtolower($apostila->title));
    $html .= '</a>';
    $html .= '</h1>';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= $apostila->details;
    $html .= '</a>';
    $html .= '</div>';
    $html .= '<div class="comprar">';
    $html .= '<div class="price">';
    $html .= '<div class="price-printed">';
    $html .= 'Versão impressa: R$ ' . number_format($apostila->price->printed, 2, ',', '.');
    $html .= '</div>';
    $html .= '<div class="price-digital">';
    $html .= 'Versão digital: R$ ' . number_format($apostila->price->digital, 2, ',', '.');
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="botao-comprar">';
    $html .= '<a class="affiliate-click conversion"  data-id="' . $apostila->id . '" href="' . $apostila->href . '">';
    $html .= 'Eu quero';
    $html .= '</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }


  protected function homeOpcao()
  {
    $url = 'apostilas-opcao';
    $response = $this->client->request(
      'GET',
      '/'
    );
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML($response->getBody(), LIBXML_NOERROR);
    $finder = new DOMXPath($dom);


    $apostila['estados'] = '
    <link rel="stylesheet" type="text/css" href="https://www.apostilasopcao.com.br/skin/frontend/new/opcao/css/bootstrap.min.css?v=69" media="all" />
    <link rel="stylesheet" type="text/css" href="https://www.apostilasopcao.com.br/skin/frontend/new/opcao/css/css-geral.css?v=69" media="all" />
    <link rel="stylesheet" type="text/css" href="https://www.apostilasopcao.com.br/skin/frontend/new/opcao/css/home.css?v=69" media="all" />
    <link rel="stylesheet" type="text/css" href="https://www.apostilasopcao.com.br/skin/frontend/new/opcao/css/listagem.css?v=69" media="all" />
    <link rel="stylesheet" type="text/css" href="https://www.apostilasopcao.com.br/skin/frontend/new/opcao/css/swiper-bundle.min.css?v=69" media="all" />
    <div class="conteudo_barra_estados">
    <ul class="d-flex div_barra_estados justify-content-between">
    <li id="est_NACIONAL" class="estado_li"><a class="affiliate-click conversion a_nacional" href="https://www.apostilasopcao.com.br/apostilas/nacional?afiliado=14970">NACIONAL</a></li>
    <li id="est_AC" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ac?afiliado=14970">AC</a></li>
    <li id="est_AL" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/al?afiliado=14970">AL</a></li>
    <li id="est_AM" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/am?afiliado=14970">AM</a></li>
    <li id="est_AP" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ap?afiliado=14970">AP</a></li>
    <li id="est_BA" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ba?afiliado=14970">BA</a></li>
    <li id="est_CE" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ce?afiliado=14970">CE</a></li>
    <li id="est_DF" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/df?afiliado=14970">DF</a></li>
    <li id="est_ES" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/es?afiliado=14970">ES</a></li>
    <li id="est_GO" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/go?afiliado=14970">GO</a></li>
    <li id="est_MA" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ma?afiliado=14970">MA</a></li>
    <li id="est_MG" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/mg?afiliado=14970">MG</a></li>
    <li id="est_MS" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ms?afiliado=14970">MS</a></li>
    <li id="est_MT" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/mt?afiliado=14970">MT</a></li>
    <li id="est_PA" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/pa?afiliado=14970">PA</a></li>
    <li id="est_PB" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/pb?afiliado=14970">PB</a></li>
    <li id="est_PE" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/pe?afiliado=14970">PE</a></li>
    <li id="est_PI" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/pi?afiliado=14970">PI</a></li>
    <li id="est_PR" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/pr?afiliado=14970">PR</a></li>
    <li id="est_RJ" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/rj?afiliado=14970">RJ</a></li>
    <li id="est_RN" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/rn?afiliado=14970">RN</a></li>
    <li id="est_RO" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/ro?afiliado=14970">RO</a></li>
    <li id="est_RR" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/rr?afiliado=14970">RR</a></li>
    <li id="est_RS" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/rs?afiliado=14970">RS</a></li>
    <li id="est_SC" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/sc?afiliado=14970">SC</a></li>
    <li id="est_SE" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/se?afiliado=14970">SE</a></li>
    <li id="est_SP" class="estado_li "><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/sp?afiliado=14970">SP</a></li>
    <li id="est_TO" class="estado_li div_ultimo_li_estados"><a class="affiliate-click conversion" href="https://www.apostilasopcao.com.br/apostilas/to?afiliado=14970">TO</a></li></ul>
    </div>';

    $nodes = $finder->query("//*[contains(@id, 'bannerHomeFull')]");
    if ($nodes[0] instanceof DOMNode)
      $apostila['banner'] = utf8_decode($this->DOMinnerHTML($nodes[0]));

    $nodes = $finder->query("//*[contains(@class, 'fundo-bg')]");
    if ($nodes[0] instanceof DOMNode)
      $apostila['destaques'] = utf8_decode($this->DOMinnerHTML($nodes[0]));

    $nodes = $finder->query("//*[contains(@id, 'vitrine')]");
    if ($nodes[0] instanceof DOMNode)
      $apostila['lancamentos'] = utf8_decode($this->DOMinnerHTML($nodes[0]));



    $base64 = base64_encode($this->wordpressUser . ':' . $this->wordpressPass);
    $this->client = new Client([
      'http_errors' => false,
      'base_uri' => $this->website,
      'verify' => false,
      'headers' => ['Content-Type' => 'application/json', "Accept" => "application/json", 'Authorization' => "Basic " . $base64],
    ]);
    $post = $this->client->request('GET', '/wp-json/wp/v2/pages?slug=' . urlencode($url));

    $data = [
      'link' => $url,
      'slug' => $url,
      'title' => 'Apostilas Opção',
      'content' => utf8_encode(implode('', $apostila)),
      'status' => 'publish'
    ];


    $json = json_decode($post->getBody()->getContents());
    /**
     * Se o post existe no wordpress
     */
    if (!empty($json)) {
      $this->output->writeln([
        '=========================================',
        'Home founded',
        'FORCE UPDATE',
        '/wp-json/wp/v2/pages/' . $json[0]->id,
        '=========================================',
      ]);
      $response = $this->client->put('/wp-json/wp/v2/pages/' . $json[0]->id, ['body' => json_encode($data)]);
    } else {

      $this->output->writeln([
        '=========================================',
        'Home not found',
        'Create',
        '=========================================',
      ]);

      $response = $this->client->post('/wp-json/wp/v2/pages/', ['body' => json_encode($data)]);
    }
  }

  protected function copyOpcao()
  {

    $this->client = new Client([
      'http_errors' => false,
      'base_uri' => $this->getUrl(),
      'verify' => false
    ]);

    $this->{'home' . ucfirst(strtolower($this->targetName))}();

    foreach ($this->getStates() as $state) {
      $response = $this->extractState($state);
      if ($response['data']) {
        foreach ($response['data'] as  $key => $apostila) {
          $file = self::getBasedir('apostilas' . DIRECTORY_SEPARATOR . $state) . $apostila['id'] . '.json';
          if (file_put_contents($file, json_encode($apostila))) {
            $this->output->writeln([
              '',
              '=========================================',
              'Processando informações de ' . $state,
              'URL: ' . $response['url'],
              'Response: ' . $apostila['id'],
              '=========================================',
              '',
            ]);
            $this->processDetails($file);
          }
        }
      }
    }
  }
  protected function getSitemap($urls)
  {
    $datetime = new DateTime(date('Y-m-d H:i:s'));
    $date = $datetime->format(DateTime::ATOM); // ISO8601   

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
    $xml .= '<url>';
    $xml .= '<loc>' . $this->website . '</loc>';
    $xml .= '<lastmod>' . $date . '</lastmod>';
    $xml .= '<changefreq>weekly</changefreq>';
    $xml .= '<priority>1.00</priority>';
    $xml .= '</url>';
    foreach ($urls as $url) {
      $xml .= '<url>';
      $xml .= '<loc>' . $this->website .  $url . '</loc>';
      $xml .= '<lastmod>' . $date . '</lastmod>';
      $xml .= '<changefreq>weekly</changefreq>';
      $xml .= '<priority>0.85</priority>';
      $xml .= '</url>';
    }
    $xml .= '</urlset>';
    return $xml;
  }
  protected function getIndexSitemap($urls)
  {
    $datetime = new DateTime(date('Y-m-d H:i:s'));
    $date = $datetime->format(DateTime::ATOM); // ISO8601   

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url) {
      $xml .= '<sitemap>';
      $xml .= '<loc>' . $this->website .  $url . '</loc>';
      $xml .= '</sitemap>';
    }
    $xml .= '</sitemapindex>';
    return $xml;
  }

  protected function copySitemap()
  {
    $exclude = ['..', '.', 'sitemap.xml', 'robots.txt', 'sitemap.json'];
    $xml_sitemap_paths = [];
    $json_sitemap_paths = [];
    foreach (array_diff(scandir($this->getBasedir()), $exclude) as $key =>  $sub) {
      $path = $this->getBasedir('apostilas' . DIRECTORY_SEPARATOR . $sub);
      if (is_dir($path)) {
        $urls = [];
        $json_urls = [];
        foreach (array_diff(scandir($path), $exclude) as $file) {
          $urls[] = '/apostilas/' . $sub . '/' . pathinfo($path . $file, PATHINFO_FILENAME) . '.html';
          $json_urls[] =
            [
              'id' => pathinfo($path . $file, PATHINFO_FILENAME),
              'base' => 'apostilas',
              'state' =>  $sub
            ];
        }
        if (!empty($urls)) {
          $xml_sitemap_paths[$key] =  '/apostilas/' . $sub . '/' . 'sitemap.xml';
          $json_sitemap_paths[$key] =  '/apostilas/' . $sub . '/' . 'sitemap.json';
          file_put_contents($path . DIRECTORY_SEPARATOR . 'sitemap.xml', $this->getSitemap($urls));
          file_put_contents($path . DIRECTORY_SEPARATOR . 'sitemap.json', json_encode($json_urls));
        }
      }
    }
    if (!empty($xml_sitemap_paths)) {
      file_put_contents($this->getBasedir() . 'sitemap.xml', $this->getIndexSitemap($xml_sitemap_paths));
      file_put_contents($this->getBasedir() . 'sitemap.json', json_encode($json_sitemap_paths));
      $this->createRobots();
    }
  }

  protected function createRobots()
  {
    $txt = 'User-agent: *
Allow: /
Sitemap: ' . $this->website . '/apostilas/sitemap.xml';

    $robots_path = $this->getBasedir() . 'robots.txt';
    if (!is_file($robots_path))
      file_put_contents($robots_path, $txt);
  }

  protected static function getBasedir($folder = 'apostilas')
  {
    self::$__basedir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR .  ($folder ? $folder . DIRECTORY_SEPARATOR : '');

    if (!is_dir(self::$__basedir)  && !file_exists(self::$__basedir)) {
      mkdir(self::$__basedir, 0777, true);
    }
    return self::$__basedir;
  }
}
