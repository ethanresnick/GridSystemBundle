<?php
namespace ERD\GridSystemBundle\Controller;

use ERD\GridSystemBundle\Model\GridSystem;

/**
 * Description of GridControllerInterface
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright Jun 8, 2012 Ethan Resnick Design
 */
interface GridControllerInterface
{
    public function generate(GridSystem $gridSystem, $template);
}