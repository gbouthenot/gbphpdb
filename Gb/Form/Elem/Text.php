<?php

/**
 * Gb_Form_Elem_Text_Text
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