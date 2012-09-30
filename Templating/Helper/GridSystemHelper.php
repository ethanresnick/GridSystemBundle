<?php
namespace ERD\GridSystemBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Description of GridSystemHelper
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright Jun 8, 2012 Ethan Resnick Design
 */
class GridSystemHelper extends Helper
{
    
    public function getHiddenDeclarations()
    {
        return array('position'=>'absolute !important', 'border'=>'0 !important', 
                     'padding'=>'0 !important', 'height' => '1px !important', 
                     'width' => '1px !important', 'clip'=>'rect(0 0 0 0)',
                     'margin' => '-1px !important', 'overflow' => 'hidden');
    }

    public function getLastColDeclarations()
    {
        return array('margin-right'=>'0');
    }
    
    public function getName()
    {
        return 'erd_grid';
    }
}