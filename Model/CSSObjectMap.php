<?php
namespace ERD\GridSystemBundle\Model;

/**
 * Represents how an "object", according to its OOCSS meaning, is displayed across multiple grids
 * 
 * The most challenging part of responsive design is figuring out how to map items between grid 
 * configurations in a robust and automatic way. What I have so far relies on a framework of 
 * roles, content types, and surrounding content though those specific features (e.g. .with-x 
 * or .with-y) are sometimes less important than how the user is supposed to progress through 
 * the page, which I'm beginning to develop a framework for mapping with the idea of divergent 
 * paths (which neccessitate multiple colums instead of linearizing). They could also be 
 * represented as multiple rows, if we knew more about the screen height...maybe, and were ok 
 * with horizontal scrolling. Something to that, esp for touch.)
 * 
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright Dec 29, 2011 Ethan Resnick Design
 */
class CSSObjectMap
{
    /**
     * @var CSSObject The CSSObject being mapped
     */
    protected $object;
    
    /**
     * @var array An array of GridMap objects, one for each gridmap the object is being mapped to.
     */
    protected $gridMaps = array();
 
    /**
     * @var array The extra declarations added to the object for each grid
     */
    protected $mapping = array();
    
    /**
     * The constructor
     * @param CSSObject $object See {@link $object}
     * @param array[GridMap] $grids See {@link $gridMaps}
     * @throws \InvalidArgumentException when a GridMap in $gridMapss isn't a valid GridMap object.
     */
    public function __construct(CSSObject $object, array $gridMaps)
    {
        $this->object = $object;
        
        foreach($gridMaps as $key=>$map) 
        {
            if(in_array($map, $this->gridMaps)) 
            { 
                throw new \InvalidArgumentException('The same GridMap cannot be provided more than once.');
            }
            elseif($map instanceof GridMap)
            {
                $this->gridMaps[] = $map; 
            }
            else
            {
                throw new \InvalidArgumentException('The grid map at $gridMaps['.$key.'] is not a valid '.__NAMESPACE__.'\GridMap object');
            }
        }
    }

    /**
     * @return CSSObject {@link $object}.
     */
    public function getObject()
    {
        return $this->object;
    }
 
    /**
     * @param GridMap $map        The GridMap you're adding declarations for. See {@link $gridMaps}.
     * @param array $declarations An array of CSS declarations in format array('property'=>'value',...);
     *                            Note that a grid column number can be used as a value with an "emulate" 
     *                            key (e.g. "emulate"=>"2"), which will make the Object behave like that 
     *                            grid unit within that grid.
     * @throws \InvalidArgumentException If the grid specified by $map doesn't exist in this mapping.
     */
    public function addCSSDeclarationsToMap(GridMap $map, $declarations)
    {
        $key = $map->getId();

        if(!in_array($map, $this->gridMaps)) 
        { 
            throw new \InvalidArgumentException('The grid map you provided declarations for doesn\'t exist in this object map. GridMaps to use must be provided to this class\' constructor'); 
        }
     
        if(!isset($this->mapping[$key])) { $this->mapping[$key] = array(); }
        
        $declarations = $this->processDeclarations($declarations, $map);
        
        $this->mapping[$key] = \array_merge($this->mapping[$key], $declarations);
    }

    /**
     * @param GridMap $map The GridMap you're adding declarations for. See {@link $gridMaps}.
     * @return array An array of CSS declarations ('property'=>'value') for the grid specified by $gridIndex 
     * @throws \InvalidArgumentException If the grid specified by $grid doesn't exist in this mapping.
     */
    public function getCSSDeclarationsForMap(GridMap $map)
    {
        $key = $map->getId();
        if(isset($this->mapping[$key]))
        {
            return $this->mapping[$key];
        }
        
        throw new \InvalidArgumentException('The GridMap you asked for declarations for doesn\'t exist in this object map. GridMaps to use must be provided to this class\' constructor'); 
    }
    
    /**
     * Calculates the value of declarations with dynamic variables
     * @param array $declarations The declarations whose dynamic variables will be replaced
     * @param GridMap $map The GridMap from which to get the dynanic variables' values.
     */
    protected function processDeclarations($declarations, GridMap $map)
    {
        //get the variables' values from the gridMap.
        $grid = $map->getGrid();
        $vars = array('$'.'padding-width' => $grid->getPaddingWidth(), '$'.'gutter-width' => $grid->getGutterWidth(),
                      '$'.'unit-width' => $grid->getUnitWidth());
        
        for($i=0, $len=$grid->getUnitCount(); $i<$len; $i++)
        {
            $vars['unit-'.($i+1).'-width'] = $grid->getUnitsWidth($i+1, false);
            $vars['unit-'.($i+1).'-size']  = $grid->getUnitsWidth($i+1, true);
        }
        
        //process those values into $result
        foreach($declarations as $k=>$v)
        {
            if(strpos($v, '$')!==false)
            {
                $v = str_replace(array_keys($vars), array_values($vars), $v);
                $v = eval($v.';'); /** eval to handle any math. @todo There are better ways to do this. */
                $declarations[$k] = $v;
            }
        }
        
        return $declarations;
    }
}
?>