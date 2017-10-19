<?php

/**
 * @encoding     UTF-8
 * @copyright    Copyright (C) 2016 Torbara (http://torbara.com). All rights reserved.
 * @license      Envato Standard License http://themeforest.net/licenses/standard?ref=torbara
 * @author       Vladislav Dolgolenko (vladislavdolgolenko.com)
 * @support      support@torbara.com
 */

namespace WPObjects\Factory;

use WPObjects\EventManager\Manager as EventManager;

abstract class AbstractModelFactory extends EventManager implements 
    FactoryInterface,
    AutocompeleInterface,
    \WPObjects\Service\ManagerInterface
{
    /**
     * @var \WPObjects\Service\Manager
     */
    protected $ServiceManager = null;
    
    /**
     * Query flag. How to create result object. If true, result must be instance of \WP_Query
     * 
     * @var boolean
     */
    protected $result_as_object = false;
    
    /**
     * Query filters, using for build query 
     * 
     * @var type 
     */
    protected $filters = array();
    
    /**
     * Last query results as initialized objects 
     * 
     * @var array
     */
    protected $result = null;
    
    /**
     * Identity attribute name
     * 
     * @var string
     */
    protected $id_key = 'id';
    
    /**
     * Cached query results
     * 
     * @var array
     */
    protected $cache = array();
    
    /**
     * Initialize model type
     * 
     * @param array|\WP_Post $post once result query data for initialize model
     * @return \WPObjects\Model\AbstractModel
     */
    abstract protected function initModel($post);
    
    /**
     * Query processing
     */
    abstract protected function doQuery($filters = array(), $result_as_object = false);
    
    /**
     * Cache query control
     * 
     * @param array $filters
     * @param boolean $result_as_object
     * @return $this
     */
    public function query($filters = array(), $result_as_object = false)
    {
        $cache_hash_data = array_merge($filters, array('result_as_object' => $result_as_object));
        $cache_hash = serialize($cache_hash_data);
        $cache_id = hash('md5', $cache_hash); 
        if (isset($this->cache[$cache_id])) {
            $this->setResult($this->cache[$cache_id]);
            //\WPObjects\Log\Loger::getInstance()->write("Factory " . get_class($this) . " : query restore from cache with id $cache_id");
        } else {
            $this->doQuery($filters, $result_as_object);
            $this->cache[$cache_id] = $this->getResult();
        }
        
        return $this;
    }
    
    /**
     * Return identities attribute name of current factory model type 
     * 
     * @return string
     */
    public function getIdAttrName()
    {
        return $this->id_key;
    }
    
    /**
     * Return identities of objects from last result
     * 
     * @return array
     */
    public function getResultIds()
    {
        $result_ids = array();
        foreach ($this->getResult() as $Model) {
            $result_ids[] = $Model->getId();
        }
        
        return $result_ids;
    }
    
    /**
     * Return last query result
     * 
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * Insert last query result
     * 
     * @param array $result
     * @return $this
     */
    protected function setResult($result)
    {
        $this->result = $result;
        
        return $this;
    }
    
    /**
     * Return query filters
     * 
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * Merge current with new filters
     * 
     * @param array $array new filters
     * @return $this
     */
    public function updateFilters($array)
    {
        $this->filters = array_merge($this->filters, $array);
        
        return $this;
    }
    
    /**
     * Set current query filters.
     * 
     * @param array $array
     * @param bollean $silent if true initialize event 'set_query_filters'
     * @return $this
     */
    protected function setFilters($array, $silent = false)
    {
        $this->filters = $array;
        
        if ($silent !== true) {
            $this->trigger('set_query_filters');
        }
        
        return $this;
    }
    
    /**
     * Create value as array if string delimiter is ','
     * 
     * @param string $string
     * @return array()
     */
    static public function prepareStringToArray($string)
    {
        if (is_array($string)) {
            return $string;
        }

        $values = explode(', ', $string);
        if (is_array($values) && count($values) !== 0) {
            return $values;
        }

        return array();
    }
    
    public function setServiceManager(\WPObjects\Service\Manager $ServiceManager)
    {
        $this->ServiceManager = $ServiceManager;
        
        return $this;
    }
    
    public function getServiceManager()
    {
        if (is_null($this->ServiceManager)) {
            throw new \Exception('Undefined service manager');
        }
        
        return $this->ServiceManager;
    }
}