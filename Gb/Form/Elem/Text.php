<?php

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
}

/**
 * Gb_Form_Elem_Text
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Text  extends Gb_Form_Elem_Text_Abstract
{

    protected function getHtmlInInput()
    {
        return "type='text' class='text'";
    }

}