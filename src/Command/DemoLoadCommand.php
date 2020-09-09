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
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
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
use App\Entity\Folder;
use App\Entity\Target;
use App\Entity\TplFolder;
use App\Service\CCETools;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Keycloak\Admin\KeycloakClient;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\KernelInterface;

class DemoLoadCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName       = 'demo:load';
    public           $browserClient     = null;
    private          $kc_realm          = "cockpit-ce";
    private          $kc_admin          = "admin";
    private          $kc_adminpwd       = '';
    private          $publicKeyFile     = "/data/appdatace/public/key.pub";
    private          $privateKeyFile    = "/data/appdatace/private/key";
    private          $privatesecretFile = "/data/appdatace/private/cockpitcore.secret";
    private          $adminUser         = "audie.fritsch";
    private          $adminPwd          = "audie.fritsch";
    private          $normalUser        = [
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
    private          $normalUserTests   = [
        "kallie.dibbert",
        "zoie.goldner"
    ];
    private          $normalPwd         = [];
    private          $iriConverter      = null; // Store Manager for Store-22
    private          $token             = '';
    private          $options           = [];
    private          $headers           = [];
    private          $cockpitClient     = 'cockpitview'; // normal user. Not admin
    private          $user              = "";
    private          $password          = "";
    private          $kc                = null;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $mediaFilesystem;

    private $fakedate = '';

    private $kernel = null;

    private $roleView  = [
        "roles" => [
            "CCEDashboard",
            "CCEUser",
        ]
    ];
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

    private $entityManager = null;

    private $idMapping = [];

    public function __construct(
        KernelInterface $kernel,
        IriConverterInterface $iriService,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        FilesystemInterface $acmeFilesystem
    ) {
        $this->entityManager   = $entityManager;
        $this->iriConverter    = $iriService;
        $this->kernel          = $kernel;
        $this->params          = $params;
        $this->mediaFilesystem = $acmeFilesystem;
        $this->normalPwd       = $this->normalUser;
        parent::__construct();
    }

    private function addAnswerValue(&$answerValues, $rawValue, $choice)
    {
        if (!empty($choice) && !empty($choice->id)) {
            $choice = $choice->id;
        } else {
            $choice = null;
        }
        $answerValues[] = ['rawValue' => $rawValue ?? "", "choice" => $choice ?? null];
    }

    private function cleanStorage()
    {
        $files = $this->mediaFilesystem->listContents('/', true);
        foreach ($files as $file) {
            $this->mediaFilesystem->delete($file['path']);
        }
    }

    private function doDeleteRequest($class, $id, $headers = [])
    {
        $this->init();
        $opt = empty($headers) ? $this->headers : $headers;
        if (is_array($id)) {
            $iri = static::findIriBy($class, $id);
        } else {
            $iri = $this->getIriConverter()->getIriFromResourceClass($class);
            if (!empty($id)) {
                $iri = "$iri/$id";
            }
        }
        return $this->doRequest($iri, 'DELETE', $opt);
    }

    private function doDirectRequest($operation, $url)
    {
        return $this->getbrowserClient()->request($operation, $url, $this->headers);
    }

    /**
     * @param       $route
     * @param array $headers
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    private function doGetRequest($class, $id = null, $additionnalRoute = "", $params = [], $files = [])
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
        return $this->doRequest($iri, 'GET', $opt, $files);
    }

    private function doGetSubresourceRequest($class, $id, $sub, $headers = [], $files = [])
    {
        $this->init();
        $opt = empty($headers) ? $this->headers : $headers;
        $this->assertIsString($id);
        $this->assertIsString($sub);
        $iri = $this->getIriConverter()->getIriFromResourceClass($class);

        $iri = $iri . '/' . $id . '/' . $sub;

        return $this->doRequest($iri, 'GET', $opt, $files);
    }

    /**
     * @param $route
     * @param $data
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    private function doPatchRequest($class, $id, $data, $headers = [])
    {
        $this->init();
        $headers                            = $this->headers;
        $headers['headers']['content-type'] = 'application/merge-patch+json';
        $opt                                = array_merge($headers, ['body' => json_encode($data)]);

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
    private function doPatchWithActionRequest($class, $id, $data, $action, $headers = [])
    {
        $this->init();
        $headers                            = $this->headers;
        $headers['headers']['content-type'] = 'application/merge-patch+json';
        $opt                                = array_merge($headers, ['body' => json_encode($data)]);

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

    private function doRequest($iri, $operation, $options, $file = [])
    {
        $hostname = gethostbyaddr(gethostbyname(gethostname()));

        $defaultURL = 'https://cockpitce.' . $hostname . '/core';
        $url        = CCETools::param($this->params, 'CCE_APIURL', $defaultURL) . $iri;

        if (!empty($this->getFakedate())) {
            $options['headers']['X-FAKETIME'] = $this->getFakedate();
        }
        return $this->getbrowserClient()->request($operation, $url, $options, $file);
    }

    private function doUploadFileRequest($class, $files)
    {
        $this->init();
        $hdr                            = $this->headers;
        $hdr['headers']['content-type'] = 'multipart/formdata';

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest($iri, 'POST', $hdr, $files);
    }

    private function fillFolder(&$folder)
    {
        $faker = Factory::create();

        foreach ($folder->questionnaires as $questionnaire) {
            foreach ($questionnaire->blocks as $block) {
                foreach ($block->questionAnswers as &$questionAnswer) {
                    $answerValues = [];
                    $choices      = $questionAnswer->question->choices;
                    if (empty($choices)) {
                        continue;
                    }
                    $writeRender = $questionAnswer->question->writeRenderer;
                    $maxChoices  = $questionAnswer->question->maxChoices > 0 ?: count($choices);

                    $nbChoices = rand($questionAnswer->question->minChoices, $maxChoices);

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
                                $this->addAnswerValue($answerValues, $faker->sentence(rand(2, 20)), $chosenChoice);
                                break;
                            case 'select':
                                $this->addAnswerValue($answerValues, strval($chosenChoice->position), $chosenChoice);
                                break;
                            case 'dateTime':
                                $this->addAnswerValue($answerValues, $faker->dateTimeBetween('-1 years',
                                                                                             $this->getFakedate())->format("Y-m-d H:i"),
                                                      $chosenChoice);
                                break;
                            case
                            'range': // ??
                            case 'number':
                                $min = $writeRender->min ?? 0;
                                $max = $writeRender->max ?? 0;
                                $this->addAnswerValue($answerValues, strval($faker->numberBetween($min, $max)),
                                                      $chosenChoice);
                                break;
                        }
                    }
                    $questionAnswer->answerValues = $answerValues;
                }
            }
        }
    }

    private function getBrowserClient(): CurlHttpClient
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
                CCETools::param($this->params, 'CCE_KEYCLOAKURL'),
                CCETools::param($this->params, 'CCE_KEYCLOAKSECRET'),
                CCETools::param($this->params, 'CCE_coreclient'),
                CCETools::param($this->params, 'CCE_KEYCLOAKREALM')
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

    private function importRealm($token, $json, $url)
    {
        // https://keycloak.vagrant.cockpitlab.local/auth/admin/realms/cockpit-ce/partialImport
        $options['base_uri'] = $url;
        $options['headers']  = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json;charset=UTF-8'
        ];
        $httpClient          = new Client($options);
        try {
            $response = $httpClient->request(
                'POST',
                $url . '/partialImport',
                $json
            );
            return json_decode($response->getBody(), true);

        } catch (ClientException $e) {
            // en cas d'erreur 4xx
            return [];
        }

    }

    private function init()
    {
        $this->options = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getToken(),
            'CONTENT_TYPE'       => 'application/json',
        ];
        $this->headers = [
            'headers' => [
                'content-type'  => 'application/json',
                'accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ];
    }

    private function loadKeycloak($keycloakFile)
    {
        $this->output->write("<info>Processing Keycloak import...</info>\n");
        // creates a  progress bar (50 units)
        $progressBar         = new ProgressBar($this->output, 5);
        $keycloakUrl         = CCETools::param($this->params, 'CCE_KEYCLOAKURL', 'http://localhost:8080');
        $keycloakAdminClient = KeycloakClient::factory([
                                                           'realm'     => 'master',
                                                           'username'  => $this->kc_admin,
                                                           'password'  => $this->kc_adminpwd,
                                                           'client_id' => 'admin-cli',
                                                           'baseUri'   => $keycloakUrl,
                                                           'Accept'    => 'application/json, text/plain'
                                                       ]);

        if (!file_exists($keycloakFile)) {
            $this->output->write("<error>file $keycloakFile does not exists</error>\n");
            return -1;
        }

        $keycloakJSON = file_get_contents($keycloakFile);
        $defaultURL   = CCETools::param($this->params, 'CCE_DEFAULTURL');
        $keycloakJSON = str_replace('%%cockpitcedefaulturl%%', $defaultURL, $keycloakJSON);

        $this->output->write("\n\t<info>deleting realm $this->kc_realm</info>\n");
        $res = $keycloakAdminClient->deleteRealm(['realm' => $this->kc_realm]);
        $progressBar->advance();
        $this->output->write("\n\t<info>creating realm $this->kc_realm</info>\n");
        $res = $keycloakAdminClient->importRealm([
                                                     'realm'                       => $this->kc_realm,
                                                     'enabled'                     => true,
                                                     "internationalizationEnabled" => true,
                                                     "supportedLocales"            => [
                                                         "en",
                                                         "fr"
                                                     ],
                                                     "defaultLocale"               => "en"
                                                 ]);
        $progressBar->advance();
        $this->output->write("\n\t<info>importing $keycloakFile</info>\n");
        $realmData                     = json_decode($keycloakJSON, true, 128);
        $realmData['ifResourceExists'] = 'OVERWRITE';

        $adminKc = new KeycloakConnector(
            CCETools::param($this->params, 'CCE_KEYCLOAKURL'),
            ['username' => $this->kc_admin, 'password' => $this->kc_adminpwd],
            'admin-cli',
            'master'
        );

        $adminKc->partialImport($this->kc_realm, $keycloakJSON);

        $users = $keycloakAdminClient->getUsers(['realm' => $this->kc_realm]);
        $this->output->write("\n\t<info>setting user default password</info>\n");
        foreach ($users as $currentUser) {
            $adminKc->setUserPassword($currentUser['id'], $this->kc_realm, strtolower($currentUser['username']));
            $this->output->write("<comment>\t\t" . $currentUser['username'] . "</comment>\n");
        }
        $progressBar->advance();

        $this->rebuildKCKeys();

        $progressBar->advance();
        $progressBar->finish();
        $this->output->write("\n");
        return 0;

    }

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
                            $c     = $response->getContent();
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

    private function makeFakeData()
    {

        // process each user
        for ($i = 0; $i < count($this->normalUser); $i++) {
            $this->setViewClient()->setNormalUser($i);
            $currentUser = $this->normalUser[$i];
            $this->output->write("<info>Processing user $currentUser </info>\n");
            // get targets
            $response   = $this->doGetRequest(Target::class, null, null, ['right' => 'CREATE']);
            $statusCode = $response->getStatusCode();
            if ($statusCode == 200) {
                $targets = json_decode($response->getContent(), true);

                // process each target
                foreach ($targets as $target) {
                    if (!in_array('CREATE', $target['rights'])) {
                        continue;
                    }
                    // get TplFolders
                    $response   = $this->doGetRequest(TplFolder::class, null, 'periods');
                    $statusCode = $response->getStatusCode();
                    if ($statusCode == 200) {
                        $tplFolders = json_decode($response->getContent(), true);

                        // process each Template Folder
                        foreach ($tplFolders as $tplFolder) {

                            $periods = $tplFolder['periods'];
                            foreach ($periods as $idx => $period) {
                                if (strtotime($period['start']) >= time()) {
                                    unset($periods[$idx]);
                                }
                            }
                            $folderLabel = $tplFolder['label'];
                            $this->output->write("<info>\t\tProcessing Folder $folderLabel </info>\n");
                            $progressBar = new ProgressBar($this->output, count($periods));

                            // process each period
                            foreach ($periods as $period) {

                                // maybe, create the folder
                                // make some tricks to randomize creation (or not)
                                $minFolders = $tplFolder['minFolders'] ?? 1;
                                $maxFolders = $tplFolder['maxFolders'] ?? 3; // 0 means no limit...
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
                                    $folder     = ['target' => $target['id'], 'tplFolder' => $tplFolder['id']];
                                    $response   = $this->doPostRequest(Folder::class, $folder);
                                    $statusCode = $response->getStatusCode();
                                    if ($statusCode == 201) {
                                        $folder = json_decode($response->getContent());
                                        $this->fillFolder($folder);
                                        $folder     = json_decode(json_encode($folder), true);
                                        $response   = $this->doPatchRequest(Folder::class, $folder['id'], $folder);
                                        $statusCode = $response->getStatusCode();
                                        if ($statusCode == 200) {
                                            // submit folder
                                            $response   = $this->doPatchWithActionRequest(Folder::class, $folder['id'],
                                                                                          [], 'submit');
                                            $statusCode = $response->getStatusCode();
                                            if ($statusCode != 200) {
                                                $error = json_decode($response->getContent(), JSON_PRETTY_PRINT);

                                                $this->output->write("<error>$error</error>\n");
                                            }
                                        } else {
                                            $error = json_decode($response->getContent(), JSON_PRETTY_PRINT);

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

    private function rebuildDB()
    {
        $this->output->write("<info>Rebuilding DB...</info>");

        // check src owner and permissions

        $entitiesToIgnore = ['src/Entity/TplFolderCalendar.php', 'src/Entity/TplFolderQuestionnaire.php'];

        $error = false;
        foreach ($entitiesToIgnore as $entity) {
            if (!is_writable($entity)) {
                $this->output->writeln(sprintf("<error>file '%s' not writable </error>", $entity));
                $error = true;
            }
        }
        if ($error) {
            exit(1);
        }

        // drop database
        $this->output->writeln("<comment>Drop DB...</comment>");
        $command    = $this->getApplication()->find('doctrine:database:drop');
        $arguments  = ['--force' => true];
        $returnCode = $command->run(new ArrayInput($arguments), $this->output);

        // create database
        $this->output->writeln("<comment>Create DB...</comment>");
        $command    = $this->getApplication()->find('doctrine:database:create');
        $arguments  = [];
        $returnCode = $command->run(new ArrayInput($arguments), $this->output);

        // create database schema
        $this->output->writeln("<comment>Create Schema...</comment>");
        $preCreateScript  = "
            mv src/Entity/TplFolderCalendar.php src/Entity/TplFolderCalendar.notentity;\
            mv src/Entity/TplFolderQuestionnaire.php src/Entity/TplFolderQuestionnaire.notentity";
        $postCreateScript = "
            mv src/Entity/TplFolderCalendar.notentity src/Entity/TplFolderCalendar.php;\
            mv src/Entity/TplFolderQuestionnaire.notentity src/Entity/TplFolderQuestionnaire.php";
        $command          = $this->getApplication()->find('doctrine:schema:update');
        $arguments        = ['--force' => true];
        exec($preCreateScript);
        $returnCode = $command->run(new ArrayInput($arguments), $this->output);
        exec($postCreateScript);

        $this->output->writeln("<info>Done!\n</info>");
    }

    private function rebuildKCKeys()
    {
        $this->output->write("<info>\nRebuilding keycloak keys...</info>");
        $keycloakUrl             = CCETools::param($this->params, 'CCE_KEYCLOAKURL', 'http://localhost:8080');
        $this->publicKeyFile     = $this->params->get('JWT_PUBLIC_KEY');
        $this->privateKeyFile    = $this->params->get('JWT_SECRET_KEY');
        $this->privatesecretFile = $this->params->get('JWT_PASSPHRASEFILE');

        $keycloakAdminClient = KeycloakClient::factory([
                                                           'realm'     => 'master',
                                                           'username'  => $this->kc_admin,
                                                           'password'  => $this->kc_adminpwd,
                                                           'client_id' => 'admin-cli',
                                                           'baseUri'   => $keycloakUrl
                                                       ]);
        $keys                = $keycloakAdminClient->getRealmKeys(['realm' => $this->kc_realm]);
        $key                 = "";
        $publickey           = "";
        foreach ($keys['keys'] as $k) {
            $publickey = $k['publicKey'] ?? $publickey;
            $key       = $k['certificate'] ?? $key;
        }

        // Save key/publicKey in file
        if (file_exists($this->publicKeyFile)) {
            chmod($this->publicKeyFile, 0666);
        }

        if (file_exists($this->privateKeyFile)) {
            chmod($this->privateKeyFile, 0666);
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
        chmod($this->publicKeyFile, 0644);
        chmod($this->privateKeyFile, 0644);

        $clients  = $keycloakAdminClient->getClients(['realm' => $this->kc_realm]);
        $clientId = '';
        foreach ($clients as $client) {
            $clientId = $client['clientId'] === 'cockpitcore' ? $client['id'] : $clientId;
        }
        $secret = $keycloakAdminClient->getClientSecret(['realm' => $this->kc_realm, 'id' => $clientId]);
        $secret = $secret['value'];
        if (file_exists($this->privatesecretFile)) {
            chmod($this->privatesecretFile, 0666);
        }
        file_put_contents($this->privatesecretFile, $secret);
        chmod($this->privatesecretFile, 0644);
        $this->output->write("<info>DONE!\n</info>");
    }

    private function removeEmptyArray($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->removeEmptyArray($haystack[$key]);
            }

            if (empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
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
            if ($onlyData && (preg_match('/tpl|Calendars|Categories|TplFolderPermissions|QuestionChoices/i', $table))) {
                continue;
            }

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
        $this->addOption('fakedata', null, InputOption::VALUE_NONE, 'instantiate and generate fake data.');
        $this->addOption('rebuild', null, InputOption::VALUE_NONE, 'rebuild DB first.');
        $this->addOption('kcrebuild', null, InputOption::VALUE_NONE, 'rebuild Keycloak Keys.');
        $this->addOption('clean', null, InputOption::VALUE_NONE, 'truncate all tables first.');
        $this->addOption('cleandata', null, InputOption::VALUE_NONE, 'truncate data tables first.');
        $this->addOption('tests', null, InputOption::VALUE_NONE, 'load for tests (less users).');
        $this->addArgument('dummy', InputArgument::OPTIONAL, 'dummy.');
    }

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

        if (!empty($keycloak)) {
            $this->loadKeycloak($keycloak);
        }
        if ($input->getOption('kcrebuild')) {
            $this->rebuildKCKeys();
        }

        if ($input->getOption('rebuild')) {
            $this->rebuildDB();
        }

        if ($input->getOption('clean')) {
            $this->getToken(true); // force token regenerate
            $this->cleanStorage();
            $this->cleanTables();
        }

        if ($input->getOption('cleandata')) {
            $this->getToken(true); // force token regenerate
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
