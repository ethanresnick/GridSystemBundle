<?php
namespace ERD\GridSystemBundle\Model;

/**
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright Dec 29, 2011 Ethan Resnick Design
 */
class CSSObject
{
    /**
     * @var string The CSS Selector that defines the object
     */
    protected $selector;
    
    /**
     * @var array Declarations that apply to the object by default in all grids. 
     */
    protected $baseDeclarations;
    
    /**
     * The constructor
     * @param string $selector See {@link $selector}
     * @param array $baseDeclarations Optional. See {@link $baseDeclarations}
     */
    public function __construct($selector, array $baseDeclarations = array())
    {
        $this->selector = \trim($selector);
        $this->baseDeclarations = $baseDeclarations;
    }
    
    /**
     * Sets {@link $baseDeclarations} to the array provided
     * 
     * @api
     */
    public function setBaseDeclarations(array $baseDeclarations)
    {
        $this->baseDeclarations = $baseDeclarations;
    }
    
        
    /**
     * @return string The selector that defines the object, as was provided to the constructor.
     * 
     * @api
     */
    public function getSelector()
    {
        return $this->selector;
    }
            
    /**
     * @return string {@link $baseDeclarations}
     * 
     * @api
     */
    public function getBaseDeclarations()
    {
        return $this->baseDeclarations;
    }
}
?>