<?php
/*
 * Core
 * DemoLoadCommand.php
 *
 * Copyright (c) 2020 Sentinelo
 *
 * @author  Christophe AGNOLA
 * @license MIT License (https://mit-license.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the “Software”), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace App\Command;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\CentralAdmin\KeycloakConnector;
use App\Entity\Folder\Folder;
use App\Entity\Folder\FolderTpl;
use App\Entity\Media\UserMedia;
use App\Entity\Target;
use App\Service\CCETools;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Keycloak\Admin\KeycloakClient;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class DemoLoadCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName    = 'demo:load';
    public           $browserClient  = null;
    private          $kc_admin       = "admin";
    private          $kc_adminpwd    = '';
    private          $publicKeyFile  = "/data/appdatace/public/key.pub";
    private          $privateKeyFile = "/data/appdatace/private/key";
    private          $adminUser      = "audie.fritsch";
    private          $adminPwd       = "audie.fritsch";

    private $time_start = 0;
    /**
     * @var string[]
     */
    private $normalUser = [
        "kallie.dibbert",
        "garfield.mayert",
        "queenie.jacobi",
        "maximillia.bode",
        "bethany.willms",
        "kacie.lockman",
        "manley.hegmann",
        "pietro.cronin",
        "tiara.goyette",
        "zoie.goldner"
    ];
    /**
     * @var string[]
     */
    private $normalUserTests = [
        "kallie.dibbert",
        "zoie.goldner"
    ];
    private $normalPwd       = [];
    /**
     * @var \ApiPlatform\Core\Api\IriConverterInterface
     */
    private $iriConverter = null; // Store Manager for Store-22
    /**
     * @var string
     */
    private $token = '';
    /**
     * @var array
     */
    private $options = [];
    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var string
     */
    private $cockpitClient = 'cockpitview'; // normal user. Not admin
    /**
     * @var string
     */
    private $user = "";
    /**
     * @var string
     */
    private $password = "";
    /**
     * @var null
     */
    private $kc = null;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $mediaFilesystem;

    /**
     * @var string
     */
    private $fakedate = '';

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel = null;

    /**
     * @var \string[][]
     */
    private $roleView = [
        "roles" => [
            "CCEDashboard",
            "CCEUser",
        ]
    ];
    /**
     * @var \string[][]
     */
    private $roleAdmin = [
        "roles" => [
            "CCEDashboard",
            "CCEUser",
            "CCEAdmin",
        ]
    ];

    private $roles;
    private $userId;

    private $input;
    private $output;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager = null;

    /**
     * @var \App\Command\LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $idMapping = [];
    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var false|mixed|string
     */
    private $coreUrl;
    /**
     * @var false|mixed|string
     */
    private $kcUrl;
    /**
     * @var false|mixed|string
     */
    private $kcSecret;
    /**
     * @var false|mixed|string
     */
    private $kcCoreClient;
    /**
     * @var false|mixed|string
     */
    private $kcRealm;
    /**
     * @var float|int
     */
    private $tokenDelayMultiplier;
    /**
     * @var false|mixed|string
     */
    private $kcSmtpServer;
    /**
     * @var bool
     */
    private $simpleimage;


    /**
     * DemoLoadCommand constructor.
     *
     * @param \Symfony\Component\HttpKernel\KernelInterface                             $kernel
     * @param \ApiPlatform\Core\Api\IriConverterInterface                               $iriService
     * @param \Doctrine\ORM\EntityManagerInterface                                      $entityManager
     * @param \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params
     * @param \League\Flysystem\FilesystemInterface                                     $acmeFilesystem
     */
    public function __construct(
        KernelInterface $kernel,
        IriConverterInterface $iriService,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        FilesystemInterface $acmeFilesystem,
        LoggerInterface $applogger
    ) {
        $this->logger          = $applogger;
        $this->entityManager   = $entityManager;
        $this->iriConverter    = $iriService;
        $this->kernel          = $kernel;
        $this->params          = $params;
        $this->mediaFilesystem = $acmeFilesystem;
        $this->normalPwd       = $this->normalUser;

        $this->publicKeyFile  = CCETools::filename($this->params, 'JWT_PUBLIC_KEY');
        $this->privateKeyFile = CCETools::filename($this->params, 'JWT_SECRET_KEY');

        $hostname           = gethostbyaddr(gethostbyname(gethostname()));
        $this->baseUrl      = CCETools::param($this->params, 'CCE_BASEURL', 'https://cockpitce.' . $hostname);
        $this->coreUrl      = CCETools::param($this->params, 'CCE_APIURL', $this->baseUrl . '/core');
        $this->kcUrl        = CCETools::param($this->params, 'CCE_KEYCLOAKURL');
        $this->kcSecret     = CCETools::param($this->params, 'CCE_KEYCLOAKSECRET');
        $this->kcCoreClient = CCETools::param($this->params, 'CCE_coreclient');
        $this->kcRealm      = CCETools::param($this->params, 'CCE_KEYCLOAKREALM');
        $smtpServerJSON     = CCETools::param($this->params, 'CCE_KEYCLOAK_smtpServer', '"{}"');
        $this->kcSmtpServer = json_decode($smtpServerJSON, true);

        parent::__construct();
    }

    /**
     * @param       $answers
     * @param       $rawValue
     * @param       $choice
     */
    private function addAnswer(&$answers, $rawValue, $choice)
    {
        if (!empty($choice) && !empty($choice->id)) {
            $choice = $choice->id;
        } else {
            $choice = null;
        }
        $answers[] = ['rawValue' => $rawValue ?? "", "choice" => $choice ?? null];
    }

    /**
     * @param $filename
     *
     * @return mixed
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function addPhotos($filename, $ext = '')
    {
        $tmpFile = $this->kernel->getLocalTmpDir() . '/' . basename($filename) . $ext;
        copy($filename, $tmpFile);

        $response = $this->doUploadFileRequest(UserMedia::class, $tmpFile);
        $response = json_decode($response->getContent(), true);
        unlink($tmpFile);
        $this->logUseTime(" DEMO:LOAD upload photo");

        return $response['id'];
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    private function cleanStorage()
    {
        $files = $this->mediaFilesystem->listContents('/', true);
        foreach ($files as $file) {
            $this->mediaFilesystem->delete($file['path']);
        }
    }


    /**
     * @param        $class
     * @param null   $id
     * @param string $additionnalRoute
     * @param array  $params
     *
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function doGetRequest($class, $id = null, $additionnalRoute = "", $params = [])
    {
        $this->init();
        $opt = $this->headers;
        if (is_array($id)) {
            $iri = static::findIriBy($class, $id);
        } else {
            $iri = $this->getIriConverter()->getIriFromResourceClass($class);
            if (!empty($id)) {
                $iri = "$iri/$id";
            }
        }
        $iri = rtrim($iri . "/$additionnalRoute", '/');
        if (!empty($params)) {
            $opt['query'] = $params;
        }
        return $this->doRequest($iri, 'GET', $opt);
    }

    /**
     * @param $route
     * @param $data
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    private function doPatchRequest($class, $id, $data)
    {
        $this->init();
        $hdrs                            = $this->headers;
        $hdrs['headers']['content-type'] = 'application/merge-patch+json';
        $opt                             = array_merge($hdrs, ['body' => json_encode($data)]);

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest("$iri/$id", 'PATCH', $opt);
    }

    /**
     * @param       $class
     * @param       $id
     * @param       $data
     * @param       $action
     * @param array $headers
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\HttpClient\ResponseInterface
     */
    private function doPatchWithActionRequest($class, $id, $data, $action)
    {
        $this->init();
        $hdrs                            = $this->headers;
        $hdrs['headers']['content-type'] = 'application/merge-patch+json';
        $opt                             = array_merge($hdrs, ['body' => json_encode($data)]);

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest("$iri/$id/$action", 'PATCH', $opt);
    }

    /**
     * @param $route
     * @param $data
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    private function doPostRequest($class, $data)
    {
        $this->init();
        $opt = array_merge($this->headers, ['body' => json_encode($data)]);
        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest($iri, 'POST', $opt);
    }

    /**
     * @param        $iri
     * @param        $operation
     * @param        $options
     * @param string $file
     *
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function doRequest($iri, $operation, $options = null, $file = '')
    {
        if (empty($options)) {
            $options = $this->headers;
        }
        $url = $this->coreUrl . $iri;

        if (!empty($this->getFakedate())) {
            $options['headers']['X-FAKETIME'] = $this->getFakedate();
        }
        if (!empty($file)) {
            $formFields = [
                'file' => DataPart::fromPath($file)
            ];

            $formData = new FormDataPart($formFields);
            $formData->getHeaders()->addTextHeader('Authorization', 'Bearer ' . $this->getToken());

            return $this->getbrowserClient()->request('POST', $this->coreUrl . $iri, [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body'    => $formData->bodyToString(),
            ]);
        } else {
            return $this->getbrowserClient()->request($operation, $url, $options);

        }
    }

    /**
     * @param $class
     * @param $file
     *
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function doUploadFileRequest($class, $file)
    {
        $this->init();
        $hdr = $this->headers;

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest($iri, 'POST', $hdr, $file);
    }

    /**
     */
    private function fillFolder(&$folderTpl)
    {
        $faker = Factory::create();

        foreach ($folderTpl->questionnaires as &$questionnaire) {
            // add tasks
            for ($currentTaskIdx = 0; $currentTaskIdx < rand(0, 5); $currentTaskIdx++) {
                $countInformed = rand(0, 3);
                for ($inf = 0; $inf < $countInformed; $inf++) {
                    $response = $this->doRequest("/api/users?search=" . $this->normalUser[array_rand($this->normalUser)],
                                                 'GET');
                    $user     = json_decode($response->getContent());
                    if (!empty($user) && is_array($user)) {
                        $questionnaire->tasks[$currentTaskIdx]['informed'][] = $user[0];
                    }
                }
                $response                                             = $this->doRequest("/api/users?search=" . $this->normalUser[array_rand($this->normalUser)],
                                                                                         'GET');
                $user                                                 = json_decode($response->getContent());
                $questionnaire->tasks[$currentTaskIdx]['responsible'] = $user[0];
                $questionnaire->tasks[$currentTaskIdx]['action']      = $faker->sentence(20);
                $questionnaire->tasks[$currentTaskIdx]['dueDate']     = rand(0,3)>0?$faker->dateTimeBetween('now', '+1 month')->format("Y-m-d H:i"):null;
            }
            foreach ($questionnaire->blocks as $block) {
                foreach ($block->questions as &$question) {
                    $answers = [];
                    $choices = $question->choices;
                    if (empty($choices)) {
                        continue;
                    }
                    $writeRender = $question->writeRenderer;
                    $maxChoices  = $question->maxChoices > 0 ?: count($choices);
                    $maxPhotos   = $question->maxPhotos;

                    $nbChoices = rand($question->minChoices, $maxChoices);
                    $nbPhotos  = rand(0, $maxPhotos);
                    $photos    = [];
                    for ($n = 0; $n < $nbPhotos; $n++) {
                        if($this->simpleimage) {
                            $photos[] = $this->addPhotos($this->kernel->getProjectDir()."/GrumpyBear.png", '.png');
                        }else {
                            $photos[] = $this->addPhotos("https://picsum.photos/500/300", '.jpg');
                        }
                    }
                    $question->photos = $photos;
                    if ($nbChoices === 0) {
                        continue;
                    }
                    $chosenChoices = array_rand(json_decode(json_encode($choices), true), $nbChoices);
                    $chosenChoices = (is_array($chosenChoices) ? $chosenChoices : [$choices[$chosenChoices]]);

                    foreach ($chosenChoices as $chosenChoice) {
                        switch ($writeRender->component) {
                            default:
                            case 'none':
                                break;
                            case 'text':
                                $this->addAnswer($answers, $faker->sentence(rand(2, 20)), $chosenChoice);
                                break;
                            case 'select':
                                $this->addAnswer($answers, strval($chosenChoice->position), $chosenChoice);
                                break;
                            case 'dateTime':
                                $this->addAnswer($answers,
                                                 $faker->dateTimeBetween('-1 years',
                                                                         $this->getFakedate())->format("Y-m-d H:i"),
                                                 $chosenChoice);
                                break;
                            case
                            'range': // ??
                            case 'number':
                                $min = $writeRender->min ?? 0;
                                $max = $writeRender->max ?? 0;
                                $this->addAnswer($answers, strval($faker->numberBetween($min, $max)),
                                                 $chosenChoice);
                                break;
                        }
                    }
                    $question->answers = $answers;
                }
            }
        }
    }

    private function getBrowserClient()
    {
        if (empty($this->browserClient)) {
            $this->browserClient = HttpClient::create();
        }
        return $this->browserClient;
    }

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return null
     */
    private function getIriConverter()
    {
        return $this->iriConverter;
    }

    /**
     * @return null
     */
    private function getKc(): KeycloakConnector
    {
        if (empty($this->kc)) {
            $this->kc = new KeycloakConnector(
                $this->kcUrl,
                $this->kcSecret,
                $this->kcCoreClient,
                $this->kcRealm
            );
        }
        return $this->kc;
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string
     */
    private function getToken($force = false): string
    {
        if (empty($this->token) || $force) {
            $this->requestToken();
        }
        return $this->token;
    }

    /**
     * @return mixed
     */
    private function getUserId()
    {
        if (empty($this->userId)) {
            $this->userId = $this->getKc()->getUserId($this->user);
        }
        return $this->userId;
    }

    /**
     *
     */
    private function init()
    {
        $mimeJSON      = 'application/json';
        $this->options = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getToken(),
            'CONTENT_TYPE'       => $mimeJSON,
        ];
        $this->headers = [
            'headers' => [
                'content-type'  => $mimeJSON,
                'accept'        => $mimeJSON,
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ];
    }

    /**
     * @param $keycloakFile
     *
     * @return int
     * @throws \Exception
     */
    private function loadKeycloak($keycloakFile)
    {
        $this->output->write("<info>Processing Keycloak import...</info>\n");
        // creates a  progress bar (50 units)
        $progressBar         = new ProgressBar($this->output, 5);
        $keycloakAdminClient = KeycloakClient::factory([
                                                           'realm'     => 'master',
                                                           'username'  => $this->kc_admin,
                                                           'password'  => $this->kc_adminpwd,
                                                           'client_id' => 'admin-cli',
                                                           'baseUri'   => $this->kcUrl,
                                                           'Accept'    => 'application/json, text/plain'
                                                       ]);

        if (!file_exists($keycloakFile)) {
            $this->output->write("<error>file $keycloakFile does not exists</error>\n");
            return -1;
        }

        $keycloakJSON = file_get_contents($keycloakFile);
        $keycloakJSON = str_replace('%%cockpitcebaseurl%%', $this->baseUrl, $keycloakJSON);
        $keycloakJSON = str_replace('%%cockpitcerealm%%', $this->kcRealm, $keycloakJSON);
        $keycloakJSON = str_replace('%%cockpitcoresecret%%', $this->kcSecret, $keycloakJSON);

        $this->output->write("\n\t<info>deleting realm $this->kcRealm</info>\n");
        $keycloakAdminClient->deleteRealm(['realm' => $this->kcRealm]);
        $progressBar->advance();
        $this->output->write("\n\t<info>creating realm $this->kcRealm</info>\n");

        $ret = $keycloakAdminClient->importRealm([
                                                     'realm'                              => $this->kcRealm,
                                                     'enabled'                            => true,
                                                     "internationalizationEnabled"        => true,
                                                     "accessTokenLifespan"                => 300 * $this->tokenDelayMultiplier,
                                                     "accessTokenLifespanForImplicitFlow" => 900 * $this->tokenDelayMultiplier,
                                                     "supportedLocales"                   => [
                                                         "en",
                                                         "fr"
                                                     ],
                                                     "smtpServer"                         => $this->kcSmtpServer,
                                                     "defaultLocale"                      => "en"
                                                 ]);

        if (!empty($ret['error'])) {
            $this->output->write("<error>" . $ret['error'] . "</error>\n");
            exit(1);

        }
        $progressBar->advance();
        $this->output->write("\n\t<info>importing $keycloakFile</info>\n");
        $realmData                     = json_decode($keycloakJSON, true, 128);
        $realmData['ifResourceExists'] = 'OVERWRITE';

        $adminKc = new KeycloakConnector(
            $this->kcUrl,
            ['username' => $this->kc_admin, 'password' => $this->kc_adminpwd],
            'admin-cli',
            'master'
        );

        $adminKc->partialImport($this->kcRealm, $keycloakJSON);

        $users = $keycloakAdminClient->getUsers(['realm' => $this->kcRealm]);
        $this->output->write("\n\t<info>setting user default password</info>\n");
        foreach ($users as $currentUser) {
            $adminKc->setUserPassword($currentUser['id'], $this->kcRealm, strtolower($currentUser['username']));
            $this->output->write("<comment>\t\t" . $currentUser['username'] . "</comment>\n");
        }
        $progressBar->advance();

        $this->rebuildKCKeys();

        $progressBar->advance();
        $progressBar->finish();
        $this->output->write("\n");
        return 0;

    }

    /**
     * @param $templateFile
     *
     * @return int
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function loadTemplates($templateFile)
    {
        if (!empty($templateFile)) {
            $entities = file_get_contents($templateFile);
            $entities = json_decode($entities, true);
            $this->output->write("Loading templates\n");

            // processing templates
            $this->setAdminUser()->setAdminClient()->getToken(true);

            foreach ($entities as $entity) {
                $entitytype = key($entity);
                $this->output->write("\tInjecting '$entitytype' \n");
                foreach ($entity as $listitem) {
                    $progressBar = new ProgressBar($this->output, count($listitem));
                    $progressBar->start();
                    foreach ($listitem as $item) {
                        $progressBar->advance();
                        // store local id
                        $currentLocalid = $item['id'] ?? 'noid';
                        unset($item['id']);
                        // process calculated values
                        array_walk($item, function (&$value) {
                            if (empty($value) || is_array($value)) {
                                return;
                            }
                            // calculated date
                            if (preg_match('/date\:(.*)/', $value, $matches)) {
                                $date = new \DateTime('now');
                                $date->modify($matches[1]);
                                $value = $date->format('r');
                            }
                        });

                        // calculated ids
                        // replace localid if needed
                        $jsonitem = json_encode($item);
                        foreach ($this->idMapping as $localid => $id) {
                            $jsonitem = str_replace("localid:$localid", $id, $jsonitem);
                        }
                        $item       = json_decode($jsonitem, true);
                        $response   = $this->doPostRequest('App\\Entity\\' . $entitytype, $item);
                        $statusCode = $response->getStatusCode();
                        if ($statusCode == 201) {
                            $response = json_decode($response->getContent(), true);
                            if (!empty($response['id'])) {
                                $this->idMapping[$currentLocalid] = $response['id'];
                            }
                        } else {
                            $response->getContent();
                            $error = json_decode($response->getContent(), JSON_PRETTY_PRINT);

                            $this->output->write("<error>$error</error>\n");
                            exit(1);
                        }
                    }
                    $progressBar->finish();
                    $this->output->write("\n");
                }
            }
        }
        $this->output->write("\n");
        return 0;
    }

    private function logUseTime(string $msg)
    {
        $useTime          = (hrtime(true) - $this->time_start) / 100000;
        $this->time_start = hrtime(true);
        $msg              = "[execution time $useTime ms] $msg";
        $this->getLogger()->debug($msg);
    }

    /**
     * @return int
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function makeFakeData()
    {

        // process each user
        for ($i = 0; $i < count($this->normalUser); $i++) {
            $this->setViewClient()->setNormalUser($i);
            $currentUser = $this->normalUser[$i];
            $this->output->write("<info>Processing user $currentUser</info>\n");
            // get targets
            $response   = $this->doGetRequest(Target::class, null, null, ['right' => 'CREATE']);
            $statusCode = $response->getStatusCode();
            $this->logUseTime(" DEMO:LOAD get Target");
            if ($statusCode == 200) {
                $targets = json_decode($response->getContent(), true);

                // process each target
                foreach ($targets as $target) {
                    if (!in_array('CREATE', $target['rights'])) {
                        continue;
                    }
                    // get FolderTpls
                    $response   = $this->doGetRequest(FolderTpl::class, null, 'periods');
                    $statusCode = $response->getStatusCode();
                    $this->logUseTime(" DEMO:LOAD get Folder periods");
                    if ($statusCode == 200) {
                        $folderTpls = json_decode($response->getContent(), true);

                        // process each Template Folder
                        foreach ($folderTpls as $folderTpl) {

                            $periods = $folderTpl['periods'];
                            foreach ($periods as $idx => $period) {
                                if (strtotime($period['start']) >= time()) {
                                    unset($periods[$idx]);
                                }
                            }
                            $folderLabel = $folderTpl['label'];
                            $this->output->write("<info>\t\tProcessing Folder $folderLabel </info>\n");
                            $progressBar = new ProgressBar($this->output, count($periods));

                            // process each period
                            foreach ($periods as $period) {

                                // maybe, create the folder
                                // make some tricks to randomize creation (or not)
                                $minFolders = $folderTpl['minFolders'] ?? 1;
                                $maxFolders = $folderTpl['maxFolders'] ?? 3; // 0 means no limit...
                                $maxFolder  = rand($minFolders, $maxFolders); // decrease maxfolder randomly
                                $minFolder  = rand($minFolders, $maxFolders);

                                for ($occurence = $minFolder; $occurence <= $maxFolder; $occurence++) {
                                    // set the date and time
                                    $date     = new \DateTime($period['start']);
                                    $hourDiff = random_int(-11, 11);
                                    $minDiff  = random_int(-59, 59);
                                    $date->modify("NOON $hourDiff HOURS $minDiff MINUTES");
                                    $this->setFakeDate($date->format('r'));

                                    // create a folder
                                    $folder = ['target' => $target['id'], 'folderTpl' => $folderTpl['id']];
                                    $this->logUseTime(" DEMO:LOAD prepare folder periods");
                                    $response   = $this->doPostRequest(Folder::class, $folder);
                                    $statusCode = $response->getStatusCode();
                                    $this->logUseTime(" DEMO:LOAD create folder");
                                    if ($statusCode == 201) {
                                        $folder = json_decode($response->getContent());
                                        $this->fillFolder($folder);
                                        $this->logUseTime(" DEMO:LOAD fill folder");
                                        $folder     = json_decode(json_encode($folder), true);
                                        $response   = $this->doPatchRequest(Folder::class, $folder['id'], $folder);
                                        $statusCode = $response->getStatusCode();
                                        $this->logUseTime(" DEMO:LOAD update folder");
                                        if ($statusCode == 200) {
                                            // submit folder
                                            $response = $this->doPatchWithActionRequest(Folder::class, $folder['id'],
                                                                                        [], 'submit');
                                            $this->logUseTime(" DEMO:LOAD submit folder");
                                            if ($statusCode != 200) {
                                                $content = $response->getContent();
                                                $error   = json_decode($content, JSON_PRETTY_PRINT);

                                                $this->output->write("<error>$error</error>\n");
                                            }
                                        } else {
                                            $content = $response->getContent();
                                            $error   = json_decode($content, JSON_PRETTY_PRINT);

                                            $this->output->write("<error>$error</error>\n");
                                            return 1;

                                        }
                                    } else {
                                        $error = json_decode($response->getContent(), JSON_PRETTY_PRINT);

                                        $this->output->write("<error>$error</error>\n");
                                        return 1;

                                    }
                                    $folder = null;
                                }
                                $progressBar->advance();
                            }
                            $progressBar->finish();
                            $this->output->write("\n");
                        }
                    }
                }
            }
        }
        $this->output->write("\n");

    }

    /**
     * @throws \Exception
     */
    private function rebuildDB()
    {
        $this->output->write("<info>Rebuilding DB...</info>");

        // check src owner and permissions

        $entitiesToIgnore = [
//            'src/Entity/FolderTplCalendar.php'      => 'src/Entity/FolderTplCalendar.notentity',
//            'src/Entity/QuestionnaireTplBlockTpl.php' => 'src/Entity/QuestionnaireTplBlockTpl.notentity'
        ];

        $error = false;
        foreach ($entitiesToIgnore as $phpFilename => $notEntityFilename) {
            if (file_exists($phpFilename)) {
                if (!is_writable($phpFilename)) {
                    $this->output->writeln(sprintf("<error>file '%s' not writable </error>", $phpFilename));
                    $error = true;
                } else {
                    // rename file
                    rename($phpFilename, $notEntityFilename);
                }
            } else {
                if (!file_exists($notEntityFilename)) {
                    $this->output->writeln(sprintf("<error>Neither files '%s' or '%s' exists </error>", $phpFilename,
                                                   $notEntityFilename));
                    $error = true;
                }
            }
        }

        if ($error) {
            exit(1);
        }

        // drop database
        $this->output->writeln("<comment>Drop DB...</comment>");
        $command   = $this->getApplication()->find('doctrine:database:drop');
        $arguments = ['--force' => true];
        $command->run(new ArrayInput($arguments), $this->output);

        // create database
        $this->output->writeln("<comment>Create DB...</comment>");
        $command   = $this->getApplication()->find('doctrine:database:create');
        $arguments = [];
        $command->run(new ArrayInput($arguments), $this->output);

        // create database schema
        $this->output->writeln("<comment>Create Schema...</comment>");
        $command   = $this->getApplication()->find('doctrine:schema:update');
        $arguments = ['--force' => true];
        $command->run(new ArrayInput($arguments), $this->output);
        foreach ($entitiesToIgnore as $phpFilename => $notEntityFilename) {
            if (file_exists($notEntityFilename)) {
                if (!is_writable($notEntityFilename)) {
                    $this->output->writeln(sprintf("<error>file '%s' not writable </error>", $notEntityFilename));
                    $error = true;
                } else {
                    // rename file
                    rename($notEntityFilename, $phpFilename);
                }
            } else {
                if (!file_exists($phpFilename)) {
                    $this->output->writeln(sprintf("<error>Neither files '%s' or '%s' exists </error>", $phpFilename,
                                                   $notEntityFilename));
                    $error = true;
                }
            }
        }
        if ($error) {
            exit(1);
        }

        $this->output->writeln("<info>Done!\n</info>");
    }

    /**
     *
     */
    private function rebuildKCKeys()
    {
        $this->output->write("<info>\nRebuilding keycloak keys...</info>");

        $keycloakAdminClient = KeycloakClient::factory([
                                                           'realm'     => 'master',
                                                           'username'  => $this->kc_admin,
                                                           'password'  => $this->kc_adminpwd,
                                                           'client_id' => 'admin-cli',
                                                           'baseUri'   => $this->kcUrl
                                                       ]);
        $keys                = $keycloakAdminClient->getRealmKeys(['realm' => $this->kcRealm]);
        $key                 = "";
        $publickey           = "";
        foreach ($keys['keys'] as $k) {
            $publickey = $k['publicKey'] ?? $publickey;
            $key       = $k['certificate'] ?? $key;
        }

        // Save key/publicKey in file
        if (file_exists($this->publicKeyFile)) {
            @chmod($this->publicKeyFile, 0666);
        }

        if (file_exists($this->privateKeyFile)) {
            @chmod($this->privateKeyFile, 0666);
        }
        file_put_contents($this->publicKeyFile, "-----BEGIN PUBLIC KEY-----\n");
        while ($line = substr($publickey, 0, 64)) {
            file_put_contents($this->publicKeyFile, $line . "\n", FILE_APPEND);
            $publickey = substr($publickey, 64);
        }
        file_put_contents($this->publicKeyFile, "-----END PUBLIC KEY-----", FILE_APPEND);

        file_put_contents($this->privateKeyFile, "-----BEGIN RSA PRIVATE KEY-----\n");
        while ($line = substr($key, 0, 64)) {
            file_put_contents($this->privateKeyFile, $line . "\n", FILE_APPEND);
            $key = substr($key, 64);
        }
        file_put_contents($this->privateKeyFile, "-----END RSA PRIVATE KEY-----", FILE_APPEND);
        @chmod($this->publicKeyFile, 0644);
        @chmod($this->privateKeyFile, 0644);

        $clients  = $keycloakAdminClient->getClients(['realm' => $this->kcRealm]);
        $clientId = '';
        foreach ($clients as $client) {
            $clientId = $client['clientId'] === 'cockpitcore' ? $client['id'] : $clientId;
        }
        $secret = $keycloakAdminClient->getClientSecret(['realm' => $this->kcRealm, 'id' => $clientId]);
        $secret = $secret['value'];

        if ($secret != $this->kcSecret) {
            $this->output->write("<error>secret in keycloak does not match secret in .env!\n</error>");
        }
        $this->output->write("<info>DONE!\n</info>");
    }

    /**
     *
     */
    private function requestToken()
    {
        $this->token = $this->getKc()->requestToken($this->cockpitClient, $this->getUser(), $this->getPassword());
    }

    /**
     * @return $this
     */
    private function setAdminClient(): self
    {
        $this->setCockpitClient('cockpitadmin');
        return $this;
    }

    /**
     * @return $this
     */
    private function setAdminUser(): self
    {
        $this->setPassword($this->adminPwd);
        $this->setUser($this->adminUser);
        $this->token = '';
        $this->roles = $this->roleAdmin;
        return $this;
    }

    /**
     * @param string $cockpitClient
     *
     * @return $this
     */
    private function setCockpitClient(string $cockpitClient): self
    {
        $this->cockpitClient = $cockpitClient;
        $this->token         = null;
        return $this;
    }

    /**
     * @return $this
     */
    private function setNormalUser($idx): self
    {
        $this->setPassword($this->normalPwd[$idx])->setUser($this->normalUser[$idx])->setUserId($this->getKc()->getUserId($this->normalUser[$idx]));
        $this->token = '';
        $this->roles = $this->roleView;
        return $this;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    private function setPassword(string $password): self
    {
        $this->password = $password;
        $this->token    = null;
        return $this;
    }

    /**
     * @param string $user
     *
     * @return $this
     */
    private function setUser(string $user): self
    {
        $this->user  = $user;
        $this->token = null;
        return $this;
    }

    /**
     * @param mixed $userId
     */
    private function setUserId($userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return $this
     */
    private function setViewClient(): self
    {
        $this->setCockpitClient('cockpitview');
        return $this;
    }

    /**
     * @param false $onlyData
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function cleanTables($onlyData = false)
    {
        $connection = $this->entityManager->getConnection();
        $driver     = $connection->getDriver();
        $isMysql    = ($driver->getName() == "pdo_mysql");
        $tables     = $connection->getSchemaManager()->listTableNames();

        $this->output->write("<info>Cleaning DB\n</info>\n");
        $progressBar = new ProgressBar($this->output, count($tables));
        $progressBar->start();
        if ($isMysql) {
            $sql  = 'SET FOREIGN_KEY_CHECKS = 0;';
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }
        foreach ($tables as $table) {
            if ($isMysql) {
                $sql = "TRUNCATE TABLE $table;";
            } else {
                $sql = "DELETE FROM $table;";

            }
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $progressBar->advance();
        }
        if ($isMysql) {
            $sql  = 'SET FOREIGN_KEY_CHECKS = 1;';
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }
        $progressBar->finish();
        $this->output->write("\n");
    }

    /**
     *
     */
    protected function configure()
    {
        // the short description shown while running "php bin/console list"
        $this->setDescription('Load demo data.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('This command allows you to load the demo data in db...');

        // configure an argument
        $this->addOption('template', null, InputOption::VALUE_OPTIONAL, 'The json template file to load.');
        $this->addOption('data', null, InputOption::VALUE_OPTIONAL, 'The json data file to load.');
        $this->addOption('keycloak', null, InputOption::VALUE_OPTIONAL, 'The keycloak json file to load');
        $this->addOption('kcadmin', null, InputOption::VALUE_OPTIONAL, 'The keycloak admin user', 'admin');
        $this->addOption('kcadminpwd', null, InputOption::VALUE_OPTIONAL, 'The keycloak admin password', '');
        $this->addOption('testtoken', null, InputOption::VALUE_NONE, 'high delay token.');
        $this->addOption('fakedata', null, InputOption::VALUE_NONE, 'instantiate and generate fake data.');
        $this->addOption('rebuild', null, InputOption::VALUE_NONE, 'rebuild DB first.');
        $this->addOption('kcrebuild', null, InputOption::VALUE_NONE, 'rebuild Keycloak Keys.');
        $this->addOption('clean', null, InputOption::VALUE_NONE, 'truncate all tables first.');
        $this->addOption('cleandata', null, InputOption::VALUE_NONE, 'truncate data tables first.');
        $this->addOption('tests', null, InputOption::VALUE_NONE, 'load for tests (less users).');
        $this->addOption('simpleimage', null,InputOption::VALUE_NONE, 'no picsum image.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        $this->output      = $output;
        $this->input       = $input;
        $this->kc_adminpwd = $input->getOption('kcadminpwd');
        $output->writeln([
                             'Demo data loading',
                             '=================',
                             '',
                         ]);
        $keycloak  = $input->getOption('keycloak');
        $templates = $input->getOption('template');
        $data      = $input->getOption('data');
        if ($input->getOption('tests')) {
            $this->normalPwd = $this->normalUser = $this->normalUserTests;
        }

        $this->simpleimage=$input->getOption('simpleimage');

        $this->tokenDelayMultiplier = 1;
        if ($input->getOption('testtoken')) {
            $this->tokenDelayMultiplier = 60 * 24;
        }

        if (!empty($keycloak)) {
            $this->loadKeycloak($keycloak);
        }

        if ($input->getOption('kcrebuild')) {
            $this->rebuildKCKeys();
        }

        if ($input->getOption('rebuild')) {
            $this->cleanStorage();
            $this->rebuildDB();
        }
        if ($input->getOption('clean')) {
            $this->getToken(true); // force token regenerate
            $this->cleanStorage();
            $this->cleanTables();
        }

        if ($input->getOption('cleandata')) {
            $this->getToken(true); // force token regenerate
            $this->cleanStorage();
            $this->cleanTables(true);
        }

        if (!empty($templates)) {
            $this->getToken(true); // force token regenerate
            $this->loadTemplates($templates);
        }

        if ($input->getOption('fakedata')) {
            $this->getToken(true); // force token regenerate
            $this->makeFakeData();
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getFakedate(): string
    {
        return $this->fakedate;
    }

    /**
     * @param string $fakedate
     */
    public function setFakedate(string $fakedate): void
    {
        $this->fakedate = $fakedate;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param \App\Command\LoggerInterface $logger
     */
    public function setLogger(\App\Command\LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param null $kc
     */
    public function setKc($kc): self
    {
        $this->kc = $kc;
        return $this;
    }

}
