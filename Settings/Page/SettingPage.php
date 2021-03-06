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

namespace WPObjects\Settings\Page;

class SettingPage extends \WPObjects\Page\AdminPage
{
    public function __construct()
    {
        $this->setMenuName(__( 'Settings', 'msp' ));
        $this->setTitle(__( 'Settings', 'msp' ));
        $this->setPermission('manage_options');
        $this->setId('settings');
        $this->setMenuPosition(30);
        
        $this->setTemplatePath(dirname(__FILE__) . '/templates/setting-page.php');
    }
    
    public function enqueues()
    {
        $AM = $this->getAssetsManager();
        $AM->enqueueStyle('database');
        $AM->enqueueScript('bootstrap');
    }
    
    public function POSTAction($data)
    {
        if (!isset($data['setting_namespace']) || 
            $data['setting_namespace'] !== $this->getAssetsManager()->getNamespace()) {
            return;
        }
        
        $Settings = $this->getSettings();
        foreach ($Settings as $Settings) {
            $id = $Settings->getId();
            if (!isset($data[$id]) || 
                $data[$id] == $Settings->getCurrentValue()) {
                continue;
            }
            
            $new_value = $data[$id];
            $Settings->setCurrentValue($new_value);
        }
    }
    
    /**
     * @return \WPObjects\Settings\Model
     */
    public function getSettings()
    {
        $Factory = $this->getServiceManager()->get('SettingsFactory');
        return $Factory->query()->getResult();
    }
    
    
}