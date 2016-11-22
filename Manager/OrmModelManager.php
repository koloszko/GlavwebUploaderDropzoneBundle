<?php
/**
 * Created by PhpStorm.
 * User: pkoloszko
 * Date: 18.11.2016
 * Time: 15:40
 */

namespace Glavweb\UploaderDropzoneBundle\Manager;


class OrmModelManager extends \Glavweb\UploaderBundle\Model\OrmModelManager
{
    public function removeMedia($entity, $context, $requestId, $andFlush = true)
    {
        $em = $this->doctrine->getManager();
        $repositoryMediaMarkRemove = $em->getRepository('GlavwebUploaderBundle:MediaMarkRemove');

        $rows = $repositoryMediaMarkRemove->findBy(array(
            'requestId' => $requestId
        ));

        $changesAffected = false;
        foreach ($rows as $row) {
            if ($row && $row->getMedia()->getContext() == $context) {
                $media = $row->getMedia();
                $em->remove($media);
                $changesAffected = true;
            }
        }

        if ($changesAffected) {
            $em->detach($entity);
            if ($andFlush) {
                $em->flush();
            }
        }
    }
}