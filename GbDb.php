<?php

require_once("Zend/Db.php");

/**
 * Class GbDb
 *
 * @author Gilles Bouthenot
 * @version 1.01
 *
 * @todo retrieve_one
 */
Class GbDb extends Zend_Db
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $conn;

  protected static $sqlTime=0;
  protected static $nbInstance_total=0;						// Nombre de classes gbdb ouvertes au total
  protected static $nbInstance_peak=0;						// maximum ouvertes simultanément
  protected static $nbInstance_current=0;					// nom d'instances ouvertes en ce moment

	/**
	 * Renvoie une nouvelle connexion
	 *
	 * type est le driver à utiliser (MYSQL, OCI8)
	 *
	 * @param array("type"=>"MYSQL/OCI8", "host"=>"", "user"=>"", "pass"=>"", "name"=>"") $aIn
	 * @return GbDb
	 */
	function __construct(array $aIn)
	{
		$time=microtime(true);
		$user=$pass=$name="";
		$driver=$aIn["type"];
		$host=$aIn["host"];
		if (isset($aIn["user"]))						$user=$aIn["user"];
		if (isset($aIn["pass"]))						$pass=$aIn["pass"];
		if (isset($aIn["name"]))						$name=$aIn["name"];
		if     (strtoupper($driver)=="MYSQL")				$driver="Pdo_Mysql";
		elseif (strtoupper($driver)=="OCI8")				$driver="Pdo_Oci";
		elseif (strtoupper($driver)=="OCI")					$driver="Pdo_Oci";
		elseif (strtoupper($driver)=="PDO_OCI")			$driver="Pdo_Oci";
		elseif (strtoupper($driver)=="PDO_MYSQL")		$driver="Pdo_Mysql";
		elseif (strtoupper($driver)=="MYSQLI")			$driver="Pdo_Mysql";
		elseif (strtoupper($driver)=="ORACLE")			$driver="Pdo_Oci";

		try
		{
			$this->conn=Zend_Db::factory($driver, array("host"=>$host, "username"=>$user, "password"=>$pass, "dbname"=>$name));
			$conn=$this->conn->getConnection();
			if ($driver=="Pdo_Oci")
			{
				$conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
			}
		} catch (Exception $e)
		{
			self::$sqlTime+=microtime(true)-$time;
			throw new GbUtilException($e->getMessage());
		}

		self::$nbInstance_total++;
		self::$nbInstance_current++;
		self::$nbInstance_peak=max(self::$nbInstance_peak, self::$nbInstance_current);
		self::$sqlTime+=microtime(true)-$time;
	}

	function __destruct()
	{
		$time=microtime(true);
		self::$nbInstance_current--;
		$this->conn->closeConnection();
		self::$sqlTime+=microtime(true)-$time;  
	}


	public static function get_nbInstance_peak()
	{
		return self::$nbInstance_peak;
	}

	public static function get_nbInstance_total()
	{
		return self::$nbInstance_total;
	}

	public static function get_sqlTime()
	{
		return self::$sqlTime;
	}


	function fetchAll($a, $b)
	{
		$time=microtime(true);
		$ret=$this->conn->fetchAll($a, $b);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	function fetchAssoc($a, $b)
	{
		$time=microtime(true);
		$ret=$this->conn->fetchAssoc($a, $b);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	function query($sql, $bindarguments=array())
	{
		$time=microtime(true);
		$ret=$this->conn->query($sql, $bindargument);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}


	/**
	 * Execute du sql (PDO seulement)
	 */
	function exec($sql)
	{
		$time=microtime(true);
		$ret=$this->conn->getConnection()->exec($sql);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Renvoie toutes les lignes d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargurment exemple array("PE2")
	 * @param string[optional] $index Si spécifié, utilise la colonne comme clé
	 * @param string[optional] $col Si spécifié, ne renvoie que cette colonne
	 *
	 * @return array|string
	 * @throws GbUtilException
	 *
	 *  exemple:
	 *
	 * $dbGE=new GbDb(array("type"=>"mysql", "host"=>"127.0.0.1", "user"=>"gestion_e", "pass"=>"***REMOVED***" ,"base"=>"gestion_e", "notPersistent"=>false));
	 * $a=$dbGE->retrieve_one("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login");
	 *  renvoie "1erlogin"
	 *
	 * $a=$dbGE->retrieve_one("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "");
	 *  renvoie array("usa_login=>"1er login", "usa_idprothee"=>123)
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "", "");
	 *  renvoie array[0]=("usa_login=>"1er login", "usa_idprothee"=>123)
	 *  renvoie array[n]=("usa_login=>"2nd login", "usa_idprothee"=>456)
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login", "");
	 *  renvoie array["1erlogin"]=("usa_idprothee"=>123)
	 *  renvoie array["2ndlogin"]=("usa_idprothee"=>456)
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "usa_login", "usa_idprothee");
	 *  renvoie array["1erlogin"]=123
	 *  renvoie array["2ndlogin"]=456
	 *
	 * $a=$dbGE->retrieve_all("SELECT * FROM tusager WHERE usa_statut=?", array("ARC07PE1"), "", "usa_idprothee");
	 *  renvoie array[0]=123
	 *  renvoie array[1]=456
	 */
	public function retrieve_all($sql, $bindargurment=array(), $index="", $col="")
	{
		$time=microtime(true);

		if ($bindargurment===False)
			$bindargurment=array();

		try
		{
			/**
			 * @var Zend_Db_Statement
			 */
			$stmt=$this->conn->query($sql, $bindargurment);

			$fCol=(strlen($col)>0);						// True si on veut juste une valeur
			$fIdx=(strlen($index)>0);					// True si on veut indexer

			$ret=array();
			if ($fCol && !$fIdx)
			{	// on veut array[numero]=valeur de la colonne
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
					$ret[]=$res[$col];
			}
			elseif ($fCol && $fIdx)
			{	// on veut array[index]=valeur de la colonne
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
				{
					$key=$res[$index];
					unset($res[$index]);
					$ret[$key]=$res[$col];
				}
			}
			elseif (!$fCol && !$fIdx)
			{	//on veut juste un array[numero]=array(colonnes=>valeur)
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
					$ret[]=$res;
			}
			elseif (!$fCol && $fIdx)
			{	//on veut un array[index]=array(colonnes=>valeur)
				while ($res= $stmt->fetch(Zend_Db::FETCH_ASSOC) )
				{
					$key=$res[$index];
					unset($res[$index]);
					$ret[$key]=$res;
				}
			}

			self::$sqlTime+=microtime(true)-$time;
			return $ret;
		} catch (Exception $e){
			self::$sqlTime+=microtime(true)-$time;
			throw new GbUtilException($e);
		}

	}

	/**
	 * Renvoie la première ligne d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargurment exemple array("PE2")
	 * @param string[optional] $col Si spécifié, renvoie directement la valeur
	 *
	 * @return array|string|false
	 * @throws GbUtilException
	 */
	public function retrieve_one($sql, $bindargurment=array(), $col="")
	{
		$time=microtime(true);

		if ($bindargurment===False)
			$bindargurment=array();
		try
		{
			$stmt=$this->conn->query($sql, $bindargurment);

			$fCol=(strlen($col)>0);						// True si on veut juste une valeur

			$ret=array();
			if ($fCol)
			{	// on veut juste la valeur
				$res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
				if ($res===false) {
					self::$sqlTime+=microtime(true)-$time;
					return false;
				}
				$ret=$res[$col];
			}
			else
			{	//on veut un array
				$res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
				if ($res===false) {
					self::$sqlTime+=microtime(true)-$time;
					return false;
				}
				$ret=$res;
			}
			self::$sqlTime+=microtime(true)-$time;
			return $ret;
		} catch (Exception $e){
			self::$sqlTime+=microtime(true)-$time;
			throw new GbUtilException($e);
		}
	}

	public function beginTransaction()
	{
		$time=microtime(true);
		$ret=$this->conn->beginTransaction();
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	public function rollBack()
	{
		$time=microtime(true);
		$ret=$this->conn->rollBack();
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	public function commit()
	{
		$time=microtime(true);
		$ret=$this->conn->commit();
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * SQL update
	 *
	 * @param string $table
	 * @param array $data array("col"=>"val"...)
	 * @param array[optional] $where array("col='val'", ...)
	 * @return int nombre de lignes modifiées
	 */
	public function update($table, array $data, array $where=array())
	{
		$time=microtime(true);
		$ret=$this->conn->update($table, $data, $where);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * SQL delete
	 *
	 * @param string $table
	 * @param array[optional] $where array("col='val'", ...)
	 * @return int nombre de lignes modifiées
	 */
	public function delete($table, array $where)
	{
		$time=microtime(true);
		$ret=$this->conn->delete($table, $where);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * SQL insert
	 *
	 * @param string $table
	 * @param array $data array("col"=>"val"...)
	 * @return int nombre de lignes modifiées
	 */
	public function insert($table, array $data)
	{
		$time=microtime(true);
		$ret=$this->conn->insert($table, $data);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Regarde Combien de lignes correspondant à $where existe dans la table $data
	 * 0?: insére nouvelle ligne. 1?: met à jour la ligne. 2+: Throws exception
	 *
	 *
	 * @param string $table Table à mettre à jour
	 * @param array $data Données à modifier
	 * @param array[optional] $where array("col='val'", ...)
	 * @throws GbUtilException
	 */
	public function replace($table, array $data, array $where)
	{
		$time=microtime(true);
		try {
			// compte le nombre de lignes correspondantes
			$select=$this->conn->select();
			$select->from($table, array("A"=>"COUNT(*)"));
			foreach ($where as $w) {
				$select->where($w);
			}
			$stmt=$this->conn->query($select);
			$nb=$stmt->fetchAll();
			$nb=$nb[0]["A"];
			if ($nb==0) {
				// Insertion nouvelle ligne: ajoute le where array("col=val"...)
				foreach ($where as $w) {
					$pos=strpos($w, '=');
					if ($pos===false)	throw new GbUtilException("= introuvable dans clause where !");
					$col=substr($w, 0, $pos);
					$val=substr($w, $pos+1);
					//enlève les quote autour de $val
					if     (substr($val,0,1)=="'" && substr($val,-1)=="'")	$val=substr($val, 1, -1);
					elseif (substr($val,0,1)=='"' && substr($val,-1)=='"')	$val=substr($val, 1, -1);
					else throw new GbUtilException("Pas de guillements trouvés dans la clause where !");
					$data[$col]=$val;
				}
				$ret=$this->conn->insert($table, $data);
				self::$sqlTime+=microtime(true)-$time;
				return $ret;
			}
			elseif ($nb==1) {
				$ret=$this->conn->update($table, $data, $where);
				self::$sqlTime+=microtime(true)-$time;
				return $ret;
			}
			else {
				self::$sqlTime+=microtime(true)-$time;
				throw new GbUtilException("replace impossible: plus d'une ligne correspond !");
			}
		} catch (Exception $e)
		{
			self::$sqlTime+=microtime(true)-$time;
			throw new GbUtilException($e);
		}
	}

	/**
	 * Quote une chaîne
	 *
	 * @param string $var Chaine à quoter
	 * @return string Chaine quotée
	 */
	public function quote($var)
	{
		$time=microtime(true);
		$ret=$this->conn->quote($var);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Quote une valeur dans une chaine
	 *
	 * @param string $text ex SELECT * WHERE uid=?
	 * @param  string/array $value ex login (pas de quote !)
	 * @return string chaine quotée
	 */
	public function quoteInto($text, $value)
	{
		$time=microtime(true);
		if (is_array($value)) {
			foreach($value as $val) {
				$pos = strpos($text, "?");
				if ($pos=== false) {
					break;
				} else {
					$text=substr($text, 0, $pos).$val.substr($text, $pos+1);
				}
			}
		} else {
			$ret=$this->conn->quoteInto($text, $value);
			self::$sqlTime+=microtime(true)-$time;
			return $ret;
		}
	}
}
