<?php

/**
 * @encoding     UTF-8
 * @copyright    Copyright (C) 2016 Torbara (http://torbara.com). All rights reserved.
 * @license      Envato Standard License http://themeforest.net/licenses/standard?ref=torbara
 * @author       Vladislav Dolgolenko (vladislavdolgolenko.com)
 * @support      support@torbara.com
 */

namespace WPObjects\LessCompiler;

use WPObjects\Factory\AbstractData;

class ParamsFactory extends AbstractData
{
    public function initModel($post)
    {
        return new ParamModel($post, \ArrayObject::ARRAY_AS_PROPS);
    }
    
    public function getByGroup($group)
    {
        $this->query(array(
            'group' => $group
        ));
        
        return $this->getResult();
    }
    
    public function getResultGroupped()
    {
        $colors = $this->getResult();

        $groups = array();
        foreach ($colors as $name => $color) {
            $group = $color['group'];
            if (!isset($groups[$group])) {
                $groups[$group] = array();
            }

            $groups[$group][$name] = $color;
        }

        return $groups;
    }
    
    public function getParamValueById($id)
    {
        $Param = $this->get($id);
        if ($Param) {
            return null;
        }
        
        return $Param->getCurrentValue();
    }
    
    public function getResultAsLessParams()
    {
        $Params = $this->getResult();
        
        $result = array();
        foreach ($Params as $Param) {
            $result[$Param->id] = $Param->getCurrentValue();
        }
        
        return $result;
    }
    
    public function getResultAsThemeModeDefault()
    {
        $Params = $this->getResult();
        
        $result = array();
        foreach ($Params as $Param) {
            $new_param = $Param->getArrayCopy();
            $new_param['default'] = $Param->getCurrentValue();
            $result[] = $new_param;
        }
        
        return $result;
    }
}