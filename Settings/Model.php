<?php

/**
 * @encoding     UTF-8
 * @copyright    Copyright (C) 2018 Torbara (http://torbara.com). All rights reserved.
 * @license      Envato Standard License http://themeforest.net/licenses/standard?ref=torbara
 * @author       Vladislav Dolgolenko <vladislavdolgolenko.com>
 * @support      support@torbara.com
 */

namespace WPObjects\Settings;

class Model extends WPObjects\Model\AbstractDataModel implements
    \WPObjects\Service\NamespaceInterface
{
    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';
    const TYPE_DATE = 'date';
    const TYPE_URL = 'url';
    const TYPE_SELECT = 'select';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_IMAGE = 'image';
    
    protected $id = '';
    protected $name = '';
    protected $group = '';
    protected $default = '';
    protected $type = self::TYPE_TEXT;

    protected $namespace = '';

    public function getName()
    {
        return $this->get('name');
    }
    
    public function getDefault()
    {
        return $this->default;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getCurrentValue()
    {
        return \get_option($this->getWPOptionKey(), $this->getDefault());
    }
    
    public function getWPOptionKey()
    {
        return $this->getNamespace() . '_setting_' . $this->getId();
    }
    
    public function setNamespace($string)
    {
        $this->namespace = $string;
        
        return $this;
    }
    
    public function getNamespace()
    {
        return $this->namespace;
    }
    
    public function getViewUI()
    {
        if ($this->getType() === self::TYPE_TEXT) {
            
        }
    }
}