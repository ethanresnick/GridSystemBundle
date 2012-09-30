<?php
namespace ERD\GridSystemBundle\Tests\Model;
use ERD\GridSystemBundle\Model\GridMap;

/**
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright Jul 3, 2012 Ethan Resnick Design
 */
class GridMapTest extends \PHPUnit_Framework_TestCase
{
    /** @var ERD\GridSystemBundle\Model\Grid */
    protected $stubGrid;

    /** @var GridMap */
    protected $emptyMap;

    public function setUp()
    {
        $this->stubGrid = $this->getMockBuilder('ERD\GridSystemBundle\Model\Grid')->disableOriginalConstructor()->getMock();
        $this->stubGrid->expects($this->any())->method('getTotalWidth')->will($this->returnValue(800));
        $this->stubGrid->expects($this->any())->method('getTextSize')->will($this->returnValue(10));
        
        $this->emptyMap = new GridMap($this->stubGrid);
    }
    
    //////providers
    public function validCriteriaAndExpectedValuesProvider()
    {
        return array(

          array( //empty criteria causes an "always on" grid, that goes from 0 width to infinity.
              array(), 
              array('minWidth'=>0, 'minTextSize'=>0, 'maxWidth'=>null, 'maxTextSize'=>null)
          ),
          array(
              array('minWidth'=>400), 
              array('minWidth'=>400, 'minTextSize'=>5, 'maxWidth'=>null, 'maxTextSize'=>null)
          ),
          array(
              array('minWidth'=>500, 'minTextSize'=>6.25), //if they match, it's ok to have a minWidth and minTextSize
              array('minWidth'=>500, 'minTextSize'=>6.25, 'maxWidth'=>null, 'maxTextSize'=>null)
          ),
          array(
              array('maxTextSize'=>'6.25'), //max text size smaller than start size is ok
              array('minWidth'=>0, 'minTextSize'=>0, 'maxWidth'=>500, 'maxTextSize'=>6.25)
          ),
          array(
              array('maxTextSize'=>'11.173'), //maxWidth below should always be rounded up to nearest pixel.
              array('minWidth'=>0, 'minTextSize'=>0, 'maxWidth'=>894, 'maxTextSize'=>11.173)
          )

        );        
    }
    
    public function invalidCriteriaProvider()
    {
        return array(
            
            //grid will be wider than 800 at that width.
            array(array('maxWidth'=>800, 'maxTextSize'=>9)),
            //sizes don't quite match
            array(array('minWidth'=>500, 'minTextSize'=>6.2)),
            //invalid keys
            array(array('minwidth'=>200))
            
        );
    }

    public function invalidScalingIntervalProvider()
    {
        return array(
            
            array(array('maxWidth'=>800, 'maxTextSize'=>9)), //not a scaling interval at all.
            array(array('method'=>'invalid method', 'amount'=>10)), //invalid method
            array(array('amount'=>1)), //missing method
            array(array()),            //empty array
            array(array('amount'=>'invalid amount', 'method'=>'absolute-pixels')) //invalid amount            
        );
    }    



    public function testGetGrid()
    {
        $this->assertEquals($this->emptyMap->getGrid(), $this->stubGrid);
    }

    public function testGetScalingInterval()
    {
        $this->assertEquals(array('method'=>'font-pixels', 'amount'=>1), $this->emptyMap->getScalingInterval());
        
        //try setting to something besides the default (our only test that valid scaling intervals are accepted)
        $testInterval = array('method'=>'absolute-pixels', 'amount'=>'20');
        $this->emptyMap->setScalingInterval($testInterval);
        $this->assertEquals($testInterval, $this->emptyMap->getScalingInterval());
    }

    /**
     * @dataProvider invalidCriteriaProvider
     */    
    public function testSetPropertiesRejectsInvalidCriteria($invalidCriteria)
    {
        try { $this->emptyMap->setProperties($invalidCriteria); $this->fail('Exception expected'); }
        catch(\Exception $e) {}
    }

    /**
     * @dataProvider invalidScalingIntervalProvider
     */
    public function testSetScalingIntervalRejectsInvalidScalingInterval($invalidScalingInterval)
    {
        try { $this->emptyMap->setScalingInterval($invalidScalingInterval); $this->fail('Exception expected'); }
        catch(\Exception $e) {}        
    }
    
    public function testConstructorValidatesCriteriaAndScalingInterval()
    {
        //just check, for speed, that the first invalid scaling interval and the first
        //invalid criteria array are rejected. the other rejects* tests are more extensive.
        
        $invalidCriteria = $this->invalidCriteriaProvider(); $invalidCriteria = $invalidCriteria[0][0];
        $validCriteria = $this->validCriteriaAndExpectedValuesProvider(); $validCriteria = $validCriteria[0][0];
        $invalidSI = $this->invalidScalingIntervalProvider(); $invalidSI = $invalidSI[0][0];
        
        try { new GridMap($this->stubGrid, $invalidCriteria); $this->fail("Exception expected."); }
        catch(\Exception $e) { }
    
        try { new GridMap($this->stubGrid, $validCriteria, $invalidSI); $this->fail("Exception expected."); }
        catch(\Exception $e) { }
    }

    /**
     * @dataProvider validCriteriaAndExpectedValuesProvider
     */
    public function testSetProperties($criteria, $expectedValues)
    {
        $this->emptyMap->setProperties($criteria);
        
        $this->assertEquals($expectedValues['minWidth'], $this->emptyMap->getMinWidth());
        $this->assertEquals($expectedValues['maxWidth'], $this->emptyMap->getMaxWidth());
        $this->assertEquals($expectedValues['minTextSize'], $this->emptyMap->getMinTextSize());
        $this->assertEquals($expectedValues['maxTextSize'], $this->emptyMap->getMaxTextSize());
    }
    
    public function testSetPropertiesOverwritesExistingContradictoryState()
    {
        $testMap = new GridMap($this->stubGrid, array('minWidth'=>200));
        $testMap->setProperties(array('minWidth'=>600));
        
        $this->assertEquals(600, $testMap->getMinWidth());
        
        $testMap->setProperties(array('maxWidth'=>1000)); //so maxTextSize is 12.5
        $testMap->setProperties(array('maxTextSize'=>10));
        
        $this->assertEquals(800, $testMap->getMaxWidth());
    }
    
    public function testSetPropertiesMergesNewStateWithExistingConsonantState()
    {
        $testMap = new GridMap($this->stubGrid, array('minWidth'=>200));
        $testMap->setProperties(array('maxWidth'=>800));
        
        $this->assertEquals(200, $testMap->getMinWidth());
    }
}