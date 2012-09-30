<?php

namespace ERD\GridSystemBundle\Controller;

use ERD\GridSystemBundle\Model\GridSystem;
use Symfony\Component\Templating\EngineInterface;
/**
 * Returns a GridSystem's CSS from its data and template.
 * 
 * This controller doesn't extend the symfony base controller, or have any routes that make it
 * externally reachable, because it's not meant to integrate with the rest of the framework. 
 * It's not called by a ControllerResolver to handle a request and it doesn't return a Response.
 * Rather, it's called by a cache warmer that builds it only with access to the templating 
 * service and that gives it "request parameters" manually to its various actions.
 */
class GridController implements GridControllerInterface
{
    protected $templating;
    
    public function __construct(EngineInterface $templating)
    {
        $this->templating = $templating;
    }
    
    /**
     * Returns a GridSystem's CSS from its data and template.
     * 
     * @param GridSystem $gs The GS to generate output for, with all its data
     * @param string $template A template name capable of being handled by {@link templating}
     * that will be used to generate the output.
     */
    public function generate(GridSystem $gs, $template)
    {
        return $this->templating->render($template, array('gridSystem'=>$gs));
    }
}
