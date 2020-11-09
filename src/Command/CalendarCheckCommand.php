<?php
/*
 * Core
 * CalendarCheckCommand.php
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

use App\Entity\Calendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalendarCheckCommand extends Command
{
    protected static $defaultName = 'calendar:check';

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        // the short description shown while running "php bin/console list"
        $this->setDescription('Check and activate/deactivate calendar.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('This command calculates calendar availability...');
        $this->addArgument('dummy', InputArgument::OPTIONAL, 'dummy.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        $this->output = $output;
        $this->input  = $input;
        $output->writeln([
                             'Processing',
                             '==========',
                             '',
                         ]);

        $now = new \DateTime();
        // update calendars
        foreach ($this->entityManager->getRepository(Calendar::class)->findAll() as $calendar) {
            $valid = ($now >= $calendar->getPeriodStart()) && ($now <= $calendar->getPeriodEnd());
            $calendar->setValid($valid);
            $this->entityManager->persist($calendar);
            $this->entityManager->flush();
        }

        return 0;
    }
}