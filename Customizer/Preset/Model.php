<?php

/**
 * @encoding     UTF-8
 * @package      WPObjects
 * @link         https://github.com/VladislavDolgolenko/WPObjects
 * @copyright    Copyright (C) 2018 Vladislav Dolgolenko
 * @license      MIT License
 * @author       Vladislav Dolgolenko <vladislavdolgolenko.com>
 * @support      <help@vladislavdolgolenko.com>
 */

namespace WPObjects\Customizer\Preset;

class Model extends \WPObjects\Model\AbstractModel
{
    protected $id = null;
    protected $name = null;
    protected $params = array();
    protected $settings_pregix = '';
    
    public function setupPreset()
    {
        $theme_mode_params = $this->getParamsForCustomizer();
        foreach ($theme_mode_params as $key => $value) {
            \set_theme_mod($key, $value);
        }
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getParamsForCustomizer()
    {
        $result = array();
        foreach ($this->params as $key => $value) {
            $result[$this->getSettingsPregix() . $key] = $value;
        }
        
        return $result;
    }
    
    public function getSettingsPregix()
    {
        return $this->settings_pregix;
    }
    
    public function setSettingsPregix($settings_pregix)
    {
        $this->settings_pregix = $settings_pregix;
        
        return $this->settings_pregix;
    }
    
    public function toJSON()
    {
        $data = parent::toJSON();
        $data['id'] = $this->getId();
        $data['name'] = $this->getName();
        $data['params'] = $this->getParams();
    }
}