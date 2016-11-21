<?php

namespace Glavweb\UploaderDropzoneBundle\EventListener;


use Doctrine\Common\Persistence\ObjectManager;
use Glavweb\UploaderBundle\Entity\Media;
use Glavweb\UploaderBundle\Model\OrmModelManager;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Event\PreUploadEvent;

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
     * @var Media
     */
    private $media;

    
    public function __construct(ObjectManager $om, OrmModelManager $modelManager)
    {
        $this->om = $om;
        $this->modelManager = $modelManager;
    }

    public function preUpload(PreUploadEvent $event) {
        $processedFile = $event->getFile();

        /* Zapamiętujemy te info w preUpload, bo w postUpdate się wywala */
        $this->media = $this->modelManager->createMedia();
        $this->media->setName($processedFile->getClientOriginalName());
        $this->media->setContentType($processedFile->getMimeType());
        $this->media->setContentSize($processedFile->getSize());
    }
        
    public function postUpload(PostUploadEvent $event)
    {
        $uploadedFile = $event->getFile();
        $contentPath = basename($uploadedFile->getPathname());

        $this->media->setContext($event->getType());
        $this->media->setProviderName($event->getType());
        $this->media->setContentPath($contentPath);
        $this->media->setThumbnailPath($contentPath);
        $this->media->setIsOrphan(true);
        $this->media->setRequestId($event->getRequest()->get('_glavweb_uploader_request_id'));
        $this->modelManager->updateMedia($this->media, true);

        $response = $event->getResponse();
        $response['id']          = $this->media->getId();
        $response['contentPath'] = '';
        $this->media = null;
    }

}