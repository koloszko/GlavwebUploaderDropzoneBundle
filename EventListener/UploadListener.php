<?php

namespace Glavweb\UploaderDropzoneBundle\EventListener;


use Doctrine\Common\Persistence\ObjectManager;
use Glavweb\UploaderBundle\Model\OrmModelManager;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Event\PreUploadEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadListener
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var OrmModelManager
     */
    private $modelManager;

    /**
     * @var UploadedFile
     */
    private $processedFile;
    private $processedFileMimeType;
    
    public function __construct(ObjectManager $om, OrmModelManager $modelManager)
    {
        $this->om = $om;
        $this->modelManager = $modelManager;
    }

    public function preUpload(PreUploadEvent $event) {
        $this->processedFile = $event->getFile();
        /* Zapamiętujemy te info w preUpload, bo w postUpdate się wywala */
        $this->processedFileMimeType = $this->processedFile->getMimeType();
    }
        
    public function postUpload(PostUploadEvent $event)
    {
        $uploadedFile = $event->getFile();
        $contentPath = basename($uploadedFile->getPathname());

        $media = $this->modelManager->createMedia();
        $media->setContext($event->getType());
        $media->setProviderName($event->getType());
        $media->setContentPath($contentPath);
        $media->setThumbnailPath($contentPath);
        $media->setName($this->processedFile->getClientOriginalName());
        $media->setContentType($this->processedFileMimeType);
        $media->setContentSize($this->processedFile->getSize());
        $media->setIsOrphan(true);
        $media->setRequestId($event->getRequest()->get('_glavweb_uploader_request_id'));
        $this->modelManager->updateMedia($media, true);

        $this->processedFile = null;
        $response = $event->getResponse();
        $response['id']          = $media->getId();
        $response['contentPath'] = '';
    }

}