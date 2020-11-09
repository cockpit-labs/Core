<?php
/*
 * Core
 * APIMediaTest.php
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

namespace App\Tests\API;


use App\Entity\Media\UserMedia;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once('_ApiTest.php');

class APIMediaTest extends ApiTest
{
    /**
     * @group Default
     */
    public function testCreateMedia()
    {
        $imageFile = __DIR__ . '/GrumpyBear.png';
        $this->setViewClient()->setNormalUser();
        $this->preTest();

//        $response=$this->doGetRequest(Folder::class);
        $response = $this->doGetRequest(UserMedia::class);

        $uploadedFile = new UploadedFile(
            $imageFile,
            'GrumpyBear.'
        );

        $file     = ['foo' => $uploadedFile];
        $response = $this->doUploadFileRequest(UserMedia::class, $file);
        $this->assertResponseStatusCodeSame(400);

        $file     = ['file' => $uploadedFile];
        $response = $this->doUploadFileRequest(UserMedia::class, $file);
        $this->assertResponseStatusCodeSame(201);

        $this->assertMatchesResourceCollectionJsonSchema(UserMedia::class);
        $this->assertNotEmpty(json_decode($response->getContent()));

        $imageResponse = json_decode($response->getContent(), true);
        $imageId       = $imageResponse['id'];

        $response = $this->doGetRequest(UserMedia::class, $imageId);
        $this->assertMatchesResourceCollectionJsonSchema(UserMedia::class);
        $this->assertNotEmpty(json_decode($response->getContent()));

        // get image to compare with original
        $response = $this->doGetSubresourceRequest(UserMedia::class, $imageId, 'content');
        $this->assertEquals(file_get_contents($imageFile), $response->getContent());

        $response = $this->doGetRequest(UserMedia::class, "not-a-good-uuid");
        $this->assertResponseStatusCodeSame(404);
        $response = $this->doGetSubresourceRequest(UserMedia::class, "not-a-good-uuid", 'content');
        $this->assertResponseStatusCodeSame(404);


        $this->setAdminClient()->setAdminUser();
        $response = $this->doDeleteRequest(UserMedia::class, $imageId);
        $this->assertResponseStatusCodeSame(204);

    }
}
