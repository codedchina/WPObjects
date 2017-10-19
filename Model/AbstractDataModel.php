<?php

/**
 * @encoding     UTF-8
 * @copyright    Copyright (C) 2016 Torbara (http://torbara.com). All rights reserved.
 * @license      Envato Standard License http://themeforest.net/licenses/standard?ref=torbara
 * @author       Vladislav Dolgolenko (vladislavdolgolenko.com)
 * @support      support@torbara.com
 */

namespace WPObjects\Model;

abstract class AbstractDataModel extends AbstractTypicalModel
{
    public function getId()
    {
        $attr = $this->getModelType()->getIdAttrName();
        if (isset($this->$attr)) {
            return $this->$attr;
        }
        
        return null;
    }
    
    /**
     * Get associated model identities
     * @param string $model_type_id
     */
    public function getQualifierId($model_type_id)
    {
        $attr_name = $this->getModelType()->getQualifierAttrName($model_type_id);
        if (isset($this->$attr_name)) {
            return $this->$attr_name;
        }
        
        return null;
    }
    
    /**
     * Set association with other typical model instance 
     * @param string $model_type_id
     * @param int $model_id
     * @return $this
     */
    public function setQualifierId($model_type_id, $model_id)
    {
        $attr_name = $this->getModelType()->getQualifierAttrName($model_type_id);
        $this->$attr_name = $model_id;
        
        return $this;
    }
    
    public function save()
    {
        return;
    }
}