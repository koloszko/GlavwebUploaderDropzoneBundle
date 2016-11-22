<?php
/**
 * Created by PhpStorm.
 * User: pkoloszko
 * Date: 18.11.2016
 * Time: 13:13
 */

namespace Glavweb\UploaderDropzoneBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Glavweb\UploaderBundle\Exception\MappingNotSetException;
use Glavweb\UploaderBundle\Manager\UploaderManager as BaseUploaderManager;
use Glavweb\UploaderBundle\Model\MediaInterface;
use Glavweb\UploaderDropzoneBundle\Exception\TooManyFilesUploadedException;

class UploaderManager extends BaseUploaderManager
{
    const DEFAULT_MAPPING = 'default';

    protected $configGlavwebUploader;

    public function __construct(array $config, array $configGlavwebUploader)
    {
        parent::__construct($config);
        $this->configGlavwebUploader = $configGlavwebUploader;
    }

    /**
     * @param \Glavweb\UploaderBundle\Model\ModelManagerInterface $modelManager
     */
    public function setModelManager($modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function handleUpload($entity, $requestId = null, $options = array())
    {
        $request = $this->getRequestStack()->getCurrentRequest();
        $requestId = $request->get('_glavweb_uploader_request_id');

        if (!$requestId) {
            return;
        }

        $driverAnnotation = $this->getDriverAnnotation();
        $data = $driverAnnotation->loadDataForClass(new \ReflectionClass($entity));

        foreach ($data as $property) {
            $context = $property['mapping'];

            $this->removeMedia($entity, $context, $requestId, $property);
            $this->renameMarkedMedia($context, $requestId);
            $uploadedMediaEntities = $this->uploadOrphans($context, $requestId);

            $this->addMedia($uploadedMediaEntities, $entity, $property);
            
            $mediaEntities = $entity->{$property['nameGetFunction']}();
            if ($mediaEntities instanceof Collection) {
                $positions = explode(',', $request->get('_glavweb_uploader_sorted_array')[$context]);
                $this->sortMedia($mediaEntities, $positions);
            }
        }
    }

    /**
     * @param $mediaEntities
     * @param $entity
     * @param $property
     * @throws MappingNotSetException
     * @throws TooManyFilesUploadedException
     * @internal param $property 
     */
    protected function addMedia($mediaEntities, $entity, $property)
    {
        $nameAddFunction = $property['nameAddFunction'];
        $nameGetFunction = $property['nameGetFunction'];
        $mapping         = $property['mapping'];

        $mappingConfig = $this->configGlavwebUploader['mappings'];
        if (!array_key_exists($mapping, $mappingConfig)) {
            if (!array_key_exists(self::DEFAULT_MAPPING, $mappingConfig)) {
                throw new MappingNotSetException();
            } else {
                $mapping = self::DEFAULT_MAPPING;
            }
        }
        $contextConfig = $this->configGlavwebUploader['mappings'][$mapping];
        $maxFiles = $contextConfig['max_files'];

        $entityMedia = $entity->$nameGetFunction();

        if ($entityMedia instanceof Collection) {
            $maxFiles = $maxFiles - $entityMedia->count();
            if (!empty($mediaEntities)) {
                foreach ($mediaEntities as $mediaEntity) {
                    if ($maxFiles == 0) {
                        break;
                    }
                    $entity->$nameAddFunction($mediaEntity);
                    $maxFiles--;
                }
            }
        } else {
            if (count($mediaEntities) > 1) {
                throw new TooManyFilesUploadedException();
            }
            if (count($mediaEntities) === 1) {
                $entity->$nameAddFunction($mediaEntities[0]);
            }
        }

    }

    public function removeMediaFromStorage(MediaInterface $media)
    {
        $context = $media->getContext();
        $storageConfig = $this->getContextConfig($context, 'storage');
        if ($storageConfig['type'] == 'filesystem') {
            $storage = $this->container->get('glavweb_uploader.storage.filesystem');
            $directory = $storageConfig['directory'];
            $files = array();
            if (($contentPath = $media->getContentPath())) {
                if ($storage->isFile($directory, $contentPath)) {
                    $files[] = $storage->getFile($directory, $contentPath);
                }
            }

            if (($thumbnailPath = $media->getThumbnailPath()) && $thumbnailPath != $contentPath) {
                if ($storage->isFile($directory, $contentPath)) {
                    $files[] = $storage->getFile($directory, $thumbnailPath);
                }
            }

            foreach ($files as $file) {
                $storage->removeFile($file);
            }
        } elseif ($storageConfig['type'] == 'flysystem') {
            $filesystem = $this->container->get($storageConfig['filesystem']);
            $filesystem->delete($media->getContentPath());
        }
    }

    public function removeMedia($entity, $context, $requestId, $property)
    {
        $this->modelManager->removeMedia($entity, $context, $requestId, $property);
    }
}