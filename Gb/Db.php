<?php

require_once("Zend/Db.php");

/**
 * Class Gb_Db
 *
 * @author Gilles Bouthenot
 * @version 1.01
 */
Class Gb_Db extends Zend_Db
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $conn;

  protected static $sqlTime=0;
  protected static $nbInstance_total=0;						// Nombre de classes gbdb ouvertes au total
  protected static $nbInstance_peak=0;						// maximum ouvertes simultan�ment
  protected static $nbInstance_current=0;					// nom d'instances ouvertes en ce moment

	/**
	 * Renvoie une nouvelle connexion
	 *
	 * type est le driver � utiliser (MYSQL, OCI8)
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
			throw new Gb_Exception($e->getMessage());
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
	 * @param string[optional] $index Si sp�cifi�, utilise la colonne comme cl�
	 * @param string[optional] $col Si sp�cifi�, ne renvoie que cette colonne
	 *
	 * @return array|string
	 * @throws Gb_Exception
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
			throw new Gb_Exception($e);
		}

	}

	/**
	 * Renvoie la premi�re ligne d'un select
	 *
	 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
	 * @param array[optional] $bindargurment exemple array("PE2")
	 * @param string[optional] $col Si sp�cifi�, renvoie directement la valeur
	 *
	 * @return array|string|false
	 * @throws Gb_Exception
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
			throw new Gb_Exception($e);
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
	 * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
	 * @param array[optional] $where array("col='val'", ...)
	 * @return int nombre de lignes modifi�es
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
	 * @return int nombre de lignes modifi�es
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
	 * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
	 * @return int nombre de lignes modifi�es
	 */
	public function insert($table, array $data)
	{
		$time=microtime(true);
		$ret=$this->conn->insert($table, $data);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Regarde Combien de lignes correspondant � $where existe dans la table $data
	 * 0?: ins�re nouvelle ligne. 1?: met � jour la ligne. 2+: Throws exception
	 *
	 *
	 * @param string $table Table � mettre � jour
	 * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
	 * @param array[optional] $where array("col='val'", ...)
	 * @throws Gb_Exception
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
					if ($pos===false)	throw new Gb_Exception("= introuvable dans clause where !");
					$col=substr($w, 0, $pos);
					$val=substr($w, $pos+1);
					//enl�ve les quote autour de $val
					if     (substr($val,0,1)=="'" && substr($val,-1)=="'")	$val=substr($val, 1, -1);
					elseif (substr($val,0,1)=='"' && substr($val,-1)=='"')	$val=substr($val, 1, -1);
					else throw new Gb_Exception("Pas de guillements trouv�s dans la clause where !");
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
				throw new Gb_Exception("replace impossible: plus d'une ligne correspond !");
			}
		} catch (Exception $e)
		{
			self::$sqlTime+=microtime(true)-$time;
			throw new Gb_Exception($e);
		}
	}

	/**
	 * Quote une cha�ne
	 *
	 * @param string $var Chaine � quoter
	 * @return string Chaine quot�e
	 */
	public function quote($var)
	{
		$time=microtime(true);
		$ret=$this->conn->quote($var);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Quote une/des valeur(s) dans une chaine
	 *
	 * @param string $text ex SELECT * WHERE uid=?
	 * @param  string/array $value ex login (pas de quote !)
	 * @return string chaine quot�e
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
			$text=$this->conn->quoteInto($text, $value);
		}

		self::$sqlTime+=microtime(true)-$time;
		return $text;
	}

	/**
	 * Quote an identifier
	 *
	 * @param string|array|Zend_Db_expr $ident
	 * @param boolean $auto
	 * @return string the quoted string
	 */
	public function quoteIdentifier($ident, boolean $auto)
	{
		$time=microtime(true);
		$ret=$this->conn->quoteIdentifier($ident, $auto);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Enter description here...
	 *
	 * @param string[optional] $tableName
	 * @param string[optional] $primaryKey
	 * @return unknown
	 */
	public function lastInsertId($tableName=null, $primaryKey=null)
	{
		$time=microtime(true);
		$ret=$this->conn->lastInsertId($tableName, $primaryKey);
		self::$sqlTime+=microtime(true)-$time;
		return $ret;
	}

	/**
	 * Renvoit la valeur suivant d'une s�quence
	 *
	 * 	la table doit etre de la forme::
	 * 	create table seq_sise_numero (id int not null);
	 * 	insert into seq_sise_numero values (0);
	 * 	update seq_sise_numero set id=LAST_INSERT_ID(id+1);
	 *
	 * @param string $tableName
	 * @param string[optionel] $colName
	 * @throws Gb_Exception
	 */
	public function sequenceNext($tableName, $colName="id")
	{
		$time=microtime(true);
		$nb=$this->update($tableName, array( $colName=>new Zend_Db_Expr("LAST_INSERT_ID(".$this->conn->quoteIdentifier($colName)."+1)") ));
		if ($nb!=1) {
			throw new Gb_Exception("erreur sequenceNext($tableName.$colName)");
		}
		self::$sqlTime+=microtime(true)-$time;
		return $this->conn->lastInsertId();
	}

	/**
	 * Renvoit la valeur courante d'une s�quence
	 *
	 * @param string $tableName
	 * @param string[optionel] $colName
	 * @throws Gb_Exception
	 */
	public function sequenceCurrent($tableName, $colName="id")
	{
		$time=microtime(true);
		$sql="SELECT ".$this->conn->quoteIdentifier($colName)." FROM ".$this->conn->quoteIdentifier($tableName);
		$stmt=$this->conn->query($sql);
		$res=$stmt->fetch(Zend_Db::FETCH_NUM);
		if ($stmt->fetch(Zend_Db::FETCH_NUM)) {
			throw new Gb_Exception("erreur sequenceCurrent($tableName.$colName)");
		}
		$res=$res[0];
		self::$sqlTime+=microtime(true)-$time;
		return $res;
	}

}
