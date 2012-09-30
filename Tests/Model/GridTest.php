<?php 
namespace ERD\GridSystemBundle\Tests\Model;
use ERD\GridSystemBundle\Model\Grid;

class GridTest extends \PHPUnit_Framework_TestCase
{
    /** @var Grid */
    protected $basicGrid;
    
    protected function setUp()
    {
        $this->basicGrid = new Grid(8, 5, 10, 5, 12, 95); //khoi's grid
    }


    public function testGetUnitCount()
    {
        $this->assertEquals($this->basicGrid->getUnitCount(), 8);
    }

    
    public function testGetMeasureUnitCount()
    {
        $this->assertEquals($this->basicGrid->getMeasureUnitCount(), 5);
    }
    
    public function testGetGutterWidth()
    {
        $this->assertEquals($this->basicGrid->getGutterWidth(), 10);
    }
    
    public function testGetPaddingWidth()
    {
        $this->assertEquals($this->basicGrid->getPaddingWidth(), 5);
    }
    
    public function testGetTextSize()
    {
        $this->assertEquals($this->basicGrid->getTextSize(), 12);
    }
    
    public function testGetUnitsWidth()
    {
        $this->assertEquals($this->basicGrid->getUnitsWidth(2, true), 220);
        $this->assertEquals($this->basicGrid->getUnitsWidth(2, false), 210);
        
        $this->assertEquals($this->basicGrid->getUnitsWidth(5, true), 565);
        $this->assertEquals($this->basicGrid->getUnitsWidth(5, false), 555);
    }    
    

    
    
    
    ///////// BELOW ARE MORE COMPLICATED TESTS THAT REQUIRE FIXTURES BEYOND $BASICGRID ////////
    
    public function testTextSizeSupportsFloats()
    {
        $floatSize = new Grid(3, 2, 3, 4, 3.212, 29);
        $this->assertEquals($floatSize->getTextSize(), 3.212);
    }

    public function testConstructorRejectsInvalidGrids()
    {
        try { new Grid(5, 6, 10, 10, 10, 40); $this->fail('Expected an exception'); }
        catch(\InvalidArgumentException $e) 
        { 
            if(strpos($e->getMessage(), 'measure cannot occupy more units') === false) 
            {
                $this->fail('The wrong exeption was thrown.');
            }
        }

        try { new Grid(5, 2, 10, 10, 10); $this->fail('Expected an exception'); }
        catch(\InvalidArgumentException $e) 
        { 
            if(strpos($e->getMessage(), 'must provide either a unit width or') === false) 
            {
                $this->fail('The wrong exeption was thrown.');
            }
        }
    }
    

    public function complexGridProvider()
    {
        return array(
            array(
                new Grid(8, 5, 10, 5, 12, 95),  //repeat of basic grid (it's needed below too)
                array('totalWidth'=>910, 'unitWidth'=>95, 'margin'=>0, 'isOneCol'=>false)
            ),
            array(
                new Grid(8, 5, 10, 5, 12, 95, null, 10),  //the above with margin
                array('totalWidth'=>930, 'unitWidth'=>95, 'margin'=>10, 'isOneCol'=>false)
            ),
            array(
                new Grid(8, 5, 10, 5, 12, null, 910),  //total width given
                array('totalWidth'=>910, 'unitWidth'=>95, 'margin'=>0, 'isOneCol'=>false)
            ),
            array(
                new Grid(8, 5, 10, 5, 12, null, 910, 10),  //the above with margin
                array('totalWidth'=>910, 'unitWidth'=>92.5, 'margin'=>10, 'isOneCol'=>false)
            ),
            array(
                new Grid(8, 5, 10, 5, 12, null, 910, 10, true),  //explicitly set at is one col
                array('totalWidth'=>910, 'unitWidth'=>92.5, 'margin'=>10, 'isOneCol'=>true)
            ),
            array(
                new Grid(5, 5, 10, 5, 12, null, 910, 10, true),  //should be is one col implictly
                array('totalWidth'=>910, 'unitWidth'=>160, 'margin'=>10, 'isOneCol'=>true)
            )
        );
    }

    /**
     * @dataProvider complexGridProvider 
     */
    public function testGetTotalWidth($grid, $expectedValues)
    {
        $this->assertEquals($grid->getTotalWidth(), $expectedValues['totalWidth']);
    }

    /**
     * @dataProvider complexGridProvider 
     */        
    public function testGetUnitWidth($grid, $expectedValues)
    {
        $this->assertEquals($grid->getUnitWidth(), $expectedValues['unitWidth']);
    }

    /**
     * @dataProvider complexGridProvider 
     */
    public function testGetMargin($grid, $expectedValues)
    {
        $this->assertEquals($grid->getMargin(), $expectedValues['margin']);
    }

    /**
     * @dataProvider complexGridProvider 
     */
    public function testGetIsOneCol($grid, $expectedValues)
    {
        $this->assertEquals($grid->getIsOneCol(), $expectedValues['isOneCol']);
    }
}