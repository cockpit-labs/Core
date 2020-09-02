<?php
/*
 * Core
 * GetMediaContentAction.php
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

namespace App\Controller;

use League\Flysystem\FilesystemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class GetMediaContentAction extends AbstractController
{
    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var string
     */
    private $mediaClass = "";

    /**
     * GetMediaContentAction constructor.
     *
     * @param \League\Flysystem\FilesystemInterface $mediafsFilesystem
     */
    public function __construct(FilesystemInterface $mediafsFilesystem)
    {
        $this->filesystem = $mediafsFilesystem;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function __invoke(Request $request): Response
    {
        $id = $request->get('id');

        $media = $this->getDoctrine()
                      ->getRepository($this->mediaClass)
                      ->find($id);

        if (!$media) {
            throw $this->createNotFoundException(
                'No media found for id ' . $id
            );
        }
        $fileContent = $this->filesystem->read($media->getFileName());
        $response    = new Response($fileContent);
        $response->headers->set('Content-Type', $this->filesystem->getMimetype($media->getFileName()));
        return $response;
    }

    /**
     * @param string $mediaClass
     */
    public function setMediaClass(string $mediaClass): void
    {
        $this->mediaClass = $mediaClass;
    }
}
