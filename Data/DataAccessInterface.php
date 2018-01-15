<?php

/**
 * @encoding     UTF-8
 * @copyright    Copyright (C) 2018 Torbara (http://torbara.com). All rights reserved.
 * @license      Envato Standard License http://themeforest.net/licenses/standard?ref=torbara
 * @author       Vladislav Dolgolenko <vladislavdolgolenko.com>
 * @support      support@torbara.com
 */

namespace WPObjects\Data;

interface DataAccessInterface
{
    public function setDataAccess(\WPObjects\Data\Data $Data);
    
    /**
     * @return \WPObjects\Data\Data
     */
    public function getDataAccess();
}