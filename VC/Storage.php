<?php

/**
 * @encoding     UTF-8
 * @copyright    Copyright (C) 2018 Torbara (http://torbara.com). All rights reserved.
 * @license      Envato Standard License http://themeforest.net/licenses/standard?ref=torbara
 * @author       Vladislav Dolgolenko <vladislavdolgolenko.com>
 * @support      support@torbara.com
 */

namespace WPObjects\VC;

/**
 * Load addons from directory structure:
 * 
 * ./addons_folder
 *      ./addon_name_folder
 *          ./addon.less
 *          ./addon.js
 *          ./template.php
 *          ./less.php
 *          ./config.php
 * 
 */
class Storage extends \WPObjects\Data\AbstractStorage implements
    \WPObjects\AssetsManager\AssetsManagerInterface
{
    protected $base_folder_path = null;
    protected $template_file_name = 'template.php';
    protected $config_file_name = 'config.php';
    protected $less_params_file_name = 'less.php';
    
    /**
     * @var \WPObjects\AssetsManager\AssetsManager
     */
    protected $AssetsManager = null;
    
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }
        
        $this->data = $this->readFolder();
        
        return $this->data;
    }
    
    /**
     * Register styles and scripts
     * 
     * @param type $addons_folder_path
     * @return type
     */
    public function readFolder()
    {
        $list = $this->getList();
        
        $result = array();
        foreach ($list as $name) {
            $addon = $this->readAddonDir($name);
            if (is_array($addon) && isset($addon['base'])) {
                $result[] = $addon;
            }
        }
        
        return $result;
    }
    
    protected function readAddonDir($path_name)
    {
        $shortcode_dir = $this->getBaseFolderPath() . DIRECTORY_SEPARATOR . $path_name;
        $config_file_path = $this->getBaseFolderPath() . DIRECTORY_SEPARATOR . $path_name . DIRECTORY_SEPARATOR . $this->config_file_name;
        if (!file_exists($config_file_path)) {
            return array();
        }
        
        $addon = include $config_file_path;
        if (!isset($addon['base'])) {
            return array();
        }
        
        $name = $addon['base'];
        $addon['enqueue_styles'] = array();
        $addon['enqueue_scripts'] = array();
        $scripts_deps = isset($addon['scripts_deps']) ? $addon['scripts_deps'] : array();
        $styles_deps = isset($addon['styles_deps']) ? $addon['styles_deps'] : array();
        
        $files = scandir( $shortcode_dir );
        foreach ($files as $file) {
            
            if (preg_match('/.css|.less/', $file)) {
                $asset_name = $name . '-' . current(explode('.', $file));
                $dir_url = \plugin_dir_url( $shortcode_dir . DIRECTORY_SEPARATOR . $file ) ;
                $this->getAssetsManager()->registerStyle($asset_name, $dir_url . $file, $styles_deps);
                $addon['enqueue_styles'][] = $this->getAssetsManager()->prepareAssetName($asset_name);
                continue;
            } 
            
            if (preg_match('/.js/', $file)) {
                $asset_name = $name . current(explode('.', $file));
                $dir_url = \plugin_dir_url( $shortcode_dir . DIRECTORY_SEPARATOR . $file ) ;
                $this->getAssetsManager()->registerScript($asset_name, $dir_url . $file, $scripts_deps);
                $addon['enqueue_scripts'][] = $this->getAssetsManager()->prepareAssetName($asset_name);
                continue;
            } 
            
            if ($file === $this->template_file_name) {
                $addon['html_template'] = $shortcode_dir . DIRECTORY_SEPARATOR . $file;
                continue;
            }
            
            if ($file === $this->less_params_file_name) {
                $less_params = include ($shortcode_dir . DIRECTORY_SEPARATOR . $file);
                $addon['CustomizerSettings'] = $this->initLessParams($less_params);                
                continue;
            }
            
        }
        
        return $addon;
    }
    
    protected function initLessParams($params)
    {
        $result = array();
        foreach ($params as $key => $param) {
            if (is_string($key)) {
                $param['id'] = $key;
            }
            $ParamModel = new \WPObjects\LessCompiler\ParamModel($param);
            $ParamModel->setNamespace($this->getAssetsManager()->getNamespace());
            $this->getServiceManager()->inject($ParamModel);
            $result[] = $ParamModel;
        }
        
        return $result;
    }
    
    protected function getList()
    {
        $shortcodes_dir = $this->getBaseFolderPath();
        $files = scandir( $shortcodes_dir );
        
        $list = array();
        foreach ($files as $file) {
            if (!is_dir($shortcodes_dir . '/' . $file) || $file == "." || $file == "..") {
                continue;
            }
            
            $list[] = $file;
        }
        
        return $list;
    }
    
    public function setBaseFolderPath($path)
    {
        $this->base_folder_path = $path;
        
        return $this;
    }
    
    public function getBaseFolderPath()
    {
        return $this->base_folder_path;
    }
    
    public function setAssetsManager(\WPObjects\AssetsManager\AssetsManager $AM)
    {
        $this->AssetsManager = $AM;
        
        return $this;
    }
    
    /**
     * @return \WPObjects\AssetsManager\AssetsManager 
     */
    public function getAssetsManager()
    {
        return $this->AssetsManager;
    }
}