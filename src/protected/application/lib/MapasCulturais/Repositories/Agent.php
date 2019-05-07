<?php
namespace MapasCulturais\Repositories;
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
}

