<?php
namespace ERD\GridSystemBundle\Tests\Model;
use ERD\GridSystemBundle\Model\CSSObjectMap;
use ERD\GridSystemBundle\Model\GridMap;
use ERD\GridSystemBundle\Model\CSSObject;
use ERD\GridSystemBundle\Model\Grid;
/**
 * Test class for CSSObjectMap.
 * 
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 */
class CSSObjectMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CSSObjectMap
     */
    protected $om;

    /**
     * @var GridMap
     */
    protected $gridMap;

    /**
     * @var CSSObject
     */    
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object  = new CSSObject('dummyObject');
        $this->gridMap = $this->getMockBuilder('ERD\GridSystemBundle\Model\GridMap')->disableOriginalConstructor()->getMock();
        $this->gridMap->expects($this->any())->method('getGrid')->will($this->returnValue(new Grid(4, 4, 10, 3, 12, 45)));
        
        $this->om = new CSSObjectMap($this->object, array($this->gridMap));
    }

    public function testGetObject()
    {
        $this->assertEquals($this->object, $this->om->getObject());
    }

    public function testConstuctorRejectsInvalidOptions()
    {     
        try  /* non GridMap provided */ 
        { 
            new CSSObjectMap($this->object, array(new \stdClass()));
            $this->fail('Proving a non-GridMap object should throw an exception.');
        }
        catch(\InvalidArgumentException $e) {}
        
        try /* duplicate GridMap provided */ 
        {
            new CSSObjectMap($this->object, array($this->gridMap, $this->gridMap));
            $this->fail('Proving the same GridMap more than once should throw an exception.');
        }
        catch(\InvalidArgumentException $e) {}
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddCSSDeclarationsToMapRequiresMappedGridMap()
    {
        $gm = $this->getMockBuilder('ERD\GridSystemBundle\Model\GridMap')->disableOriginalConstructor()->getMock();
        $this->om->addCSSDeclarationsToMap($gm, array('padding'=>'20px'));
    }

    public function testAddGetCSSDeclarationsForMapInsertsNewPropertyAndIsCumulative()
    {
        /** @todo  this should be two tests, with the one depdendent on the other. */

        $data = array('width'=>'200px');
        $extraData = array('padding'=>'20px');
        
        //test properties added
        $this->om->addCSSDeclarationsToMap($this->gridMap, $data);
        $this->assertEquals($data, $this->om->getCSSDeclarationsForMap($this->gridMap));
        
        //test they're added cumulatively
        $this->om->addCSSDeclarationsToMap($this->gridMap, $extraData);
        $this->assertEquals(array_merge($extraData, $data), $this->om->getCSSDeclarationsForMap($this->gridMap));
    }

    public function testDeclarationVariablesProcessedCorrectly()
    {
        $this->markTestIncomplete();
    }
}
?>
