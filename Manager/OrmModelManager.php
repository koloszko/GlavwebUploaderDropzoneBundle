<?php
/**
 * Created by PhpStorm.
 * User: pkoloszko
 * Date: 18.11.2016
 * Time: 15:40
 */

namespace Glavweb\UploaderDropzoneBundle\Manager;


use Doctrine\Common\Collections\Collection;

class OrmModelManager extends \Glavweb\UploaderBundle\Model\OrmModelManager
{
    public function removeMedia($entity, $context, $requestId, $property)
    {
        $em = $this->doctrine->getManager();
        $repositoryMediaMarkRemove = $em->getRepository('GlavwebUploaderBundle:MediaMarkRemove');

        $rows = $repositoryMediaMarkRemove->findBy(array(
            'requestId' => $requestId
        ));

        $nameGetFunction = $property['nameGetFunction'];
        $entityMedia = $entity->$nameGetFunction();

        $changesAffected = false;
        foreach ($rows as $row) {
            if ($row && $row->getMedia()->getContext() == $context) {
                $media = $row->getMedia();
                if (!$entityMedia instanceof Collection && !is_null($entityMedia)) {
                    $nameAddFunction = $property['nameAddFunction'];

                    $entity->$nameAddFunction(null);
                    $em->remove($row);
                    break;
                } else {
                    $changesAffected = true;
                    $em->remove($media);
                }
            }
        }

        if ($changesAffected) {
            $em->detach($entity);
            $em->flush();
        }
    }
}