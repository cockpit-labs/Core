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

namespace App\Controller;

use League\Flysystem\FilesystemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * @var bool
     */
    private $download = false;

    /**
     * @var string
     */
    private $id = '';

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
        if(empty($this->getId())) {
            $this->setId($request->get('id'));
        }
        $media = $this->getDoctrine()
                      ->getRepository($this->mediaClass)
                      ->find($this->getId());

        if (!$media) {
            throw $this->createNotFoundException(
                'No media found for id ' . $this->getId()
            );
        }
        $filename = $media->getFileName();
        $response = null;
        switch ($this->filesystem->getMimetype($filename)) {
            case 'application/pdf':
                $response = $this->pdfContent($filename);
                break;

            default:
                $response = $this->defaultContent($filename);
                break;
        }
        return $response;
    }

    /**
     * @param string $filename
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function defaultContent(string $filename): Response
    {
        $fileContent = $this->filesystem->read($filename);
        $response    = new Response($fileContent);
        $response->headers->set('Content-Type', $this->filesystem->getMimetype($filename));
        return $response;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isDownload(): bool
    {
        return $this->download;
    }

    /**
     * @param string $filename
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function pdfContent(string $filename): Response
    {
        $fs  = $this->filesystem;
        $response    = new StreamedResponse(function () use ($fs, $filename) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream   = $fs->readStream($filename);
            stream_copy_to_stream($fileStream, $outputStream);
        });
        $disposition = HeaderUtils::makeDisposition(
            $this->isDownload()?HeaderUtils::DISPOSITION_ATTACHMENT:HeaderUtils::DISPOSITION_INLINE,
            $filename
        );
        $response->headers->set('Content-Type', $this->filesystem->getMimetype($filename));
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Size', $this->filesystem->getSize($filename));
        return $response;

    }

    /**
     * @param bool $download
     */
    public function setDownload(bool $download): void
    {
        $this->download = $download;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string $mediaClass
     */
    public function setMediaClass(string $mediaClass): void
    {
        $this->mediaClass = $mediaClass;
    }
}
