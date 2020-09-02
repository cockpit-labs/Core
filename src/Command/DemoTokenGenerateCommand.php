<?php
/*
 * Core
 * DemoTokenGenerateCommand.php
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

use App\Service\CCETools;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DemoTokenGenerateCommand extends Command
{
    protected static $defaultName   = 'demo:token:generate';
    private          $browserClient = null;

    private $cockpitclient = 'cockpitview';
    private $username      = 'none';

    private $keycloakUrl;
    private $coresecret;
    private $realm;

    public function __construct(
        ParameterBagInterface $params
    ) {
        $this->params = $params;
        parent::__construct();
    }

    private function getBrowserClient(): Client
    {
        if (empty($this->browserClient)) {
            $this->browserClient = new Client();
        }
        return $this->browserClient;
    }

    private function getCoreToken()
    {
        $response = $this->getBrowserClient()->request(
            'POST',
            $this->keycloakUrl . '/auth/realms/cockpit-ce/protocol/openid-connect/token',
            [
                'form_params' => [
                    'client_id'     => 'cockpitcore',
                    'client_secret' => $this->coresecret,
                    'grant_type'    => 'client_credentials',
                ],
                'verify'      => false
            ]
        );
        $body     = json_decode($response->getBody(), true);
        return $body['access_token'];
    }

    private function getToken()
    {
        $response = $this->getBrowserClient()->request(
            'POST',
            $this->keycloakUrl . '/auth/realms/cockpit-ce/protocol/openid-connect/token',
            [
                'verify'      => false,
                'form_params' => [
                    'client_id'  => $this->cockpitclient,
                    'username'   => $this->username,
                    'password'   => $this->username,
                    'grant_type' => 'password',
                ],
            ]
        );
        $body     = json_decode($response->getBody(), true);
        return $body['access_token'];
    }

    protected function configure()
    {
        // the short description shown while running "php bin/console list"
        $this->setDescription('Get a demo JWT token.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('This command allows you to get a demo JWT token...');

        // configure an argument
        $this->addOption('username', null, InputOption::VALUE_OPTIONAL, 'The username.', 'none');
        $this->addOption('client', null, InputOption::VALUE_REQUIRED,
                         'The CockpitCE Client (cockpitview, cockpitadmin or cockpitcore).','cockpitview');
        $this->addArgument('dummy', InputArgument::OPTIONAL, 'dummy.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        $this->output        = $output;
        $this->input         = $input;
        $this->cockpitclient = $input->getOption('client');
        $this->username      = $input->getOption('username');

        $this->keycloakUrl = CCETools::param($this->params,'CCE_KEYCLOAKURL');
        $this->coresecret  = CCETools::param($this->params,'CCE_KEYCLOAKSECRET');
        $this->realm       = CCETools::param($this->params,'CCE_KEYCLOAKREALM');

        $output->writeln([
                             'Demo token generator',
                             '=================',
                             '',
                         ]);

        $message="<info>Generating token for user </info><comment>".$this->username."</comment><info> and client </info><comment>".$this->cockpitclient."</comment>";

        $output->writeln($message);
        $output->writeln(str_repeat('-', strlen(strip_tags($message))));
        $output->writeln(' ');

        if($this->cockpitclient == 'cockpitcore')
        {
            $token=$this->getCoreToken();

        }else{
            $token=$this->getToken();

        }

        $output->writeln("<comment>Bearer $token</comment>");
        $output->writeln(' ');

        return 0;
    }
}