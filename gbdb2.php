<?php
// DEPRECATED !!!

require_once("adodb5/adodb-exceptions.inc.php");
require_once("adodb5/adodb.inc.php");


class GbdbException extends Exception
{
	public function __toString()
	{
		//	$message=__CLASS__ . ": [{$this->code}]: {$this->message}";
		$message=__CLASS__ . ": \n";
		$trace=$this->getTrace();
		$file=$trace[0]["file"];
		$line=$trace[0]["line"];
		$function=$trace[0]["function"];
		$message.="Erreur dans $function(...), appelée par la ligne $line de $file.\n";
		return $message;
	}
}



Class Gbdb2 extends ADOConnection
{
	/**
	 *  @var ADOConnection
  */
	protected  $conn;

	/**
	 * Renvoie une nouvelle connexion
	 *
	 * type est le driver adoDB à utiliser
	 *
	 * @param array("type"=>driver,"host"=>"","name"=>""[,"user"=>"","pass"=>"","notPersistent"=>false]) $aIn3
	 * @return gbdb2
	 */
	function Gbdb2($aIn)
	{
		$prevtime=microtime(true);
		$type=strtolower($aIn["type"]);
		if ($type=="oci")	$type="oci8po";
		$user=$pass=$base="";
		$notPersistent=false;
		if (isset($aIn["user"]))						$user=$aIn["user"];
		if (isset($aIn["pass"]))						$pass=$aIn["pass"];
		if (isset($aIn["notPersistent"]))		$notPersistent=$aIn["notPersistent"];

		try
		{
			$this->conn=ADONewConnection($type);
			$this->conn->connectSID=true;
			$this->conn->autoCommit=false;

			if ($notPersistent)
			{
				$this->conn->NConnect($aIn["host"], $user, $pass, $aIn["name"]);
			}
			else
			{
				$this->autoRollback=true;
				$this->conn->PConnect($aIn["host"], $user, $pass, $aIn["name"]);
			}
		} catch (Exception $e)
		{
			throw new GbdbException($e->getMessage());
		}
		$GLOBALS["globals"]["sqlTime"]+=microtime(true)-$prevtime;
	}

	/**
	 * Renvoie la première ligne d'un select
	 * Si $col est spécifié, renvoie directement la valeur
	 *
	 * Ne modifie pas le fetchmode, et ferme le resultset
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array|false $bindargurment exemple array("PE2")
	 * @param string $col
	 *
	 * @return array|string
	 */
	function retrieve_one($sql, $bindargurment=false, $col="")
	{
		$prevtime=microtime(true);
		try
		{
			$oldfetch=$this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
			$rs=$this->conn->Execute($sql, $bindargurment);
			$this->conn->SetFetchMode($oldfetch);

			$fCol=(strlen($col)>0);						// True si on veut juste une valeur

			if (!$rs && $fCol)
				$ret=false;											// Pas de résultat: renvoie false (on s'attend à un string)
			elseif (!$rs)
				$ret=array();										// Pas de résultat: renvoie un tableau vide
			elseif ($fCol)
			{
				$ret=$rs->FetchRow();
				$ret=$ret[$col];
				$rs->Close();
			}
			else
			{
				$ret=$rs->FetchRow();						// On veut un array
				$rs->Close();
			}

			$GLOBALS["globals"]["sqlTime"]+=microtime(true)-$prevtime;
			return $ret;

		} catch (Exception $e){
			throw new GbdbException($e);
		}
	}

	/**
	 * Renvoie toutes les lignes d'un select
	 * Si $index est spécifié, utilise la colonne comme clé
	 * Si $col est spécifié, ne renvoie que cette colonne
	 *
	 * Ne modifie pas le fetchmode, et ferme le resultset
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array|false $bindargurment exemple array("PE2")
	 * @param string $index
	 * @param string $col
	 *
	 * @return array|string
	 */
	function retrieve_all($sql, $bindargurment=false, $index="", $col="")
	{
		$prevtime=microtime(true);
		try
		{
			$oldfetch=$this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
			$rs=$this->conn->Execute($sql, $bindargurment);
			$this->conn->SetFetchMode($oldfetch);

			$fCol=(strlen($col)>0);						// True si on veut juste une valeur
			$fIdx=(strlen($index)>0);					// True si on veut indexer

			$ret=array();

			if (!$rs)
				$ret=array();										// Pas de résultat: renvoie un tableau vide
			else
			{	// $rs existe
				if ($fCol && !$fIdx)
				{	// on veut array[numero]=valeur de la colonne
					while ($res= $rs->FetchRow() )
						$ret[]=$res[$col];
				}
				elseif ($fCol && $fIdx)
				{	// on veut array[index]=valeur de la colonne
					while ($res= $rs->FetchRow() )
					{
						$key=$res[$index];
						unset($res[$index]);
						$ret[$key]=$res[$col];
					}
				}
				elseif (!$fCol && !$fIdx)
				{	//on veut juste un array[numero]=array(colonnes=>valeur)
					while ($res= $rs->FetchRow() )
						$ret[]=$res;
				}
				elseif (!$fCol && $fIdx)
				{	//on veut un array[index]=array(colonnes=>valeur)
					while ($res= $rs->FetchRow() )
					{
						$key=$res[$index];
						unset($res[$index]);
						$ret[$key]=$res;
					}
				}
				$rs->Close();
			}

			$GLOBALS["globals"]["sqlTime"]+=microtime(true)-$prevtime;
			return $ret;

		} catch (Exception $e){
			throw new GbdbException($e);
		}

	}

}


//exemple:
//$dbGE=new gbdb2(array("type"=>"mysql", "host"=>"127.0.0.1", "user"=>"gestion_e", "pass"=>"***REMOVED***" ,"base"=>"gestion_e", "notPersistent"=>false));
//$a=$dbGE->retrieve_one("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login");
// renvoie "1erlogin"

//$a=$dbGE->retrieve_one("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "");
//  renvoie array("usa_login=>"1er login", "usa_idprothee"=>123)

//$a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "", "");
//  renvoie array[0]=("usa_login=>"1er login", "usa_idprothee"=>123)
//  renvoie array[n]=("usa_login=>"2nd login", "usa_idprothee"=>456)

//$a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login", "");
//  renvoie array["1erlogin"]=("usa_idprothee"=>123)
//  renvoie array["2ndlogin"]=("usa_idprothee"=>456)

//$a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login", "usa_idprothee");
//  renvoie array["1erlogin"]=123
//  renvoie array["2ndlogin"]=456

//$a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "", "usa_idprothee");
//  renvoie array[0]=123
//  renvoie array[1]=456

?>