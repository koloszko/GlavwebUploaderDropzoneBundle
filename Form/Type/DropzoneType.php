<?php

namespace Glavweb\UploaderDropzoneBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Glavweb\UploaderBundle\Driver\AnnotationDriver;
use Glavweb\UploaderBundle\Exception\ClassNotUploadableException;
use Glavweb\UploaderBundle\Exception\MappingNotSetException;
use Glavweb\UploaderBundle\Exception\NotFoundPropertiesInAnnotationException;
use Glavweb\UploaderBundle\Exception\ValueEmptyException;
use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DropzoneType
 *
 * @package Glavweb\UploaderDropzoneBundle\Form\Type
 */
class DropzoneType extends AbstractType
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var UploaderHelper
     */
    protected $uploaderHelper;

    /**
     * @var AnnotationDriver;
     */
    protected $driverAnnotation;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Router $router
     * @param array $config
     * @param AnnotationDriver $driverAnnotation
     */
    public function __construct(Router $router, UploaderHelper $uploaderHelper, array $config, AnnotationDriver $driverAnnotation, TranslatorInterface $translator)
    {
        $this->router           = $router;
        $this->uploaderHelper   = $uploaderHelper;
        $this->config           = $config;
        $this->driverAnnotation = $driverAnnotation;
        $this->translator       = $translator;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     * @throws MappingNotSetException
     * @throws NotFoundPropertiesInAnnotationException
     * @throws ClassNotUploadableException
     * @throws ValueEmptyException
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $entity = $form->getParent()->getData();
        $fieldName = $form->getConfig()->getName();

        if (!isset($options['requestId'])) {
            $options['requestId'] = uniqid();
        }

        $dataPropertyAnnotation = $this->driverAnnotation->getDataByFieldName(new \ReflectionClass($entity), $fieldName);

        if (!$dataPropertyAnnotation) {
            throw new NotFoundPropertiesInAnnotationException();
        }

        $files = $entity->{$dataPropertyAnnotation['nameGetFunction']}();
        if (!$files instanceof Collection) {
            $fakeFiles = new ArrayCollection();
            if ($files) {
                $fakeFiles->add($files);
            }
            $files = $fakeFiles;
        }
        $context = $dataPropertyAnnotation['mapping'];

        if (!$context) {
            throw new MappingNotSetException();
        }
        $config = $this->getConfigByContext($context);

        $router    = $this->router;
        $urls      = array(
            'upload' => $this->uploaderHelper->endpoint($context),
            'rename' => $router->generate('glavweb_uploader_rename', array('context' => $context)),
            'delete' => $router->generate('glavweb_uploader_delete', array('context' => $context)),
        );

        $view->vars['requestId']        = $options['requestId'];
        $view->vars['views']            = $options['views' ];
        $view->vars['type']             = $context;
        $view->vars['files']            = $files;
        $view->vars['previewShow']      = array_merge($options['previewShowDefault'],$options['previewShow']);

        // Dropzone
        $view->vars['dropzoneOptions'] = array_merge($options['dropzoneOptionsDefault'], array(
            'url'               => $urls['upload'],
            'previewTemplate'   => '#js-gwu-template_' . $options['requestId'],
            'previewsContainer' => '#js-gwu-previews_' . $options['requestId'],
            'form'              => '.js-gwu-from_' . $options['requestId'],
            'link'              => '.js-gwu-link_' . $options['requestId'],
            'maxFilesize'       => $config['max_size'],
            'clickable'         => '.js-gwu-clickable_' . $options['requestId'],
        ),$options['dropzoneOptions']);

        // Dropzone
        $view->vars['uploaderOptions'] = array_merge($options['uploaderOptionsDefault'], array(
            'urls'              => $urls,
            'requestId'         => $options['requestId'],
            'dropzoneContainer' => '#js-gwu-dropzone_' . $options['requestId'],
            'previewShow'       => $view->vars['previewShow'],
            'countFiles'        => $files->count(),
            'maxFiles'          => $view->vars['dropzoneOptions']['maxFiles'],
            'type'              => $context,
            'clickable'         => '.js-gwu-clickable_' . $options['requestId'],
        ), $options['uploaderOptions']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $translator = $this->translator;

        $resolver->setDefaults(array(
            'previewImg'         => null,
            'requestId'          => null,
            'useLink'            => true,
            'useForm'            => true,
            'showMark'           => true,
            'showUploadButton'   => true,
            'showLabel'          => true,
            'thumbnailOptions'   => array(
                'width'     => 200,
                'height'    => 200,
            ),
            'views'              => array(
//                'form' => 'path/to/view',
//                'link' => 'path/to/view',
//                'preview' => 'path/to/view',
            ),
            'previewShow'        => array(),
            'previewShowDefault' => array(
                'filename'        => '.js-gwu-form-name',
                'fileDescription' => '.js-gwu-form-description',
                'filenameLabel'        => 'Nazwa',
                'fileDescriptionLabel' => 'Оpis',
                'isDetails'  => true,
                'isSize'     => true,
                'isFilename' => true,
                'isFileDescription' => false,
                'isProgress' => true,
                'isError'    => true,
                'isShowMark' => true
            ),
            'uploaderOptions'    => array(),
            'uploaderOptionsDefault' => array(
                'type'             => null,
                'uploaderClass'    => '',
                'formViewType'     => 'form',
                'previewViewType'  => 'image',
                'preloader'        => '.js-gwu-preloader',
                'upoloaderError'   => '.js-gwu-error',
                'previewContainer' => '.js-gwu-preview',
                'rename'           => '.js-gwu-rename',
                'saveRename'        => '.js-gwu-save-rename',
                'filename'         => '.js-gwu-filename',
                'description'      => '.js-gwu-description',
                'form'             => '.js-gwu-form',
                'link'             => '.js-gwu-link',
                'popup'            => '.js-gwu-popup',
                'isPopup'          => true,
                'isName'           => true,
                'isDescription'    => false,
                'isSort'           => false,
                'isShowErrorPopup' => false,
                'isThumbnail'      => true,
                'isUploadButton'   => true,
                'thumbnailOptions' => array(),
                'countFiles'       => 0,
                'enableRename'     => false,
                'underLabel'       => false,
            ),
            'dropzoneOptions'    => array(),
            'dropzoneOptionsDefault' => array(
                'url'                          => null,
                'previewTemplate'              => null,
                'previewsContainer'            => null,
                'clickable'                    => null,
                'maxFilesize'                  => 2,
                'maxFiles'                     => 1,
                'thumbnailWidth'               => 350,
                'thumbnailHeight'              => 350,
                'parallelUploads'              => 20,
                'autoQueue'                    => true,
                'autoProcessQueue'             => true,
                'acceptedFiles'                => '.png, .jpg',
                'dictDefaultMessage'           => $translator->trans('dropzone.files_uploaded'),
                'dictFallbackMessage'          => $translator->trans('dropzone.browser_not_support_drag_n_drop'),
                'dictFileTooBig'               => $translator->trans('dropzone.file_size_too_large'),
                'dictInvalidFileType'          => $translator->trans('dropzone.wrong_format'),
                'dictResponseError'            => $translator->trans('dropzone.disable_adblocker'),
                'dictCancelUpload'             => $translator->trans( 'dropzone.cancel_upload'),
                'dictCancelUploadConfirmation' => $translator->trans('dropzone.cancel_upload_confirmation'),
                'dictRemoveFile'               => $translator->trans('dropzone.remove_file'),
                'dictRemoveFileConfirmation'   => null,
                'dictMaxFilesExceeded'         => $translator->trans('dropzone.max_files_exceeded')
            ),
        ));
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return FormType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'glavweb_uploader_dropzone';
    }

    /**
     * @param string $context
     * @return array
     */
    protected function getConfigByContext($context)
    {
        return $this->config['mappings'][$context];
    }
}