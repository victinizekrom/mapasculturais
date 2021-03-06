<?php
namespace MapasCulturais\Repositories;
use MapasCulturais\Traits;
use MapasCulturais\App;

class EntityRevision extends \MapasCulturais\Repository{
    use Traits\EntityGeoLocation;

    public function findLastRevision($entity) {
        $objectId = $entity->id;
        $objectType = $entity->getClassName();
        $query = $this->_em->createQuery("SELECT e
                                            FROM MapasCulturais\Entities\EntityRevision e
                                            WHERE e.objectId = {$objectId} AND e.objectType = '{$objectType}'
                                            ORDER BY e.id DESC");
        $query->setMaxResults(1);
        return $query->getOneOrNullResult();
    }

    public function findEntityRevisions($entity) {
        $objectId = $entity->id;
        $objectType = $entity->getClassName();
        $query = $this->_em->createQuery("SELECT e
                                            FROM MapasCulturais\Entities\EntityRevision e
                                            WHERE e.objectId = {$objectId} AND e.objectType = '{$objectType}'
                                            ORDER BY e.id DESC");
        return $query->getResult();
    }

    public function findCreateRevisionObject($id) {
        $app = App::i();
        $qryRev = $this->_em->createQuery("SELECT e
                                            FROM MapasCulturais\Entities\EntityRevision e
                                            WHERE e.id = {$id}");
        $qryRev->setMaxResults(1);
        $entityRevision = $qryRev->getOneOrNullResult();
        $actualEntity = $app->repo($entityRevision->objectType)->find($entityRevision->objectId);
        $entityRevisioned = new \stdClass();
        $entityRevisioned->controller_id =  $actualEntity->getControllerId();
        $entityRevisioned->id = $actualEntity->id;
        $entityRevisioned->entityClassName = $entityRevision->objectType;
        $entityRevisioned->userCanView = $actualEntity->canUser('viewPrivateData');
        $entityRevisioned->entity = $actualEntity;

        $registeredMetadata = $app->getRegisteredMetadata($entityRevision->objectType);

        foreach(array_keys($registeredMetadata) as $metadata) {
            $entityRevisioned->$metadata = null;
        }

        foreach($entityRevision->data as $dataRevision) {
            if(!is_array($dataRevision) && !is_object($dataRevision)) {
                $data = $dataRevision;
            } else {
                $data = $dataRevision->value;
            }

            if($dataRevision->key == 'location' && $data->longitude != 0 && $data->latitude !=0) { 
                $entityRevisioned->location = new \MapasCulturais\Types\GeoPoint($data->longitude,$data->latitude);
            } elseif($dataRevision->key == 'createTimestamp' || $dataRevision->key == 'updateTimestamp') {
                $attribute = $dataRevision->key;
                if(isset($data->date)) {
                    $entityRevisioned->$attribute = \DateTime::createFromFormat('Y-m-d H:i:s.u',$data->date);
                }
            } else {
                $attribute = $dataRevision->key;
                $entityRevisioned->$attribute = $data;
            }
        }
        return $entityRevisioned;
    }

    public function findEntityLastRevisionId($classEntity, $entityId) {
        $query = $this->_em->createQuery("SELECT e.id
                                            FROM MapasCulturais\Entities\EntityRevision e
                                            WHERE e.objectId = {$entityId} AND e.objectType = '{$classEntity}'
                                            ORDER BY e.id DESC");

        $query->setMaxResults(1);
        $return = $query->getOneOrNullResult();
        if(is_array($return) && count($return) > 0) {
            $return = $return['id'];
        } else {
            $return = 0;
        }
        return $return;
    }


    /**
     * @param array $objectIds
     * @param string $classEntity
     * @param string $action
     * @param \DateTime $createTimestamp
     * @return object[]
     */
    public function findAllByIdsAndClassAndActionAndTimestamp($objectIds, $classEntity, $action, $createTimestamp) {
        $app = App::i();
        $objectIds = implode(',',$objectIds);
        $query = $this->_em->createQuery("SELECT e
                                                FROM MapasCulturais\Entities\EntityRevision e
                                                WHERE e.objectId IN ({$objectIds}) AND e.objectType = '{$classEntity}'
                                                AND e.action = '{$action}' AND e.createTimestamp = '{$createTimestamp->format('Y-m-d H:i:s')}'");
        $revisionList = $query->getResult();



        $metadata = $app->getRegisteredMetadata($classEntity);
        $metadataKeys = array_keys($metadata);
        $entityList = [];

        foreach ($revisionList as $item) {
            $entity = new \stdClass();
            $entity->revisionId = $item->id;
            $entity->objectId = $item->objectId;

            foreach( $metadataKeys as $key) {
                $entity->$key = null;
            }

            foreach($item->data as $revisionData) {
                if(!is_array($revisionData) && !is_object($revisionData)) {
                    $data = $revisionData;
                } else {
                    $data = $revisionData->value;
                }

                $attribute = $revisionData->key;

                switch ($attribute) {
                    case  'location':
                        $entity->location = ($revisionData->longitude != 0 && $revisionData->latitude !=0) ? new \MapasCulturais\Types\GeoPoint($data->longitude,$data->latitude) : null;
                        break;
                    case  'createTimestamp':
                        $entity->createTimestamp = \DateTime::createFromFormat('Y-m-d H:i:s.u',$data->date);
                        break;
                    case  'updateTimestamp':
                        $entity->updateTimestamp = \DateTime::createFromFormat('Y-m-d H:i:s.u',$data->date);
                        break;
                    default:
                        $entity->$attribute = $data;
                        break;
                }
            }

            $entityList[$entity->objectId] = $entity;
        }

        return $entityList;
    }
}
