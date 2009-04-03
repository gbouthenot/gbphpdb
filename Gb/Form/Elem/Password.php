<?php

/**
 * Gb_Form_Elem_Text_Password
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

class Gb_Form_Elem_Password extends Gb_Form_Elem_Text_Abstract
{
    
    protected function getHtmlInInput()
    {
        return "type='password' class='password'";
    }

}