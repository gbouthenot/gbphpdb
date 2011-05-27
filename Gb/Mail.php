<?php
/**
 * Gb_Mail
 * 
 * @author Gilles Bouthenot
 * @version $Revision$
 * @Id $Id$
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== dirname(__FILE__).DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}

require_once(_GB_PATH."Exception.php");


class Gb_Mail
{

    /**
     * Renvoie la révision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
     *
     * @return boolean|integer
     * @throws Gb_Exception
     */
    public static function getRevision($mini=null, $throw=true)
    {
        $revision='$Revision$';
        $revision=(int) trim(substr($revision, strrpos($revision, ":")+2, -1));
        if ($mini===null) { return $revision; }
        if ($revision>=$mini) { return true; }
        if ($throw) { throw new Gb_Exception(__CLASS__." r".$revision."<r".$mini); }
        return false;
    }
        
    /**
     * Envoie un mail en utilisant le smtp local
     *
     * @param string|array $to destinataires, séparés par virgule
     * @param string $sujet
     * @param string $bodytext
     * @param string $from
     * @param string|array[optional] $bcc destinataires, séparés par virgule
     * @param string|array[optional] $cc destinataires, séparés par virgule
     * @param string[optional] $charset charset
     * @param string[optional] $bodyhtml charset
     * 
     * @return boolean
     */
    public static function mymail($to, $sujet, $bodytext, $from, $bcc=array(), $cc=array(), $charset='UTF-8', $bodyhtml=null)
    {
        require_once 'Zend/Mail.php';
        require_once 'Zend/Mail/Transport/Smtp.php';
        
        $transport=new Zend_Mail_Transport_Smtp("127.0.0.1");
        
        if (is_string($to)) {
            $to=explode(",", $to);
        }
        if (is_string($bcc)) {
            $bcc=explode(",", $bcc);
        }
        if (is_string($cc)) {
            $cc=explode(",", $cc);
        }

        $from=str_ireplace("From: ", "", $from);

        $mail=new Zend_Mail();

        foreach ($to as $t) {
            if (strlen($t)) {
                $mail->AddTo($t);
            }
        }
    
        foreach ($cc as $t) {
            if (strlen($t)) {
                $mail->addCc($t);
            }
        }

        foreach ($bcc as $t) {
            if (strlen($t)) {
                $mail->addBcc($t);
            }
        }
    
        $mail->setFrom($from, substr($from, 0, strpos($from, "@")));
        if (strlen($bodytext)) {
            $mail->setBodyText($bodytext, $charset);
        }
        if (strlen($bodyhtml)) {
            $mail->setBodyHtml($bodyhtml, $charset);
        }

        $mail->setSubject($sujet);

        return $mail->send($transport);
    }
}
