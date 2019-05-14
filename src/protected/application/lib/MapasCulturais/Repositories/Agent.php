<?php
namespace MapasCulturais\Repositories;
use \MapasCulturais\App;
use MapasCulturais\Traits;

class Agent extends \MapasCulturais\Repository{
    use Traits\RepositoryKeyword,
        Traits\RepositoryAgentRelation;

        /**
         * @param \MapasCulturais\Entities\User $user
         * @return int
         */
        function countByUser(\MapasCulturais\Entities\User $user){
               
            $dql = "SELECT COUNT(a.id) FROM {$this->getClassName()} a WHERE a.user = :user";    
            $q = $this->_em->createQuery($dql);    
            $q->setParameter('user', $user);    
            $num = $q->getSingleScalarResult();
    
            return $num;
        }

        /**
         * Return agents by metadata fields
         * @param string $key
         * @param string $value
         * @return MapasCulturais\Entities\Agent
         */
        public function findByMetadata($key, $value) {
            $entityClass = $this->getClassName();
            $app = App::i();
    
            $dql = "SELECT m,a
                    FROM {$entityClass}Meta m
                    JOIN m.owner a
                    WHERE m.key = :key AND m.value = :value ";
    
            $query = $app->em->createQuery($dql);
    
            $query->setParameter('key', $key);
            $query->setParameter('value', $value);
    
            $entityList = $query->getResult();
            $list = [];
            foreach($entityList as $item) {
                $list[] = $item->owner;
            }
            return $list;
        }        
}