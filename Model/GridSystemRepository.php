<?php
namespace ERD\GridSystemBundle\Model;

use ERD\GridSystemBundle\Model\GridSystem;

/**
 * Stores and gives access to the bundle's various GridSystems. Implemented in the service container as singleton.
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright May 26, 2012 Ethan Resnick Design
 */
class GridSystemRepository
{
    const CONFIG_TEMPLATE_KEY = 'template';
    const CONFIG_OUTPUT_KEY   = 'output_path';

    /**
     * @var array[GridSystem] The grid systems in this repository.
     */
    protected $gridSystems = array();

    /**
     * @var array[array] An array that, for each grid system, stores an array with its template name and output path. 
     */
    protected $gridSystemsConfig = array();
    

    public function addGridSystem(GridSystem $gridSystem)
    {
        $this->gridSystems[$gridSystem->getName()] = $gridSystem;
    }
    
    public function setGridSystems($gridSystems)
    {
        $this->gridSystems = array();

        foreach($gridSystems as $system)
        {
            $this->addGridSystem($system); //so we get the type hinting.
        }
    }
    
    public function getGridSystem($name)
    {
        return $this->gridSystems[$name];
    }
    
    public function getGridSystems()
    {
        return $this->gridSystems;
    }
    
    /**
     * @param GridSystem $gs
     * @param array $config
     * @throws \InvalidArgumentException If $config doesn't have the keys, and only the keys,
     * GridSystemRepository::CONFIG_TEMPLATE_KEY and GridSystemRepository::CONFIG_OUTPUT_KEY.
     */
    public function setGridSystemConfig(GridSystem $gs, array $config)
    {
        if(array(self::CONFIG_TEMPLATE_KEY, self::CONFIG_OUTPUT_KEY) !== array_keys($config))
        {
            throw new \InvalidArgumentException('$config must have the proper keys.');
        }

        $this->gridSystemsConfig[$gs->getName()] = $config;
    }
    
    public function getGridSystemConfig(GridSystem $gs)
    {
        return $this->gridSystemsConfig[$gs->getName()];
    }
}