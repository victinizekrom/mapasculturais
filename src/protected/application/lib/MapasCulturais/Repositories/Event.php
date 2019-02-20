<?php
namespace MapasCulturais\Repositories;
use MapasCulturais\Traits;
use MapasCulturais\App;

class Event extends \MapasCulturais\Repository{
    use Traits\RepositoryKeyword,
        Traits\RepositoryAgentRelation;

    protected function _getCurrentSubsiteSpaceIds($implode = true){
        $app = App::i();
        if($app->getCurrentSubsiteId()){
            $space_ids = $app->repo('Space')->getCurrentSubsiteSpaceIds($implode);
        } else {
            $space_ids = "SELECT id FROM space WHERE status > 0";
        }

        return $space_ids;
    }

    public function findByEntity($entity, $date_from = null, $date_to = null, $limit = null, $offset = null, $order = 'ASC'){
        $events = array();
        $class = $entity->className;

        switch ($class) {
            case 'MapasCulturais\Entities\Space':
                $events = !$entity->id ? array() : $this->findBySpace($entity, $date_from, $date_to, $limit, $offset, $order);
                break;
            case 'MapasCulturais\Entities\Registration':
                $events = !$entity->owner->id ? array() : $app->repo('Event')->findByAgent($entity->owner, $date_from, $date_to, $limit, $offset, $order);
                break;
            case 'MapasCulturais\Entities\Agent':
                $events = !$entity->id ? array() : $app->repo('Event')->findByAgent($entity, $date_from, $date_to, $limit, $offset, $order);
                break;
            case 'MapasCulturais\Entities\Project':
                $events = !$entity->id ? array() : $app->repo('Event')->findByProject($entity, $date_from, $date_to, $limit, $offset, $order);
                break;
            default:
                break;
        }

        return $events;
    }

    public function findBySpace($space, $date_from = null, $date_to = null, $limit = null, $offset = null, $order = 'ASC'){

        $app = App::i();

        if($space instanceof \MapasCulturais\Entities\Space){
            $ids = $space->getChildrenIds();
            $ids[] = $space->id;

        }elseif($space && is_array($space) && is_object($space[0]) ){
            $ids = [-1];
            foreach($space as $s)
                if(is_object($s) && $s instanceof \MapasCulturais\Entities\Space && $s->status > 0)
                    $ids[] = $s->id;

        }elseif($space && is_array($space) && is_numeric ($space[0])){
            $ids = $space;

        }else{
            $ids = '0';
        }

        if(is_array($ids) && $app->getCurrentSubsiteId()){
            $space_ids = $this->_getCurrentSubsiteSpaceIds(false);
            $ids = array_intersect($ids, $space_ids);

        }

        if(is_null($date_from))
            $date_from = date('Y-m-d');
        else if($date_from instanceof \DateTime)
            $date_from = $date_from->format('Y-m-d');

        if(is_null($date_to))
            $date_to = $date_from;
        else if($date_to instanceof \DateTime)
            $date_to = $date_to->format('Y-m-d');
            
        $dql_limit = $dql_offset = '';

        if(!is_null($limit) && !is_null($offset)){
            $dql_limit = 'LIMIT :limit';
            $dql_offset = 'OFFSET :offset';
        }
      
        if(is_array($ids)){
            $ids = implode(',', $ids);
        }
        
        $sql = "
            SELECT
                e.id
            FROM
                event e
            JOIN
                event_occurrence eo
                    ON eo.event_id = e.id
                        AND eo.space_id IN ($ids)
                        AND eo.status > 0

            WHERE
                e.status > 0 AND
                e.id IN (
                    SELECT
                        event_id
                    FROM
                        recurring_event_occurrence_for(:date_from, :date_to, 'Etc/UTC', NULL)
                    WHERE
                        space_id IN ($ids)
                )
            
            ORDER BY
                eo.starts_on $order, eo.starts_at
                
            $dql_limit $dql_offset";

        $params = [
            'date_from' => $date_from,
            'date_to' => $date_to
        ];


        $result = $this->_getEventsBySQL($sql, $params, $limit, $offset);

        return $result;
    }

    public function findByProject($project, $date_from = null, $date_to = null, $limit = null, $offset = null, $order = 'ASC'){

        if($project instanceof \MapasCulturais\Entities\Project){
            $ids = $project->getChildrenIds();
            $ids[] = $project->id;

        }elseif($project && is_array($project) && is_object($project[0]) ){
            $ids = [-1];
            foreach($project as $s)
                if(is_object($s) && $s instanceof \MapasCulturais\Entities\Project && $s->status > 0)
                    $ids[] = $s->id;

        }elseif($project && is_array($project) && is_numeric ($project[0])){
            $ids = $project;

        }else{
            $ids = '0';
        }

        if(is_null($date_from)){
            $date_from = date('Y-m-d');
        }else if($date_from instanceof \DateTime){
            $date_from = $date_from->format('Y-m-d');
        }

        if(is_null($date_to)){
            $date_to = $date_from;
        }else if($date_to instanceof \DateTime){
            $date_to = $date_to->format('Y-m-d');
        }

        $dql_limit = $dql_offset = '';

        if(!is_null($limit) && !is_null($offset)){
            $dql_limit = 'LIMIT :limit';
            $dql_offset = 'OFFSET :offset';
        }

        $space_ids = $this->_getCurrentSubsiteSpaceIds();
        
        if(is_array($ids)){
            $ids = implode(',', $ids);
        }

        $sql = "
            SELECT
                e.id
            FROM
                event e
            JOIN
                event_occurrence eo
                    ON eo.event_id = e.id
                        AND eo.space_id IN ($space_ids)
                        AND eo.status > 0
            WHERE
                e.status > 0 AND
                e.project_id IN ($ids) AND
                e.id IN (
                    SELECT
                        event_id
                    FROM
                        recurring_event_occurrence_for(:date_from, :date_to, 'Etc/UTC', NULL)
                    WHERE
                        space_id IN ($space_ids)
                )
            
            ORDER BY
                eo.starts_on $order, eo.starts_at

            $dql_limit $dql_offset";

        $params = [
            'date_from' => $date_from,
            'date_to' => $date_to
        ];

        $result = $this->_getEventsBySQL($sql, $params, $limit, $offset);
        
        return $result;
    }


    public function findByAgent(\MapasCulturais\Entities\Agent $agent, $date_from = null, $date_to = null, $limit = null, $offset = null, $order = 'ASC'){
        
        if(is_null($date_from)){
            $date_from = date('Y-m-d');
        }else if($date_from instanceof \DateTime){
            $date_from = $date_from->format('Y-m-d');
        }

        if(is_null($date_to)){
            $date_to = $date_from;
        }else if($date_to instanceof \DateTime){
            $date_to = $date_to->format('Y-m-d');
        }

        $dql_limit = $dql_offset = '';

        if(!is_null($limit) && !is_null($offset)){
            $dql_limit = 'LIMIT :limit';
            $dql_offset = 'OFFSET :offset';
        }

        $space_ids = $this->_getCurrentSubsiteSpaceIds();

        $sql = "
            SELECT
                e.id
            FROM
                event e
            JOIN
                event_occurrence eo
                    ON eo.event_id = e.id
                        AND eo.space_id IN ($space_ids)
                        AND eo.status > 0

            WHERE
                e.status > 0 AND (
                    e.id IN(
                        SELECT
                            object_id
                        FROM
                            agent_relation
                        WHERE
                            object_type = 'MapasCulturais\Entities\Event' AND
                            agent_id = :agent_id
                    ) OR

                    e.agent_id = :agent_id
                ) AND
                e.id IN (
                    SELECT
                        event_id
                    FROM
                        recurring_event_occurrence_for(:date_from, :date_to, 'Etc/UTC', NULL)
                    WHERE
                        space_id IN ($space_ids)
                )
            
            ORDER BY
                eo.starts_on $order, eo.starts_at
                
            $dql_limit $dql_offset";

        $params = [
            'date_from' => $date_from,
            'date_to' => $date_to,
            'agent_id' => $agent->id
        ];

        $result = $this->_getEventsBySQL($sql, $params, $limit, $offset);

        return $result;
    }


    public function findByDateInterval($date_from = null, $date_to = null, $limit = null, $offset = null, $only_ids = false, $order = 'ASC'){
                
        if(is_null($date_from)){
            $date_from = date('Y-m-d');
        } else if($date_from instanceof \DateTime){
            $date_from = $date_from->format('Y-m-d');
        }

        if(is_null($date_to)){
            $date_to = $date_from;
        }else if($date_to instanceof \DateTime){
            $date_to = $date_to->format('Y-m-d');
        }

        $dql_limit = $dql_offset = '';

        if(!is_null($limit) && !is_null($offset)){
            $dql_limit = 'LIMIT :limit';
            $dql_offset = 'OFFSET :offset';
        }
        
        $space_ids = $this->_getCurrentSubsiteSpaceIds();

        $sql = "
            SELECT
                e.id
            FROM
                event e
            JOIN
                event_occurrence eo
                    ON eo.event_id = e.id
                        AND eo.space_id IN ($space_ids)
                        AND eo.status > 0

            WHERE
                e.status > 0 AND
                e.id IN (
                    SELECT
                        event_id
                    FROM
                        recurring_event_occurrence_for(:date_from, :date_to, 'Etc/UTC', NULL)
                    WHERE
                        space_id IN ($space_ids)
                )

            ORDER BY
                eo.starts_on $order, eo.starts_at
            
            $dql_limit $dql_offset";

        $params = ['date_from' => $date_from, 'date_to' => $date_to];
        
        if($only_ids){
            $result = $this->_getIdsBySQL($sql, $params, $limit, $offset);
        }else{
            $result = $this->_getEventsBySQL($sql, $params, $limit, $offset);
        }

        return $result;
    }
    
    function _getEventsBySQL($sql, $params = [], $limit = null, $offset = null){
        $ids = $this->_getIdsBySQL($sql, $params, $limit, $offset);
        $events = $this->_getEventsByIds($ids);
        
        return $events;
    }
    
    function _getIdsBySQL($sql, $params = [], $limit = null, $offset = null){
        $connection = $this->_em->getConnection();
        $statement = $connection->prepare($sql);   
        
        if(!empty($params)){
            foreach($params as $key => $value){
                $statement->bindValue($key, $value, \PDO::PARAM_STR);
            }
        }

        if(!is_null($limit) && !is_null($offset)){
            $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
            $statement->bindValue('offset', $offset, \PDO::PARAM_INT);
        }

        $statement->execute();        
        $result = $statement->fetchAll();
        
        $ids = array_map(function($e){ return $e['id']; }, $result);
        
        return $ids;
    }

    function _getEventsByIds($ids){
        if(!$ids){
            return [];
        }
        
        $dql = "SELECT e
                FROM MapasCulturais\Entities\Event e 
                WHERE e.id IN(:ids) ";

        $q = $this->_em->createQuery($dql);
        $q->setParameter('ids', $ids);
        
        $result = $q->getResult();
        
        return $result;
    }
}
