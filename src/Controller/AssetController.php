<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Cms\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AssetController
{
    public function __construct(private readonly FileUploader $fileUploader)
    {
    }

    public function list(): Response
    {
        return new JsonResponse($this->fileUploader->getAssets());
    }

    public function upload(Request $request): Response
    {
        /** @var UploadedFile $file */
        if (null === ($file = $request->files->get('file'))) {
            throw new UnprocessableEntityHttpException('Cannot find filed "file"');
        }

        try {
            $location = $this->fileUploader->upload($file);
        } catch (FileException $exception) {
            throw new UnprocessableEntityHttpException(sprintf('Cannot upload asset: %s', $exception->getMessage()));
        }

        $response = new JsonResponse(['location' => $location], Response::HTTP_CREATED);
        $response->headers->set('Location', $location);
        return $response;
    }
}
