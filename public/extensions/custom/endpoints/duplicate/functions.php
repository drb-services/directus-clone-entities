<?php
use Directus\Services\ItemsService;

if (!function_exists('start')) {
    /**
     * @return array
     */
    function start($request)
    {
        $container = \Directus\Application\Application::getInstance()->getContainer();

        $params = $request->getQueryParams();
        $itemsService = new ItemsService($container);

	// find starting item by id and collection name
        $item = $itemsService->find($params['collection'],$params['id'],$params);

	// check if entity exists with same tags
        $dbConnection = $container->get('database');
        $tableGateway = new \Zend\Db\TableGateway\TableGateway($params['collection'], $dbConnection);
        $select = new \Zend\Db\Sql\Select($params['collection']);
        $select->where(array('tags' => $params['tags']));
        $result = $tableGateway->selectWith($select);
        
        if($result->count() > 0) {
        	return ['status' => 'error','message'=> 'Collection item exists with this tag'];
        }
	
        // clone main item	
	$copy = createItemCopy($params['collection'], $params['id'], $params['tags']);

	// clone translations
        $translation = getStartTranslations($params['collection'],$params['id'],$copy);

	// clone relationships
        $relationships = getRelationships($params['collection'], $item['data']['id'], $copy, $params['tags']);

        return $copy;
    }
}

if (!function_exists('createItemCopy')) {
    function createItemCopy($collection, $id, $tags) {
        
        $container = \Directus\Application\Application::getInstance()->getContainer();
        $acl = $container->get('acl');

        $params = Array();

        $itemsService = new ItemsService($container);
        $item = $itemsService->find($collection, $id);

        unset($item['data']['id']);
        $item['data']['tags'] = explode(',',$tags);

        $createdCopy = $itemsService->createItem($collection, $item['data']);
        return $createdCopy;

    }
}

if(!function_exists('insertRelationship')) {
    function insertRelationship($copy, $newItem, $item) {
        $container = \Directus\Application\Application::getInstance()->getContainer();
        $acl = $container->get('acl');

        $data = Array();
        $data[$item['field_many']] = $newItem['data']['id'];
        $data[$item['junction_field']] = $copy['data']['id'];
        $data['active'] = 2;
        
        $itemsService = new ItemsService($container);
        return $itemsService->createItem($item['collection_many'],$data);
        
     }
}

if(!function_exists('getStartTranslations')) {
    function getStartTranslations($collection,$id,$copy) {
        
        $container = \Directus\Application\Application::getInstance()->getContainer();
        $dbConnection = $container->get('database');

        $tableGateway = new \Zend\Db\TableGateway\TableGateway('directus_relations', $dbConnection);
        $select = new \Zend\Db\Sql\Select('directus_relations');
        $select->where('collection_one="'.$collection.'"');

        $return = [];

        $result = $tableGateway->selectWith($select);


        $translationCollection = NULL;
        $translationIdName = NULL;
    
        foreach($result->toArray() as $item) {

            if(strpos($item['collection_many'],'translations')) {
                $return['item'] = $item;

                $translationCollection = $item['collection_many'];
                $translationIdName = $item['field_many'];
            }

        }

        if($translationCollection != NULL && $translationIdName != NULL ) {
            $container2 = \Directus\Application\Application::getInstance()->getContainer();
            
            $dbConnection2 = $container2->get('database');

            $tableGateway2 = new \Zend\Db\TableGateway\TableGateway($translationCollection, $dbConnection2);
            $select2 = new \Zend\Db\Sql\Select($translationCollection);

            $select2->where($translationIdName.'="'.$id.'"');

            //var_dump('Kiválasztottuk a ' . $item['collection_one'].'_translations-ből' . ' az '. $item['field_many'] .' - '. $item2[$item['field_many']].' id-jű elemeihez tartozó translationöket!');

            $translations = $tableGateway2->selectWith($select2);
               
            foreach($translations->toArray() as $translation) {
                    unset($translation['id']);
                    $copyId = $copy['data']['id'];
                    $translation[$translationIdName] = $copyId;
                    
                    $itemsService = new ItemsService($container);
                                        
                    $container = \Directus\Application\Application::getInstance()->getContainer();
                    $acl = $container->get('acl');
                    $params = Array();
                    $itemsService = new ItemsService($container);
                    $createdCopy = $itemsService->createItem($translationCollection, $translation);
             }

         }

    }
}

if(!function_exists('getRelationships')) {
    
    function getRelationships($collection, $id, $newItem, $tags) {
 
        $container = \Directus\Application\Application::getInstance()->getContainer();
        $dbConnection = $container->get('database');

        $tableGateway = new \Zend\Db\TableGateway\TableGateway('directus_relations', $dbConnection);
        $select = new \Zend\Db\Sql\Select('directus_relations');
        $select->where('collection_one="'.$collection.'"');

        $result = $tableGateway->selectWith($select);
    
        foreach($result->toArray() as $item) {

            if(!strpos($item['collection_many'],'translations') && $item['field_one'] != NULL) {

                $tableGateway2 = new \Zend\Db\TableGateway\TableGateway($item['collection_many'], $dbConnection);
                $select2 = new \Zend\Db\Sql\Select($item['collection_many']);
            
                $select2->where($item['field_many'].'="'.$id.'"');

                $result2 = $tableGateway->selectWith($select2);


                foreach($result2 as $item2) {
                    

                    if($item['field_one'] != NULL && $item2) {
                        

                        $copy = createItemCopy($item['field_one'], $item2[$item['junction_field']], $tags);
                        //$translation = getOriginalTranslations($item['field_many'],$item,$item2,$copy);

                        $translations = getStartTranslations($item['field_one'],$item2[$item['junction_field']],$copy);
                        insertRelationship($copy, $newItem, $item);
                        
                        getRelationships($item['field_one'], $item2[$item['junction_field']], $copy, $tags);
                    
                    }
                }
                


               
            } 
           
        }


        return $result;
    }

}


