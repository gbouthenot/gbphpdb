<?php

if (!defined("_GB_PATH")) {
	define("_GB_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);
}

require_once(_GB_PATH."Exception.php");
require_once("Zend/Db.php");

/**
 * Class Gb_Db
 *
 * @author Gilles Bouthenot
 */
Class Gb_Db extends Zend_Db
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $conn;
    protected $connArray;                                    // array utilisé par Zend_Db::factory()
    protected $driver;      							     // Pdo_Mysql ou Pdo_Oci
    protected $dbname;

    protected $tables;                                      // cache de la liste des tables
    protected $tablesDesc;                                  // cache descriptif table
	
    protected static $sqlTime=0;
    protected static $nbInstance_total=0;                    // Nombre de classes gbdb ouvertes au total
    protected static $nbInstance_peak=0;                     // maximum ouvertes simultanément
    protected static $nbInstance_current=0;                  // nom d'instances ouvertes en ce moment

    public function getAdapter()
    {
        return $this->conn;
    }

    public function getConnection()
    {
        return $this->conn->getConnection();
    }

    /**
     * Renvoie une nouvelle connexion
     *
     * type est le driver à utiliser (MYSQL, OCI8)
     *
     * @param array("type"=>"Pdo_Mysql/Pdo_Oci", "host"=>"localhost", "user/username"=>"", "pass/password"=>"", "name/dbname"=>"") $aIn
     * @return GbDb
     */
    function __construct(array $aIn)
    {
        $time=microtime(true);
        $user=$pass=$name="";
        $host="";
        $driver="Pdo_Mysql";
        if (isset($aIn["type"]))                    $driver=$aIn["type"];
        if (isset($aIn["host"]))                    $host=$aIn["host"];
        if (isset($aIn["user"]))                    $user=$aIn["user"];
        if (isset($aIn["username"]))                $user=$aIn["username"];
        if (isset($aIn["pass"]))                    $pass=$aIn["pass"];
        if (isset($aIn["password"]))                $pass=$aIn["password"];
        if (isset($aIn["name"]))                    $name=$aIn["name"];
        if (isset($aIn["dbname"]))                  $name=$aIn["dbname"];
        if     (strtoupper($driver)=="MYSQL")       $driver="Pdo_Mysql";
        elseif (strtoupper($driver)=="OCI8")        $driver="Pdo_Oci";
        elseif (strtoupper($driver)=="OCI")         $driver="Pdo_Oci";
        elseif (strtoupper($driver)=="PDO_OCI")     $driver="Pdo_Oci";
        elseif (strtoupper($driver)=="PDO_MYSQL")   $driver="Pdo_Mysql";
        elseif (strtoupper($driver)=="MYSQLI")      $driver="Pdo_Mysql";
        elseif (strtoupper($driver)=="ORACLE")      $driver="Pdo_Oci";

        $array=array("username"=>$user, "password"=>$pass, "dbname"=>$name);
        if (strlen($host)) {
            $array["host"]=$host;
        }

        try
        {
            $this->conn=Zend_Db::factory($driver, $array);
            $conn=$this->conn->getConnection();
            if ($driver=="Pdo_Oci") {
                $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            }
        } catch (Exception $e) {
            self::$sqlTime+=microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }

        self::$nbInstance_total++;
        self::$nbInstance_current++;
        self::$nbInstance_peak=max(self::$nbInstance_peak, self::$nbInstance_current);
        self::$sqlTime+=microtime(true)-$time;

        $this->driver=$driver;
        $this->dbname=$name;
        $this->connArray=$array;
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

	public function getTables()
	{
        if ($this->tables==null) {
            switch($this->driver) {
                case "Pdo_Oci":
                    $sql_getTablesName="
                        SELECT OWNER || '.' || TABLE_NAME AS \"FULL_NAME\"
                        FROM ALL_TABLES
                        WHERE table_name NOT LIKE '%$%'
                        ORDER BY OWNER, TABLE_NAME";
                break;

                case "Pdo_Mysql":
                    $sql_getTablesName="
                        SELECT CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) AS 'FULL_NAME'
                        FROM information_schema.tables
                        WHERE TABLE_SCHEMA<>'information_schema' AND TABLE_SCHEMA<>'mysql'
                        ORDER BY TABLE_SCHEMA, TABLE_NAME";
                break;
            }

            $this->tables=$this->retrieve_all($sql_getTablesName, array(), "", "FULL_NAME");
        }
        return $this->tables;
	}
	
    public function getTableDesc($table)
    {
        $sqlTime=self::$sqlTime;
        $time=microtime(true);
        
        // détermine towner et tname
        $pos=strpos($table, ".");
        if ($pos!==false) {
            // la table est au format owner.tablename
            $towner=substr($table, 0, $pos);
            $tname=substr($table, $pos+1);
        } else {
            // la table est au format tablename: ajouter dbname
            $towner=$this->dbname;
            $tname=$table;
        }

        // renvoie le array caché si dispo
        if (isset($this->tablesDesc[$towner.".".$tname])) {
            return $this->tablesDesc[$towner.".".$tname];
        }

        switch($this->driver) {
            case "Pdo_Oci":
                $sql_getColumns="
                    SELECT COLUMN_NAME, DATA_TYPE AS \"TYPE\", NULLABLE, '' AS \"COMMENT\"
                    FROM ALL_TAB_COLUMNS
                    WHERE OWNER=? AND TABLE_NAME=?
                    ORDER BY COLUMN_ID";
                $sql_getPK="
                    SELECT COLUMN_NAME
                    FROM all_constraints allcons, all_cons_columns allcols
                    WHERE allcons.OWNER=? AND allcons.TABLE_NAME=?
                          AND CONSTRAINT_TYPE='P'
                          AND allcons.CONSTRAINT_NAME=allcols.CONSTRAINT_NAME
                    ORDER BY position";
                $sql_getFKs="
                    SELECT cols_src.COLUMN_NAME AS \"COLUMN_NAME\", cols_dst.TABLE_NAME || '.' || cols_dst.COLUMN_NAME AS \"FULL_NAME\"
                    FROM all_constraints cons_src
                    LEFT JOIN all_cons_columns cols_src ON (cols_src.constraint_name=cons_src.constraint_name)
                    LEFT JOIN all_cons_columns cols_dst ON (cols_dst.constraint_name=cons_src.r_constraint_name)
                    WHERE cons_src.owner=? AND cons_src.table_name=?
                          AND cons_src.constraint_type='R'
                    ORDER BY cols_dst.position";
            break;

            case "Pdo_Mysql":
                $sql_getColumns=   "
                    SELECT COLUMN_NAME, COLUMN_TYPE AS 'TYPE', IS_NULLABLE AS 'NULLABLE', COLUMN_COMMENT AS 'COMMENT'
                    FROM information_schema.columns
                    WHERE TABLE_SCHEMA=? AND TABLE_NAME=?
                    ORDER BY ORDINAL_POSITION";
                $sql_getPK="
                    SELECT COLUMN_NAME
                    FROM information_schema.key_column_usage
                    WHERE TABLE_SCHEMA=? AND TABLE_NAME=?
                          AND CONSTRAINT_NAME='PRIMARY'
                    ORDER BY ORDINAL_POSITION";
                $sql_getFKs="
                    SELECT kcu.COLUMN_NAME, CONCAT(kcu.REFERENCED_TABLE_NAME, '.', kcu.REFERENCED_COLUMN_NAME) AS 'FULL_NAME'
                    FROM information_schema.key_column_usage kcu
                    JOIN information_schema.TABLE_CONSTRAINTS tc
                         ON CONSTRAINT_TYPE='FOREIGN KEY'
                         AND kcu.CONSTRAINT_NAME=tc.CONSTRAINT_NAME
                         AND kcu.TABLE_NAME=tc.TABLE_NAME
                         AND kcu.TABLE_SCHEMA=tc.TABLE_SCHEMA
                    WHERE kcu.TABLE_SCHEMA=? AND kcu.TABLE_NAME=?
                    ORDER BY ORDINAL_POSITION";
            break;
        }

/*        $desc["columns"]=$this->retrieve_all($sql_getColumns, array($towner, $tname), "COLUMN_NAME", ""           );
        $desc["pk"]=     $this->retrieve_all($sql_getPK,      array($towner, $tname), "",            "COLUMN_NAME");
        $desc["fks"]=    $this->retrieve_all($sql_getFKs,     array($towner, $tname), "COLUMN_NAME", "FULL_NAME"  );
*/
        $desc["columns"]=$this->retrieve_all($sql_getColumns, array($towner, $tname));
        $desc["pk"]=     $this->retrieve_all($sql_getPK,      array($towner, $tname));
        $desc["fks"]=    $this->retrieve_all($sql_getFKs,     array($towner, $tname));
        
        // cache le résultat
        $this->tablesDesc[$towner.".".$tname]=$desc;
        
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $desc;
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
        $ret=$this->conn->query($sql, $bindarguments);
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

        if ($bindargurment===False) {
            $bindargurment=array();
        }

        try
        {
            /**
             * @var Zend_Db_Statement
             */
            $stmt=$this->conn->query($sql, $bindargurment);

            $fCol=(strlen($col)>0);   // True si on veut juste une valeur
            $fIdx=(strlen($index)>0); // True si on veut indexer

            $ret=array();
            if ($fCol && !$fIdx) {
                // on veut array[numero]=valeur de la colonne
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC)) ) {
                    $ret[]=$res[$col];
                }
            }
            elseif ($fCol && $fIdx) {
                // on veut array[index]=valeur de la colonne
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC)) ) {
                    $key=$res[$index];
                    unset($res[$index]);
                    $ret[$key]=$res[$col];
                }
            }
            elseif (!$fCol && !$fIdx) {
                //on veut juste un array[numero]=array(colonnes=>valeur)
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC)) ) {
                    $ret[]=$res;
                }
            }
            elseif (!$fCol && $fIdx) {
                //on veut un array[index]=array(colonnes=>valeur)
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC)) ) {
                    $key=$res[$index];
                    unset($res[$index]);
                    $ret[$key]=$res;
                }
            }
            self::$sqlTime+=microtime(true)-$time;
        } catch (Exception $e) {
            self::$sqlTime+=microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        return $ret;
    }

    /**
     * Renvoie la première ligne d'un select
     *
     * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
     * @param array[optional] $bindargurment exemple array("PE2")
     * @param string[optional] $col Si spécifié, renvoie directement la valeur
     *
     * @return array|string|false
     * @throws Gb_Exception
     */
    public function retrieve_one($sql, $bindargurment=array(), $col="")
    {
        $time=microtime(true);

        if ($bindargurment===False)
            $bindargurment=array();
        try {
            $stmt=$this->conn->query($sql, $bindargurment);

            $fCol=(strlen($col)>0); // True si on veut juste une valeur

            $ret=array();
            if ($fCol) {
                // on veut juste la valeur
                $res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
                if ($res===false) {
                    self::$sqlTime+=microtime(true)-$time;
                    return false;
                }
                $ret=$res[$col];
            } else {
                //on veut un array
                $res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
                if ($res===false) {
                    self::$sqlTime+=microtime(true)-$time;
                    return false;
                }
                $ret=$res;
            }
            self::$sqlTime+=microtime(true)-$time;
        } catch (Exception $e){
            self::$sqlTime+=microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        return $ret;
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
     * @param array[optional] $where array("col='val'", $db->quoteInto("usr_id=?", $usr_id), ...)
     * @return int nombre de lignes modifiées
     * @throws Gb_Exception
     */
    public function update($table, array $data, array $where=array())
    {
        $time=microtime(true);
        try {
            $ret=$this->conn->update($table, $data, $where);
        } catch (Exception $e) {
            self::$sqlTime+=microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime+=microtime(true)-$time;
        return $ret;
    }

    /**
     * SQL delete
     *
     * @param string $table
     * @param array[optional] $where array($db->quoteInto("col=?", "val"), ...)
     * @return int nombre de lignes modifiées
     * @throws Gb_Exception
     */
    public function delete($table, array $where=array())
    {
        $time=microtime(true);
        try {
            $ret=$this->conn->delete($table, $where);
        } catch (Exception $e) {
            self::$sqlTime+=microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime+=microtime(true)-$time;
        return $ret;
    }

    /**
     * SQL insert
     *
     * @param string $table
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @return int nombre de lignes modifiées
     * @throws Gb_Exception
     */
    public function insert($table, array $data)
    {
        $time=microtime(true);
        try {
            $ret=$this->conn->insert($table, $data);
        } catch (Exception $e) {
            self::$sqlTime+=microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime+=microtime(true)-$time;
        return $ret;
    }

   /**
     * Regarde Combien de lignes correspondant à $where existe dans la table $data
     * 0?: INSERT. 1?: UPDATE. 2+: Throws exception
     *
     * @param string $table Table à mettre à jour
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @param array[optional] $where array("col='val'", $db->quoteInto("usr_id=?", $usr_id), ...)
     * @throws Gb_Exception
     */
    public function replace($table, array $data, array $where=array())
    {
        $sqlTime=self::$sqlTime;
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
                // Aucune ligne existe: insertion nouvelle ligne: ajoute le where array("col=val"...)
                foreach ($where as $w) {
                    $pos=strpos($w, '=');
                    if ($pos===false) { throw new Gb_Exception("= introuvable dans clause where !"); }
                    $col=substr($w, 0, $pos);
                    $val=substr($w, $pos+1);
                    //enlève les quote autour de $val
                    if     (substr($val,0,1)=="'" && substr($val,-1)=="'") { $val=substr($val, 1, -1); }
                    elseif (substr($val,0,1)=='"' && substr($val,-1)=='"') { $val=substr($val, 1, -1); }
                    else { throw new Gb_Exception("Pas de guillements trouvés dans la clause where !");  }
                    $data[$col]=$val;
                }
                $ret=$this->conn->insert($table, $data);
            } elseif ($nb==1) {
                // Une ligne existe déjà: mettre à jour
                $ret=$this->conn->update($table, $data, $where);
            } else {
                // Plus d'une ligne correspond: erreur de clé ?
                throw new Gb_Exception("replace impossible: plus d'une ligne correspond !");
            }
        } catch (Gb_Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw $e;
        } catch (Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $ret;
    }

    
    
    
    
    

   /**
     * Insertion. Si l'insertion est impossible, supprime la ligne et la réinsere.
     *
     * @param string $table Table à mettre à jour
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @param array[optional] $where array("col='val'", $db->quoteInto("usr_id=?", $usr_id), ...)
     * @throws Gb_Exception
     */
    public function insertOrDeleteInsert($table, array $data)
    {
        $sqlTime=self::$sqlTime;
        $time=microtime(true);
        
        $where=array();
        $newdata=array();
        $this->developpeData($table, $data, $newdata, $where);
//        print_r($where);
//        print_r($newdata);
        
        // @todo NON NON et NON !!! Essayer d'insérer la ligne plutôt !!!
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
                // Aucune ligne existe: insertion nouvelle ligne
                $ret=$this->insert($table, $data);
                $ret;
            } elseif ($nb==1) {
                // Une ligne existe déjà: delete puis insert
                // @todo: transaction !
                $newdb=new Gb_Db(array_merge( array("driver"=>$this->driver), $this->connArray));
                try {
                    $newdb->beginTransaction();
                    $newdb->delete($table, $where);
                    $ret=$newdb->insert($table, $data);
                    $newdb->commit();
                } catch (Gb_Exception  $e) {
                    $newdb->rollBack();
                    throw $e;
                } catch (Exception  $e) {
                    $newdb->rollBack();
                    throw new Gb_Exception($e->getMessage());
                }
            } else {
                // Plus d'une ligne correspond: erreur de clé ?
                throw new Gb_Exception("replace impossible: plus d'une ligne correspond !");
            }
        } catch (Gb_Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw $e;
        } catch (Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $ret;
    }
    
    
    protected function developpeData($table, $data, &$newdata, &$where)
    {
        $sqlTime=self::$sqlTime;
        $time=microtime(true);

        $tableDesc=$this->getTableDesc($table);
        $aPk=array();
        $aPk1=$tableDesc["pk"];   // récupère array(0=>array("COLUMN_NAME"=>xxx), ...)
        foreach ($aPk1 as $aPk2) {
            $aPk[]=$aPk2["COLUMN_NAME"];    // transforme en array(xxx, ...)
        }
        
        $newdata=$data;
        // extrait les données de clé primaires contenues dans $data, et les déplace dans $where
        $where=array();
        foreach ($aPk as $key) {
            $val=$data[$key];
            $where[]=$key."=".$this->quote($val);
            unset($newdata[$key]);
        }

        self::$sqlTime=$sqlTime+microtime(true)-$time;
    }
    
   /**
     * @todo: commentaire
     *
     * @param string $table Table à mettre à jour
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @throws Gb_Exception
     */
    public function insertOrUpdateNOTWORKING($table, array $data)
    {
        $sqlTime=self::$sqlTime;
        $time=microtime(true);
        
        $where=array();
        $newdata=array();
        $this->developpeData($table, $data, $newdata, $where);

        // @todo NON NON et NON !!! Essayer d'insérer la ligne plutôt !!!
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
                // Aucune ligne existe: insertion nouvelle ligne
                $ret=$this->insert($table, $data);
            } elseif ($nb==1) {
                // Une ligne existe déjà: update
                $ret=$this->update($table, $newdata, $where);
            } else {
                // Plus d'une ligne correspond: erreur de clé ?
                throw new Gb_Exception("replace impossible: plus d'une ligne correspond !");
            }
        } catch (Gb_Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw $e;
        } catch (Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $ret;
    }
    
    public function insertOrUpdate($table, array $data)
    {
        $sqlTime=self::$sqlTime;
        $time=microtime(true);
        
        $where=array();
        $newdata=array();
        $this->developpeData($table, $data, $newdata, $where);

        // @todo NON NON et NON !!! Essayer d'insérer la ligne plutôt !!!
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
                // Aucune ligne existe: insertion nouvelle ligne
                $ret=$this->insert($table, $data);
            } elseif ($nb==1) {
                // Une ligne existe déjà: update
                $ret=$this->update($table, $newdata, $where);
            } else {
                // Plus d'une ligne correspond: erreur de clé ?
                throw new Gb_Exception("replace impossible: plus d'une ligne correspond !");
            }
        } catch (Gb_Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw $e;
        } catch (Exception $e) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw new Gb_Exception($e->getMessage());
        }
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $ret;
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
        if (is_object($var)) {
            $ret=$var;
        } else {
            $ret=$this->conn->quote($var);
        }
        self::$sqlTime+=microtime(true)-$time;
        return $ret;
    }

    /**
     * Quote une/des valeur(s) dans une chaine
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
                    $text=substr($text, 0, $pos).$this->quote($val).substr($text, $pos+1);
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
     * Renvoie le dernier id inséré
     *
     * @param string[optional] $tableName
     * @param string[optional] $primaryKey
     * @return integer
     */
    public function lastInsertId($tableName=null, $primaryKey=null)
    {
        $time=microtime(true);
        $ret=$this->conn->lastInsertId($tableName, $primaryKey);
        self::$sqlTime+=microtime(true)-$time;
        return $ret;
    }

    /**
     * Renvoie la valeur suivante d'une séquence
     *
     * 	la table doit etre de la forme::
     * 	create table seq_sise_numero (id int not null) ENGINE = 'MyIsam";
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
        $sqlTime=self::$sqlTime;
        
        $nb=$this->update($tableName, array( $colName=>new Zend_Db_Expr("LAST_INSERT_ID(".$this->conn->quoteIdentifier($colName)."+1)") ));
        if ($nb!=1) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw new Gb_Exception("erreur sequenceNext($tableName.$colName)");
        }
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $this->conn->lastInsertId();
    }

    /**
     * Renvoie la valeur courante d'une séquence
     *
     * @param string $tableName
     * @param string[optionel] $colName
     * @throws Gb_Exception
     */
    public function sequenceCurrent($tableName, $colName="id")
    {
        $sqlTime=self::$sqlTime;
        $time=microtime(true);
        $sql="SELECT ".$this->conn->quoteIdentifier($colName)." FROM ".$this->conn->quoteIdentifier($tableName);
        $stmt=$this->conn->query($sql);
        $res=$stmt->fetch(Zend_Db::FETCH_NUM);
        if ($stmt->fetch(Zend_Db::FETCH_NUM)) {
            self::$sqlTime=$sqlTime+microtime(true)-$time;
            throw new Gb_Exception("erreur sequenceCurrent($tableName.$colName)");
        }
        $res=$res[0];
        self::$sqlTime=$sqlTime+microtime(true)-$time;
        return $res;
    }

}
