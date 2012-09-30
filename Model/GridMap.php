<?php

namespace ERD\GridSystemBundle\Model;

/**
 * Wraps a GridDefinition to say how and at what widths/text sizes the grid can take effect.
 * 
 * @todo Give this more expressive power so that it accounts for when to zoom and when to widen columns only.
 * 
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright May 27, 2012 Ethan Resnick Design
 */
class GridMap
{
    /**
     * How frequrently we add another media query to "zoom" the grid
     * @var array Stores the scaling interval in an array with two keys: 'method',
     *            which can be 'absolute-pixels' (ex zoom every 20px) or 'font-pixels'
     *            (zoom every 'however many extra pixels it would take to fit the grid
     *             on screen at a font size X pixels bigger); and 'amount', which
     *            is an integer in pixels.
     */
    protected $scalingInterval;
 
    /**
     * @var int The minimum width, in pixels, at which this grid can take effect. 
     */
    protected $minWidth = null;

    /**
     * @var int The maximum width, in pixels, at which this grid can take effect. 
     */
    protected $maxWidth = null;

    /**
     * @var int The minimum text size, in pixels, at which this grid can take effect. 
     */
    protected $minTextSize = null;

    /**
     * @var int The maximum text size, in pixels, at which this grid can take effect. 
     */    
    protected $maxTextSize = null;
    
    /**
     * @var Grid The grid this object is wrapping. 
     */
    protected $grid;
    
    /**
     * @var string (Set automatically) A unique id for this object. Can be used as an array key.
     */
    protected $id;
    
    /**
     * @param Grid $grid See {@link $grid}
     * @param array $criteria An array that's used by {@link setProperties()} to determine the mapping.
     * @param array $scalingInterval See {@link $scalingInterval}
     * 
     * @throws \InvalidArgumentException When the provided scalingInterval is invalid
     */
    public function __construct(Grid $grid, array $criteria = array(), array $scalingInterval = array('method'=>'font-pixels', 'amount'=>1))
    {
        $this->grid = $grid;
        $this->setProperties($criteria);
        $this->setScalingInterval($scalingInterval);
        
        $this->id = uniqid();
    }
    
    /**
     * Sets the class' minWidth, maxWidth, minTextSize, and maxTextSize.
     * 
     * Does so using the criteria array, which is often incomplete or contradictory. Prefers new
     * values passed in in $criteria to any potential existing values of the class properties.
     * 
     * Here are some of the rules:
     * 
     * The minTextSize/Width default to zero if neither is provided
     * The maxTextSize can be smaller, or minTextSize bigger, than the GridMap's Grid's default text size.
     * A min- or maxTextSize and a min- or maxWidth can both be provided as long as they agree (i.e. the text size doesn't imply a different width than the provided width)
     * 
     * 
     * @param array $criteria An array with an optional key=>value pair for each of minWidth, maxWidth, minTextSize, maxTextSize. 
     * @throws \InvalidArgumentException When some sort of contradiction exists with the values in $criteria.
     * @throws \InvalidArgumentException When $criteria includes any keys besides 
     */
    public function setProperties(array $criteria)
    {
        $allowedKeys = array('minWidth', 'minTextSize', 'maxWidth', 'maxTextSize');
        
        if(!count(array_diff(array_keys($criteria), $allowedKeys))==0)
        { 
            throw new \InvalidArgumentException('Invalid key in your $criteria array.'); 
        }

        foreach(array('min', 'max') as $edge)
        {
            $textKey  = $edge.'TextSize';
            $widthKey = $edge.'Width'; 
            
            //check for contradictions, which throw exceptions
            if(isset($criteria[$textKey]) && isset($criteria[$widthKey]) && 
            ($this->getGridTextSizeAtWidth($criteria[$widthKey]) != $criteria[$textKey]))
            {
                throw new \InvalidArgumentException('The criteria array provided included both 
                       a '.$widthKey.' and a '.$textKey.' key, but they didn\'t match. At the '.
                       $widthKey.' provided ('.$criteria[$widthKey].') the text size would be '.
                       $this->getGridTextSizeAtWidth($criteria[$widthKey]).', not '.$criteria[$textKey].
                       ' (the '.$textKey.' provided).');
            }
            
            //If the user set one value for this edge we can use it to find the other and, if they
            //set both, we know they agree. So either way we find the first set value and use it 
            //to set both. If they set no value, than the existing values will be maintained.
            if(isset($criteria[$textKey]))
            {
                $this->{$textKey}   = (float) $criteria[$textKey];
                $this->{$widthKey}  = $this->getGridWidthAtTextSize($this->{$textKey});
            }

            elseif(isset($criteria[$widthKey]))
            {
                $this->{$widthKey} = (int) $criteria[$widthKey];
                $this->{$textKey}  = $this->getGridTextSizeAtWidth($this->{$widthKey});
            }
        }
        
        //minWidth and minTextSize can't be null so, if they still are, set them to zero.
        if($this->minWidth === null) {$this->minTextSize = 0; $this->minWidth = 0; }
    }
    
    /**
     * Calculates the width of the wrapped {@link $grid} for the given text size.
     * 
     * Will round the width up to the nearest whole pixel.
     */
    protected function getGridWidthAtTextSize($size)
    {
        return \ceil(($size / $this->grid->getTextSize()) * $this->grid->getTotalWidth());
    }

    /**
     * Calculates the text size of the wrapped {@link $grid} when the grid is scaled to have the given $width.
     */
    protected function getGridTextSizeAtWidth($width)
    {
        return ($width / $this->grid->getTotalWidth()) * $this->grid->getTextSize();
    }
    
    /**
     * Sets the scaling interval
     * @param array The scaling interval. See {@link $scalingInterval the definiton}
     * @throws \InvalidArgumentException When the provided scaling interval is invalid
     */
    public function setScalingInterval(array $scalingInterval)
    {
        try { $this->validateScalingInterval($scalingInterval); $this->scalingInterval = $scalingInterval; }
        catch (\Exception $e) { throw new \InvalidArgumentException($e->getMessage()); }
    }
    
    public function getScalingInterval() { return $this->scalingInterval; }
        
    /**
     * Tests the provided scaling interval for validity
     * @param array $scalingInterval The scaling interval to validate
     * @return boolean True if valid; throws an exception if false
     * @throws Exception
     */
    protected function validateScalingInterval(array $scalingInterval)
    {
       if(isset($scalingInterval['amount']) && is_numeric($scalingInterval['amount']) && 
          isset($scalingInterval['method']) && ($scalingInterval['method']=='font-pixels' || $scalingInterval['method']=='absolute-pixels'))
       {
           return true;
       }
       
       throw new \Exception('The scalingInterval provided is invalid. See the documentation for the valid syntax.');
    }
    
    public function getMinWidth()
    {
        return $this->minWidth;
    }
    
    public function getMinTextSize()
    {
        return $this->minTextSize;
    }     
    
    public function getMaxWidth()
    {
        return $this->maxWidth;
    }
    
    public function getMaxTextSize()
    {
        return $this->maxTextSize;
    }
    
    public function getGrid()
    {
        return $this->grid;
    }

    public function getId()
    {
        return $this->id;
    }
}