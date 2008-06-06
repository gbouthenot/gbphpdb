<?php
/**
 * 
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

class Gb_Mail
{

    /**
     * Envoie un mail en utilisant le smtp local
     *
     * @param string $to destinataires, séparés par virgule
     * @param string $sujet
     * @param string $body
     * @param string $from
     * @param string[optional] $bcc destinataires, séparés par virgule
     * 
     * @return boolean
     */
    public static function mymail($to, $sujet, $body, $from, $bcc="")
    {
        require_once 'Zend/Mail.php';
        require_once 'Zend/Mail/Transport/Smtp.php';
        
        $transport=new Zend_Mail_Transport_Smtp("127.0.0.1");
        
        $aTo=explode(",", $to);
        $aBcc=explode(",", $bcc);
        $from=str_ireplace("From: ", "", $from);

        $mail=new Zend_Mail();

        foreach ($aTo as $to) {
            if (strlen($to)) {
                $mail->AddTo($to);
            }
        }
    
        foreach ($aBcc as $to) {
            if (strlen($to)) {
                $mail->addBcc($to);
            }
        }
    
        $mail->setFrom($from, substr($from, 0, strpos($from, "@")));
        $mail->setBodyText($body);
        $mail->setSubject($sujet);
        return $mail->send($transport);
    }
}
