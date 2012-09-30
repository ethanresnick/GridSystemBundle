<?php
namespace ERD\GridSystemBundle\Model;

/**
 * Defines a grid.
 * 
 * A grid must have a unit count, measure unit count, gutter width, padding width, and starting
 * text size, all set in pixels. Further, 
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright May 26, 2012 Ethan Resnick Design
 */
class Grid
{
    /**
     * @var int Number of units in the grid
     */
    protected $unitCount;
    
    /**
     * @var int Number of units in the grid's ideal measure. 
     *          Will depend on {@link $startTextSize} but also the intended font, of course.
     */
    protected $measureUnitCount;
    
    /**
     * @var int The width of the gutters, in pixels. 
     */
    protected $gutterWidth;
    
    /**
     * @var int Pixels of padding within each column. The idea is decribed {@link http://www.subtraction.com/2007/06/06/nudge-your-e here}.
     */
    protected $paddingWidth;
    
    /**
     * @var float The basic text size for the grid (i.e from which the ideal measure width was calculated).
     * @internal Was $startTextSize in the old code. 
     */
    protected $textSize;
    
    /**
     * @var int The width of each unit in the grid, not counting it's gutter or padding. 
     */
    protected $unitWidth;
    
    /**
     * @var int The grid's total width.
     */
    protected $totalWidth;

    /**
     * @var int Are the grid's units typically rendered only as a single column.
     */
    protected $isOneCol;
    
    /**
     * @var int An optional outer margin that will be aplied symettrically to the grid.
     */
    protected $margin;


    /**
     * @param int $unitCount See {@link $unitCount}
     * @param int $measureUnitCount See {@link $measureUnitCount}
     * @param int $gutterWidth See {@link $gutterWidth}
     * @param int $paddingWidth See {@link $paddingWidth}
     * @param int $textSize See {@link $textSize}
     * @param null|int $unitWidth See {@link $unitWidth}. You must provide this or a total width 
     *                            (from which this width will be calculated internally).
     * @param null|int $totalWidth See {@link $totalWidth}. You must provide this or a column 
     *                             width (form which this total width will be calculated internally).
     * @param int $margin (Optional) See {@link $margin}.
     * @param null|boolean $isOneCol (Optional) See (@link $isOneCol}. If null, will be guessed.
     * 
     * @throws \InvalidArgumentException If both $unitWidth and $totalWidth are unset or if $measureUnitCount > $unitCount
     */
    public function __construct($unitCount, $measureUnitCount, $gutterWidth, $paddingWidth, $textSize,
                                $unitWidth = null, $totalWidth = null, $margin = 0, $isOneCol = null) 
    {
        $this->unitCount        = (int) $unitCount;
        $this->measureUnitCount = (int) $measureUnitCount;
        $this->gutterWidth      = (int) $gutterWidth;
        $this->paddingWidth     = (int) $paddingWidth;
        $this->textSize         = (float) $textSize;
        $this->margin           = (int) $margin; 
        
        $this->isOneCol   = ($isOneCol === null) ? $this->guessIsOneCol() : $isOneCol;

        if($this->measureUnitCount > $this->unitCount)
        {
            throw new \InvalidArgumentException('$measureUnitCount cannot be bigger than $unit count (i.e. the measure cannot occupy more units than the whole grid.');
        }
        
        if($unitWidth === null && $totalWidth === null)      //neither width was provided
        {
            throw new \InvalidArgumentException('You must provide either a unit width or a total width.');
        }

        else if($unitWidth !== null && $totalWidth !== null) //both widths were provided
        {
            //set column width and let completeGrid fill in totalWidth. If the value it calculates
            //is different than the totalWidth value provided, than one of the provided values is 
            //wrong and we throw an exception.
            $this->unitWidth = $unitWidth;
            $this->completeGrid();
            
            if($this->totalWidth !== $unitWidth) 
            {
                $msg = "The \$totalWidth you provided is different from the expected total width 
                        (derived from the grid's unit count and its gutter, padding, and margin 
                        sizes). Try setting only the \$unitWidth key or the \$totalWidth; the one
                        you don't set will be filled in automatically with the proper value.";

                throw new \InvalidArgumentException($msg);
            }
        }

        else                                                   //one width was provided
        {
            $this->unitWidth  = $unitWidth;
            $this->totalWidth = $totalWidth;
        
            $this->completeGrid(); //will fill in the null width
        }
    }

    /**
     * Fills in the missing property (either unitWidth or totalWidth) using the other info.
     * 
     */
    protected function completeGrid()
    {
        $paddingsWidth = ($this->unitCount * $this->paddingWidth * 2);
        $guttersWidth  = (($this->unitCount-1) * $this->gutterWidth);
        $marginsWidth  = ($this->margin * 2);

        if($this->unitWidth && !$this->totalWidth)
        {
            $unitsWidth = ($this->unitCount * $this->unitWidth);
            
            $this->totalWidth = $unitsWidth + $paddingsWidth + $guttersWidth + $marginsWidth; 
        }

        else if($this->totalWidth && !$this->unitWidth)
        {
            $unitsWidth = ($this->totalWidth - $marginsWidth - $guttersWidth - $paddingsWidth);

            $this->unitWidth = $unitsWidth/$this->unitCount;
        }
    }
    
    /**
     * Guesses @link $isOneCol}. This is called if $isOneCol is not set in the constructor.
     */
    protected function guessIsOneCol()
    {
        return ($this->unitCount === $this->measureUnitCount || $this->unitCount === 1);
    }
    
    
    public function getTotalWidth()
    {
        return $this->totalWidth;
    }

    public function getUnitCount()
    {
        return $this->unitCount;
    }
    
    public function getMeasureUnitCount()
    {
        return $this->measureUnitCount;
    }
    
    public function getUnitWidth()
    {
        return $this->unitWidth;
    }
    
    public function getGutterWidth()
    {
        return $this->gutterWidth;
    }
    
    public function getPaddingWidth()
    {
        return $this->paddingWidth;
    }
    
    public function getTextSize()
    {
        return $this->textSize;
    }

    public function getMargin()
    {
        return $this->margin;
    }

    public function getIsOneCol()
    {
        return $this->isOneCol;
    }
    
    /**
     * Gets the width occupied by multiple units, including their internal padding and gutters.
     * 
     * For example, if you're getting two units, it'll return the width of both units + the 
     * width of the gutter between them + the width of the padding around that gutter. It'll
     * also include the padding around the outside of both units (i.e. the padding on the left 
     * edge of the first unit and the right edge of the second unit) if $outerPadding is true.
     * 
     * @param int $unitCount The number of units to get the summed width of
     * @param boolean $outerPadding Whether to include the padding on the outer edges of the outer units.
     */
    public function getUnitsWidth($unitCount, $outerPadding = false)
    {
        $outerPadding = ($outerPadding) ? 2*$this->paddingWidth : 0;
        
        return ($unitCount*$this->unitWidth) + (($unitCount-1)*$this->gutterWidth) + 
               (($unitCount-1)*2*$this->paddingWidth) + $outerPadding;
    }
}