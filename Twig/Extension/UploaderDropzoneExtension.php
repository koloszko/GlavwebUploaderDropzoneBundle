<?php

namespace Glavweb\UploaderDropzoneBundle\Twig\Extension;

use Glavweb\UploaderBundle\Model\MediaInterface;


class UploaderDropzoneExtension extends \Twig_Extension
{

    /**
     * @var \Glavweb\UploaderBundle\Helper\MediaHelper
     */
    protected $mediaHelper;

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'glavweb_dropzone_uploader_extension';
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('dropzone_thumbnail', array($this, 'thumbnail')),
            new \Twig_SimpleFilter('dropzone_image', array($this, 'contentPath'))
        );
    }

    /**
     * @param MediaInterface $media
     * @param bool           $isAbsolute
     * @return string
     */
    public function thumbnail(MediaInterface $media)
    {
        $thumbnailPath = $media->getThumbnailPath();
        if ($thumbnailPath) {
            $context = $media->getContext();
            return $context . DIRECTORY_SEPARATOR . $thumbnailPath;
        }

        return null;
    }

    /**
     * @param MediaInterface $media
     * @return string
     */
    public function contentPath(MediaInterface $media)
    {
        $contentPath = $media->getContentPath();
        if ($contentPath) {
            $context = $media->getContext();
            return $context . DIRECTORY_SEPARATOR . $contentPath;
        }

        return null;
    }
}