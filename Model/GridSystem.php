<?php
namespace ERD\GridSystemBundle\Model;

/**
 * Stores all the data about a GridSystem
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright May 26, 2012 Ethan Resnick Design
 */
class GridSystem
{
    /**
     * @var sting The name of the grid system (e.g. "main site grid system")
     */
    protected $name;

    /**
     * @var int The minimum size you'll allow the base font (i.e. 1em) to be, in pixels
     */
    protected $minFontSize;

    /**
     * @var int The maxmimum size you'll allow the base font (i.e. 1em) to be, in pixels
     */
    protected $maxFontSize;

    /**
     * @var array[CSSObjectMap] The CSSObjectMaps in the system 
     */
    protected $objectMaps;
    
    /**
     * @var array The GridMaps in the system
     */
    protected $gridMaps;
    
    /**
     * @var array An array of id-like values retrieved from our ObjectMaps or GridMaps that are 
     * stored separately for speed and convenience. 
     * 
     * For example, if we need to check that the user's not adding an object to the system 
     * which already exists, we could loop over all the existing objects and compare each 
     * object's selector with the new object's selector. Or we could just store the existing 
     * selectors separately/directly in this array and just loop over it.
     */
    protected $systemIds = array('object_selectors'=>array(), 'grid_sizes'=>array());
    
    /**
     * @var array An variable used internally to cache the results of various getters. 
     */
    protected $cache = array('column_emulators'=>array(), 'hidden_objects'=>array());
    
    /**
     * @param string $name See {@link $name}.
     * @param int $minFontSize See {@link $minFontSize}. Defaults to 14px. Can skip with null,
     * @param int $maxFontSize See {@link $maxFontSize}. Defaults to 22px. Can skip with null,
     * @param array $objectMaps (Optional) See {@link $objectMaps}.
     * @param array $gridMaps (Optional) See {@link $gridMaps}.
     * 
     * @throws \InvalidArgumentException If $maxFontSize is less than $minFontSize.
     */

    public function __construct($name, $minFontSize = 14, $maxFontSize = 22, array $objectMaps = array(), array $gridMaps = array())
    {
        $this->name        = (string) $name;
        $this->minFontSize = ($minFontSize===null) ? 14 : (float) $minFontSize;
        $this->maxFontSize = ($maxFontSize===null) ? 22 : (float) $maxFontSize;
        
        if($this->maxFontSize < $this->minFontSize) 
        {
            throw new \InvalidArgumentException('The grid system\'s $maxFontSize can\'t be less than it\'s $minFontSize'); 
        }

        $this->setObjectMaps($objectMaps);
        $this->setGridMaps($gridMaps);
    }
    
    public function getName()        { return $this->name; }
    public function getMinFontSize() { return $this->minFontSize; }
    public function getMaxFontSize() { return $this->maxFontSize; }


    public function setObjectMaps($objectMaps)
    {
        //reset and add iteratively so we get the type hinting & validation.
        $this->objectMaps = array();
        $this->systemIds['object_selectors'] = array();

        foreach($objectMaps as $map) { $this->addObjectMap($map); }
    }
    
    /**
     * @throws \InvalidArgumentException When an objectMap is provided whose object already has a map in the system.
     */
    public function addObjectMap(CSSObjectMap $map)
    {
        $id = $map->getObject()->getSelector();

        if(in_array($id, $this->systemIds['object_selectors'])) 
        {
            throw new \InvalidArgumentException('An object map for the object with selector '.$id.'already exists in this grid system.');
        }
        
        $this->objectMaps[] = $map;
        $this->systemIds['object_selectors'][] = $id;
        $this->invalidateObjectMapCache();
    }

    /**
     * @return array[CSSObjectMap] 
     */
    public function getObjectMaps() { return $this->objectMaps; }


    public function setGridMaps($maps)
    {   
        //reset and add iteratively so we get the type hinting.
        $this->gridMaps = array();
        $this->systemIds['grid_sizes'] = array();
        
        foreach($maps as $map) { $this->addGridMap($map); }
    }
    
    /**
     * @throws \InvalidArgumentException When a GridMap already exists in the system that covers this Map's widths.
     */
    public function addGridMap(GridMap $map)
    {
        foreach($this->systemIds['grid_sizes'] as $sizeSet)
        {
            if($map->getMaxWidth() > $sizeSet['min'] && $map->getMinWidth() < $sizeSet['max'])
            {
                throw new \InvalidArgumentException("A GridMap already exists in the system that covers this Map's widths.");
            }
        }
        
        $this->gridMaps[] = $map;
        $this->systemIds['grid_sizes'][] = array('min'=>$map->getMinWidth(), 'max'=>$map->getMaxWidth());
        $this->invalidateGridMapCache();
    }

    public function getGridMaps() { return $this->gridMaps; }
    

    /**
     * @return int The number of units in the grid in the system that has the most units. 
     * 
     * @todo Template can loop this to build all the grid classes 
     */
    public function getMaxUnitCount() 
    {
        $unitCounts = array();
        
        foreach($this->gridMaps as $gridMap) 
        {
            $unitCounts[] = $gridMap->getGrid()->getUnitCount();
        }
        
        return \max($unitCounts);
    }

    /**
     * Gets objects that, for the given GridMap, are emulating columns (i.e. being floated, padded, etc.)
     * 
     * @param GripMap $map
     * @param int $column (Optional) To only get objects that emulate this column (1-indexed)
     * @return array[CSSObject] The objects that emulate a column at the given GridMap.
     */
    public function getColumnEmulators(GripMap $gridMap, $column = null)
    {
        //this cache is totally safe, i.e. automatically invalidated as needed by addObjectMap().
        if(isset($this->cache['column_emulators'][$gridMap->getId()])) 
        { 
            return $this->cache['column_emulators'][$gridMap->getId()];
        }

        $result = $this->getObjectsWithDeclarationAtGridMap($gridMap, '-erd-emulate', $column);
        
        $this->cache['column_emulators'][$gridMap->getId()] = $result;
        
        return $result;
    }
    
    /**
     * @param GridMap $gridMap
     * @return array[CSSObject] That are hidden at the given GridMap.
     */
    public function getHiddenObjects(GridMap $gridMap)
    {
        //this cache is totally safe, i.e. automatically invalidated as needed by addObjectMap().
        if(isset($this->cache['hidden_objects'][$gridMap->getId()])) 
        { 
            return $this->cache['hidden_objects'][$gridMap->getId()];
        }
        
        $result = $this->getObjectsWithDeclarationAtGridMap($gridMap, '-erd-hide');
        
        $this->cache['hidden_objects'][$gridMap->getId()] = $result;
        
        return $result;        
    }
    
    /**
     * Finds objects that have a given declaration in their declaration set for a given GridMap.
     * 
     * @param GridMap $gridMap The GridMap whose ObjectMap declarations to check
     * @param string $declaration The name of the declaration's key
     * @param string $value (Optional) A value the declaration must take for the object to be returned
     * @return array[CSSObject] The CSSObjects (pulled from the class's objectMaps) with the declaration.
     */
    protected function getObjectsWithDeclarationAtGridMap(GridMap $gridMap, $declaration, $value=null)
    {
        $result = array();
        foreach($this->getObjectMaps() as $objectMap)
        {
            $declarations = $objectMap->getCSSDeclarationsForMap($gridMap);
            
            if(isset($declarations[$declaration]) && ($value===null || $declarations[$declaration]==$value))
            {
                $result[] = $objectMap->getObject();
            }
        }        
        
        return $result;
    }

    /**
     * Invalidates any caches dependent on the ObjectMaps in the system. Called whenever a new 
     * ObjectMap is added (includeing when new ObjectMaps are set all at once).
     * 
     * It intentionally doesn't try to update (i.e. "fill in") the cache with the now-missing 
     * values because that would defeat its point by making each call to add() more expensive. 
     * We're trying to get all the expense of generating the cached values to be borne only 
     * when calling the getter whose value is being cached.
     * 
     * @return true On success.
     */
    protected function invalidateObjectMapCache()
    {
        $this->cache['column_emulators'] = array();
        $this->cache['hidden_objects'] = array();
         
        return true;
    }

    /**
     * Invalidates any caches dependent on the GridMaps in the system. 
     * 
     * Called whenever a new GridMap is added (including when new GridMaps are set all at once). 
     * 
     * @return true On success.
     */
    protected function invalidateGridMapCache()
    {
        return true;
    }
 
    
    /**
     * Returns all the data about the grid system, e.g. for passing to a template. The class' main function.
     * @return array The grid system's data.
    public function getData()
    {
        //populate a $grid_classes array with values ('.grid-1' to '.grid-maxcolnumber')
        
        //loop over each grid and set properties on it to be returned
        for($i=0,$len=count($this->grids); $i<$len; $i++)
        {
            $lastGrid = ($i==$len-1);
            
            $currGrid = $this->grids[$i];
            $currGrid['min_width']  = ($currGrid['total_width']/16).'em';
            $currGrid['max_width']  = (!$lastGrid ? (($this->grids[$i+1]['total_width']-1)/16).'em' : false);

            
            //for objects that emulate columns, we need to add their selectors both to the generic "act as a column" array
            //and  an array for the specific column their supposed to act like.
            $currGrid['additional_cols'] = array(); //other selectors that get treated as columns (i.e. gutters, float left, etc.)
            $currGrid['col_styles'] = array_fill(0, $currGrid['column_count'], array('selectors'=>array(), 'declarations'=>array()));
            $currGrid['container_col_styles'] = array_fill(0, $currGrid['column_count'], array('selectors'=>array(), 'declarations'=>array()));
            
            $currGrid['object_styles'] = array();
            
            //set known styles for objects
            for($j=0; $j<$currGrid['column_count']; $j++)
            {
                //set the width of our column classes
                //populate $currGrid['container_col_styles'][$j] with array('selectors'=>array('.container.grid-j+1'), 'declarations'=>array('width'=>container width)
                //populate $currGrid['col_styles'] the same way but without .container in selector and without outer padding in width.

            
            $scaleLayoutEveryXPixels = ($this->scalingInterval['method']=='absolute-pixels') ? $this->scalingInterval['amount'] : ((($this->scalingInterval['amount']+$currGrid['start_text_size'])/$currGrid['start_text_size']) * $currGrid['total_width']) - $currGrid['total_width'];
            $scaleFontByXEms = ($this->scalingInterval['method']=='font-pixels') ? $this->scalingInterval['amount']/16 : ((($this->scalingInterval['amount']+$currGrid['total_width'])/$currGrid['total_width']) * ($currGrid['start_text_size']/16)) - ($currGrid['start_text_size']/16);
            $maxFontSize  = isset($currGrid['max_text_size']) ? \min($this->maxFontSize, $currGrid['max_text_size']) : $this->maxFontSize;
            
            $currGrid['can_be_scaled'] = ($currGrid['start_text_size']+($scaleFontByXEms*16) <= $maxFontSize || (!$lastGrid && $this->grids[$i+1]['total_width'] > ($currGrid['total_width'] + $scaleLayoutEveryXPixels)));
            
            if($currGrid['can_be_scaled'])
            {
                if($i==0)
                {
                    $currGrid['scalers'] = array();
                }
                
                $maxWidth  = (!$lastGrid) ? $this->grids[$i+1]['total_width'] - 1 : ($currGrid['total_width'] * ($this->maxFontSize/$currGrid['start_text_size'])) + 1 + $scaleLayoutEveryXPixels; //if we're on the last grid, we're just scaling until the max font size, so don't worry about max width. Setting it really big like this ensures that the last scaler isn't constrained by it, so the scaling is continuous
                $currWidth = $currGrid['total_width'] + $scaleLayoutEveryXPixels; //our starting width for scaling is one interval beyond the unscaled
                $currFontSize = ($currGrid['start_text_size']/16) + $scaleFontByXEms; //same with our starting font size

                
                while($currWidth <= $maxWidth && ($currFontSize * 16) <= $maxFontSize)
                {
                    $thisScaler = array();
                    $thisScaler['min_width'] = (\ceil($currWidth)/16).'em';
                    $thisScaler['max_width'] = \min((\floor($currWidth + $scaleLayoutEveryXPixels))/16, \floor($maxWidth)/16).'em';
                    $thisScaler['font_size'] = $currFontSize.'em';
                    
                    $currGrid['scalers'][] = $thisScaler;
                    
                    $currFontSize += $scaleFontByXEms;
                    $currWidth += $scaleLayoutEveryXPixels;
                }

                //make sure the scaling persists all the way up to the next grid; necessary when the max font-size constricts our scaling
                if(!$lastGrid && $currWidth <= $maxWidth)
                {
                    //shrink current font size, which will have already been increased in preparation for th next round
                    $currFontSize -= $scaleFontByXEms;
                    
                    $thisScaler = array();
                    $thisScaler['min_width'] = (\ceil($currWidth)/16).'em';
                    $thisScaler['max_width'] = (\floor($maxWidth)/16).'em';
                    $thisScaler['font_size'] = $currFontSize.'em';
                    
                    $currGrid['scalers'][] = $thisScaler;
                }

                elseif($lastGrid) //persist the scaling indefinitely
                {
                    //shrink current font size, which will have already been increased in preparation for th next round
                    $currFontSize -= $scaleFontByXEms;
                    $thisScaler = array();
                    $thisScaler['min_width'] = (\ceil($currWidth)/16).'em';
                    $thisScaler['max_width'] = false;
                    $thisScaler['font_size'] = $currFontSize.'em';
                    
                    $currGrid['scalers'][] = $thisScaler; 
                }
            }
                    
            $grid_descriptions[] = $currGrid;
        }
        
        return array('grid-classes'=>$grid_classes, 'grids'=>$grid_descriptions);
    }        
     */
}