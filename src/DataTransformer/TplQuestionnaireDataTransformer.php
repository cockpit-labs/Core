<?php
/*
 * Core
 * TplQuestionnaireDataTransformer.php
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

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\DataProvider\KeycloakDataProvider;
use App\Entity\TplQuestionnaire;
use App\Entity\TplQuestionnaireBlock;

final class TplQuestionnaireDataTransformer extends KeycloakDataProvider implements DataTransformerInterface
{

    public function getTplQuestionnaire(TplQuestionnaire $tplQuestionnaire): ?TplQuestionnaire
    {
        foreach ($tplQuestionnaire->getTplQuestionnaireBlocks() as $tplQuestionnaireBlock) {
            $tplQuestionnaireBlock->getTplBlock()->setPosition($tplQuestionnaireBlock->getPosition());

            $tplQuestionnaire->addTplBlock($tplQuestionnaireBlock->getTplBlock());

        }
        return $tplQuestionnaire;

    }

    public function setTplQuestionnaire(TplQuestionnaire $tplQuestionnaire, $context): ?TplQuestionnaire
    {
        $em = $this->getEntityManager();

        // reconstruct tplBlocks and tplQuestionnaireBlocks
        // by removing all tplQuestionnaireBlocks that does not connected to a tplBlock
        foreach ($tplQuestionnaire->getTplQuestionnaireBlocks() as $tplQuestionnaireBlock) {
            if (!$tplQuestionnaire->getTplBlocks()->contains($tplQuestionnaireBlock->getTplBlock())) {
                $em->remove($tplQuestionnaireBlock);
            }
        }

        // and adding new ones
        foreach ($tplQuestionnaire->getTplBlocks() as $tplBlock) {
            if ($tplBlock->getId() == null) {
                // create  tplBlock
                $em->persist($tplBlock);
                // then, create tplQuestionnaire Block
                $tplQuestionnaireBlock = new TplQuestionnaireBlock();
                $tplQuestionnaireBlock->setTplQuestionnaire($tplQuestionnaire);
                $tplQuestionnaireBlock->setTplBlock($tplBlock);
                $tplQuestionnaireBlock->setPosition($tplBlock->getPosition());
                $em->persist($tplQuestionnaireBlock);
            }
            foreach ($tplQuestionnaire->getTplQuestionnaireBlocks() as $tplQuestionnaireBlock) {
                if ($tplBlock->getId() === $tplQuestionnaireBlock->getTplBlock()->getId()) {
                    // block exists
                    $tplQuestionnaireBlock->setTplBlock($tplBlock);
                    $tplQuestionnaireBlock->setPosition($tplBlock->getPosition());
                }
            }
        }
        return $tplQuestionnaire;

    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {

        if (empty($this->getUser())) {
            return $data;
        }

        switch ($context[$context['operation_type'] . '_operation_name']) {
            case 'get':
                $tplQuestionnaire = $this->getTplQuestionnaire($data);
                break;

            case 'update':
                $tplQuestionnaire = $this->setTplQuestionnaire($data, $context);
                break;

            default:
                $tplQuestionnaire = $data;
        }
        return $tplQuestionnaire;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return TplQuestionnaire::class === $to
            && (($data instanceof TplQuestionnaire && ($context['output']['class'] ?? null) !== null)
                || isset($context['input']['class']));

    }
}