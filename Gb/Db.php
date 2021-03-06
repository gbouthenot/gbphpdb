<?php
/**
 * Gb_Db
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
require_once(_GB_PATH."Glue.php");
require_once(_GB_PATH."Log.php");
require_once(_GB_PATH."Util.php");
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
    protected $_adapter;
    protected $connArray;                                     // array utilisé par Zend_Db::factory()
    protected $driver;                                        // Pdo_Mysql, Pdo_Oci, Pdo_Sqlite, Oracle
    protected $dbname;
    protected $charset;
    protected $fTransaction=false;
    protected $fInitialized=false;
    protected $instanceNumber=null;                             // starts at 0
    protected $fLogEnabled=true;

    /**
     * @var Gb_Cache
     */
    protected $_cache = null;

    protected $tables;                                        // cache de la liste des tables
    protected $tablesDesc;                                    // cache descriptif table

    protected static $sqlTime=0;                             // global time spent in Gb_Db
    protected static $aSqlTime=array();                      // per instance time spent
    protected static $nbInstance_total=0;                    // Nombre de classes gbdb ouvertes au total
    protected static $nbInstance_peak=0;                     // maximum ouvertes simultanément
    protected static $nbInstance_current=0;                  // nom d'instances ouvertes en ce moment
    protected static $nbRequest=0;                           // Nombre de requetes effectuées
    protected static $sqlLog=array();                        // log de toutes les requêtes effectuées, par instance

    protected static $fPluginRegistred=false;

    /**
     * Renvoie la revision de la classe ou un boolean si la version est plus petite que précisée, ou Gb_Exception
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
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        $this->initialize();
        return $this->_adapter;
    }

    /**
     * Returns the underlying database connection object or resource.
     * @return object|resource|null
     */
    public function getConnection()
    {
        $this->initialize();
        return $this->_adapter->getConnection();
    }

    /**
     * Renvoie une nouvelle connexion
     *
     * type est le driver à utiliser (MYSQL, OCI8)
     *
     * @param array("type"=>"Pdo_Mysql/Pdo_Oci/Pdo_Sqlite/Pdo_Pgsql/Oracle_Oci8", "host"=>"localhost", "user/username"=>"", "pass/password"=>"", "name/dbname"=>"", "port"=>"", "charset"=>"utf8", "log"=>"true|false") $aIn
     * @return GbDb
     */
    function __construct(array $aIn)
    {
        $time=microtime(true);
        $user=$pass=$name=$port=$charset="";
        $host="";
        $driver="Pdo_Mysql";
        $log = true;
        if (isset($aIn["type"]))                    $driver=$aIn["type"];
        if (isset($aIn["host"]))                    $host=$aIn["host"];
        if (isset($aIn["user"]))                    $user=$aIn["user"];
        if (isset($aIn["username"]))                $user=$aIn["username"];
        if (isset($aIn["pass"]))                    $pass=$aIn["pass"];
        if (isset($aIn["password"]))                $pass=$aIn["password"];
        if (isset($aIn["name"]))                    $name=$aIn["name"];
        if (isset($aIn["dbname"]))                  $name=$aIn["dbname"];
        if (isset($aIn["port"]))                    $port=$aIn["port"];
        if (isset($aIn["charset"]))                 $charset=$aIn["charset"];
        if (isset($aIn["log"])
            && in_array($aIn["log"], array("false", false))) $log=false;

        $array = array("username"=>$user, "password"=>$pass, "dbname"=>$name);
        if (strlen($host)) {
            $array["host"]=$host;
        }
        if (strlen($port)) {
            $array["port"]=$port;
        }
        if (strlen($charset)) {
            $array["charset"]=$charset;
        }

        $this->instanceNumber = self::$nbInstance_total;
        self::$aSqlTime[$this->instanceNumber] = 0;

        switch (strtoupper($driver)) {
            case "MYSQL":
            case "MYSQLI":
            case "PDO_MYSQL":
                $driver="Pdo_Mysql"; break;

            case "OCI8":
            case "OCI":
            case "ORACLE":
            case "PDO_OCI":
                $driver="Pdo_Oci"; break;

            case "ORACLE_OCI8":
                // see http://www.php.net/manual/en/function.oci-connect.php
                $array["dbname"] = $host .
                    (($port !== "") ? ":$port" : "") .
                    "/$name";

                // http://php.net/manual/en/function.oci-connect.php#105284
                // seem that that name is not always the same as service name, so,
                // don't use this way of connecting, but the older one:
                $address = "ADDRESS=(PROTOCOL=TCP)(HOST = $host)";
                $address .= ($port !== "") ? ("(PORT = $port)") : ("");
                $cdata = "SID = $name";
                $array["dbname"] =  "
(DESCRIPTION=
  (ADDRESS_LIST=($address))
  (CONNECT_DATA=($cdata))
)";
                unset($array["host"]);
                unset($array["port"]);
                $driver="Oracle"; break;

            case "SQLITE":
            case "PDO_SQLITE":
                $driver="Pdo_Sqlite"; break;

            case "POSTGRES":
            case "POSTGRESQL":
            case "PDO_PGSQL":
                $driver="Pdo_Pgsql"; break;
        }

        try
        {
            $this->_adapter=Zend_Db::factory($driver, $array);
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }

        $this->log("CONNECT TO " . (strlen($host)?("$host:"):("")) . $name);
        self::$nbInstance_total++;
        self::$nbInstance_current++;
        self::$nbInstance_peak=max(self::$nbInstance_peak, self::$nbInstance_current);

        if (!self::$fPluginRegistred)
        {
            Gb_Glue::registerPlugin("Gb_Response_Footer", array(__CLASS__, "GbResponsePlugin"));
            self::$fPluginRegistred=true;
        }

        $this->driver=$driver;
        $this->dbname=$name;
        $this->connArray=$array;
        $this->charset=$charset;
        $this->fLogEnabled=$log;
        $this->addTime(microtime(true)-$time);
    }

    function __destruct()
    {
        $time=microtime(true);
        self::$nbInstance_current--;
        $this->_adapter->closeConnection();
        $this->addTime(microtime(true)-$time);
    }

   /**
    * set cache backend
    * @param Gb_Cache $cache
    * @return Gb_Db provides fluent interface
    */
    public function setCache(Gb_Cache $cache)
    {
        $this->_cache = $cache;
        return $this;
    }

   /**
    * get cache backend
    * @return Gb_Cache
    */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * get instance number (start at 0)
     * @return number
     */
    public function getInstanceNumber() {
        return $this->instanceNumber;
    }

    /**
     * Log the request
     * @param string $str
     */
    public function log($str, $o=null) {
        if ($this->fLogEnabled === false) {
            return;
        }
        $str = trim($str);
        $str = str_replace("\n", " ", $str);
        if ($o !== null) { $str .= " "; }
        self::$sqlLog[$this->instanceNumber][] = $str . Gb_Log::dump($o);
    }

    /**
     * Add time spent
     * @param float $time
     */
    protected function addTime($time) {
        self::$sqlTime += $time;
        self::$aSqlTime[$this->instanceNumber] += $time;
    }

    /**
     * get/set log
     * @param boolean[optional] $param
     * @return boolean
     */
    public function enableLog($p = null) {
        if (false === $p || true === $p) {
            $this->fLogEnabled = $p;
        }
        return $this->fLogEnabled;
    }

    public function initialize()
    {
        if ($this->fInitialized) {
            return;
        }

        try
        {
            if ($this->driver=="Pdo_Oci") {
                $conn=$this->_adapter->getConnection();  // dont use $this->getConnection(); it causes infinite recursion
                $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            } elseif ($this->driver=="Pdo_Mysql") {
                if (strlen($this->charset)) {
                    $conn=$this->_adapter->getConnection();  // dont use $this->getConnection(); it causes infinite recursion
                    $conn->exec("set names ".$this->quote($this->charset));
                }
            }
        } catch (Exception $e) {
            throw new Gb_Exception($e->getMessage());
        }
        $this->fInitialized=true;
        return;
    }

    public static function GbResponsePlugin()
    {
        $ret="";

        $sqltime=self::$sqlTime;
        $dbtotal=self::$nbInstance_total;
        $dbpeak=self::$nbInstance_peak;
        $nbrequest=self::$nbRequest;
        $sqltime=Gb_Util::roundCeil($sqltime);
        $ret.="Gb_Db:\n{\n";
        $ret.="totalInstances:$dbtotal peakInstances:$dbpeak nbrequests:$nbrequest time:{$sqltime}s";
        $aTimes = array_map(function($a){return Gb_Util::roundCeil($a)."s";}, self::$aSqlTime);
        $ret.=" per instance: {" . implode(", ", $aTimes) . "}\n";
        foreach (self::$sqlLog as $instance=>$loginstance) {
            $ret .= "Gb_Db instance $instance: " . implode("\n", $loginstance) . "\n";
        }
        $ret.="}";

        return $ret;
    }

    /**
     * returns tables and view name
     * result is only cached locally (so new tables can appear without having to clear cache)
     * @return array(array("FULL_NAME"=>), ...)
     */
    public function getTables()
    {
        if ($this->tables==null) {
            switch($this->driver) {
                case "Pdo_Oci": case "Oracle":
                    $sql_getTablesName=<<<EOF
                        SELECT OWNER || '.' || TABLE_NAME AS "FULL_NAME"
                        FROM ALL_TABLES
                        WHERE table_name NOT LIKE '%$%'
                        ORDER BY OWNER, TABLE_NAME
EOF;
                break;

                case "Pdo_Mysql":
                    $sql_getTablesName=<<<EOF
                        SELECT CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) AS 'FULL_NAME'
                        FROM information_schema.tables
                        WHERE TABLE_SCHEMA<>'information_schema' AND TABLE_SCHEMA<>'mysql'
                        ORDER BY TABLE_SCHEMA, TABLE_NAME
EOF;
                break;

                case "Pdo_Pgsql":
                    $sql_getTablesName=<<<EOF
                        SELECT TABLE_SCHEMA || '.' || TABLE_NAME AS "FULL_NAME"
                        FROM information_schema.tables
                        WHERE TABLE_SCHEMA<>'information_schema' AND TABLE_SCHEMA<>'pg_catalog'
                        ORDER BY TABLE_SCHEMA, TABLE_NAME
EOF;
                break;

                case "Pdo_Sqlite":
                    $sql_getTablesName=<<<EOF
                        SELECT tbl_name AS "FULL_NAME"
                        FROM sqlite_master
                        WHERE type="table"
                        ORDER BY tbl_name
EOF;
                    break;

            }

//            $this->tables=$this->retrieve_all($sql_getTablesName, array(), "", "FULL_NAME");
            $this->tables=$this->retrieve_all($sql_getTablesName, array());
        }
        return $this->tables;
    }

    /**
     * Get table's column details, primary cols and foreign key definition
     * Result is cached permanently if setCache() has been used, or in an instance variable.
     * @param string $table
     * @return array array(
     *     "columns"=>array(array("COLUMN_NAME"=>, "TYPE"=>, "NULLABLE"=>, "COMMENT"=>, "EXTRA"=>), ...),
     *     "pk"=>     array(array("COLUMN_NAME"=>), ...),
     *     "fks"=>    array(array("COLUMN_NAME"=>, "FULL_NAME"=>), ...)
     * )
     */
    public function getTableDesc($table)
    {
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
        $cacheKey = $towner.".".$tname;
        if (null !== $this->_cache) {
            if (isset($this->_cache->$cacheKey)) {
                return $this->_cache->$cacheKey;
            }
        } else {
            if (isset($this->tablesDesc[$cacheKey])) {
                return $this->tablesDesc[$cacheKey];
            }
        }

        $sql_getColumns = $sql_getPK = $sql_getFKs = $sql_getOtr = "";

        switch($this->driver) {

            case "Pdo_Oci": case "Oracle":
                $sql_getColumns=<<<EOF
SELECT A.COLUMN_NAME, A.DATA_TYPE AS "TYPE", A.NULLABLE, C.COMMENTS AS "COMMENT", '' AS "EXTRA"
FROM ALL_TAB_COLUMNS A
LEFT JOIN ALL_COL_COMMENTS C
          ON C.OWNER=A.OWNER
         AND C.TABLE_NAME=A.TABLE_NAME
         AND C.COLUMN_NAME=A.COLUMN_NAME
WHERE A.OWNER=?
  AND A.TABLE_NAME=?
ORDER BY A.COLUMN_ID
EOF;
                $sql_getPK=<<<EOF
SELECT COLUMN_NAME
FROM all_constraints allcons, all_cons_columns allcols
WHERE allcons.OWNER=?
  AND allcons.TABLE_NAME=?
  AND CONSTRAINT_TYPE='P'
  AND allcons.CONSTRAINT_NAME=allcols.CONSTRAINT_NAME
ORDER BY position
EOF;
                // distinct rajouté pour INS_ADM_ETP: plusieurs lignes identiques pour COD_DIP (contraint_name IAE_FK_SPV_03 IAE_FK_SPV_02 IAE_FK_SPV_01)
                $sql_getFKs=<<<EOF
SELECT DISTINCT cols_src.COLUMN_NAME AS "COLUMN_NAME", cols_dst.TABLE_NAME || '.' || cols_dst.COLUMN_NAME AS "FULL_NAME"
FROM all_constraints cons_src
JOIN all_cons_columns cols_src
     ON (cols_src.constraint_name=cons_src.constraint_name)
LEFT JOIN all_cons_columns cols_dst
     ON (cols_dst.constraint_name=cons_src.r_constraint_name
         AND cols_dst.position=cols_src.position)
WHERE cons_src.owner=?
  AND cons_src.table_name=?
  AND cons_src.constraint_type='R'
ORDER BY 2
EOF;
                $sql_getOtr=<<<EOF
SELECT cols_src.COLUMN_NAME AS "COLUMN_NAME", cons_src.SEARCH_CONDITION AS "EXTRA"
FROM all_constraints cons_src
JOIN all_cons_columns cols_src
     ON (cols_src.constraint_name=cons_src.constraint_name)
WHERE cons_src.owner=? AND cons_src.table_name=?
  AND cons_src.constraint_type='C'
ORDER BY 1
EOF;
                break;

            case "Pdo_Mysql":
                $sql_getColumns=<<<EOF
SELECT COLUMN_NAME, COLUMN_TYPE AS 'TYPE', IS_NULLABLE AS 'NULLABLE', COLUMN_COMMENT AS 'COMMENT', "" AS 'EXTRA'
FROM information_schema.columns
WHERE TABLE_SCHEMA=?
  AND TABLE_NAME=?
ORDER BY ORDINAL_POSITION
EOF;
                $sql_getPK=<<<EOF
SELECT COLUMN_NAME
FROM information_schema.key_column_usage
WHERE TABLE_SCHEMA=? AND TABLE_NAME=?
  AND CONSTRAINT_NAME='PRIMARY'
ORDER BY ORDINAL_POSITION
EOF;
                $sql_getFKs=<<<EOF
SELECT kcu.COLUMN_NAME, CONCAT(kcu.REFERENCED_TABLE_NAME, '.', kcu.REFERENCED_COLUMN_NAME) AS 'FULL_NAME'
FROM information_schema.key_column_usage kcu
JOIN information_schema.table_constraints tc
     ON CONSTRAINT_TYPE='FOREIGN KEY'
    AND kcu.CONSTRAINT_NAME=tc.CONSTRAINT_NAME
    AND kcu.TABLE_NAME=tc.TABLE_NAME
    AND kcu.TABLE_SCHEMA=tc.TABLE_SCHEMA
WHERE kcu.TABLE_SCHEMA=? AND kcu.TABLE_NAME=?
ORDER BY kcu.COLUMN_NAME, ORDINAL_POSITION
EOF;
            break;

            case "Pdo_Pgsql":
                // information_schema reference: http://www.postgresql.org/docs/8.4/static/information-schema.html
                // postgresql does not provide access to table comment in information_schema.
                // see http://www.alberton.info/postgresql_meta_info.html for a workaround.
                $sql_getColumns=<<<EOF
SELECT COLUMN_NAME AS "COLUMN_NAME", DATA_TYPE AS "TYPE", IS_NULLABLE AS "NULLABLE", '' AS "COMMENT", '' AS "EXTRA"
FROM information_schema.columns
WHERE TABLE_SCHEMA=?
  AND TABLE_NAME=?
ORDER BY ORDINAL_POSITION
EOF;
                $sql_getPK=<<<EOF
SELECT kcu.COLUMN_NAME
FROM information_schema.key_column_usage kcu
JOIN information_schema.table_constraints tc
     ON tc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME
    AND tc.CONSTRAINT_CATALOG=kcu.CONSTRAINT_CATALOG
    AND tc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA
WHERE kcu.TABLE_SCHEMA=? AND kcu.TABLE_NAME=? AND tc.CONSTRAINT_TYPE='PRIMARY KEY'
EOF;
                    $sql_getFKs=<<<EOF
SELECT kcu.COLUMN_NAME AS "COLUMN_NAME", kcu2.TABLE_SCHEMA || '.' || kcu2.TABLE_NAME || '.' || kcu2.COLUMN_NAME AS "FULL_NAME"
FROM information_schema.key_column_usage kcu
JOIN information_schema.table_constraints tc
     ON tc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME
    AND tc.CONSTRAINT_CATALOG=kcu.CONSTRAINT_CATALOG
    AND tc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA
JOIN information_schema.referential_constraints rc
     ON rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME
    AND rc.CONSTRAINT_CATALOG=kcu.CONSTRAINT_CATALOG
    AND rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA
JOIN information_schema.key_column_usage kcu2
     ON kcu2.CONSTRAINT_NAME=rc.UNIQUE_CONSTRAINT_NAME
    AND kcu2.CONSTRAINT_CATALOG=rc.UNIQUE_CONSTRAINT_CATALOG
    AND kcu2.CONSTRAINT_SCHEMA=rc.UNIQUE_CONSTRAINT_SCHEMA
WHERE kcu.TABLE_SCHEMA=?
  AND kcu.TABLE_NAME=?
  AND tc.CONSTRAINT_TYPE='FOREIGN KEY'
EOF;
            break;


        }

/*        $desc["columns"]=$this->retrieve_all($sql_getColumns, array($towner, $tname), "COLUMN_NAME", ""           );
        $desc["pk"]=     $this->retrieve_all($sql_getPK,      array($towner, $tname), "",            "COLUMN_NAME");
        $desc["fks"]=    $this->retrieve_all($sql_getFKs,     array($towner, $tname), "COLUMN_NAME", "FULL_NAME"  );
*/
        $desc["columns"]=$this->retrieve_all($sql_getColumns, array($towner, $tname));
        $desc["pk"]=     $this->retrieve_all($sql_getPK,      array($towner, $tname));
        $desc["fks"]=    $this->retrieve_all($sql_getFKs,     array($towner, $tname));

        if (strlen($sql_getOtr)) {
            $aOtrs=$this->retrieve_all($sql_getOtr,     array($towner, $tname));
            foreach($aOtrs as $aOtr) {
                $col=$aOtr["COLUMN_NAME"];
                $cond=$aOtr["EXTRA"];
                // search column in desc
                foreach ($desc["columns"] as &$pCol) {
                    if ($pCol["COLUMN_NAME"]==$col) {
                        $pCol["EXTRA"].=$cond;
                        break; // column found, can stop searching
                    }
                }
            }
        }

        $cacheKey = $towner.".".$tname;

        // cache le résultat
        if (null !== $this->_cache) {
            $this->_cache->$cacheKey = $desc;
        } else {
            $this->tablesDesc[$cacheKey] = $desc;
        }


        $this->addTime(microtime(true)-$time);
        return $desc;
    }

    function fetchAll($a, $b)
    {
        $time=microtime(true);
        $this->initialize();
        $ret=$this->_adapter->fetchAll($a, $b);
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    function fetchAssoc($a, $b)
    {
        $time=microtime(true);
        $this->initialize();
        $ret=$this->_adapter->fetchAssoc($a, $b);
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    function query($sql, $bindarguments=array())
    {
        $time=microtime(true);
        $this->initialize();
        $ret=$this->_adapter->query($sql, $bindarguments);
        $this->addTime(microtime(true)-$time);
        return $ret;
    }


    /**
     * Execute du sql (PDO seulement)
     */
    function exec($sql)
    {
        self::$nbRequest++;
        $this->log($sql);
        $time=microtime(true);
        $this->initialize();
        $ret=$this->_adapter->getConnection()->exec($sql);
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

/**
 * Renvoie toutes les lignes d'un select
 *
 * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
 * @param mixed|array[optional] $bindargurment exemple array("PE2") ou "PE2"
 * @param string[optional] $index Si spécifié, utilise la colonne comme clé
 * @param string[optional] $col Si spécifié, ne renvoie que cette colonne
 * @param boolean[false] $fKeepIndex Mettre à true pour garder l'index, quand $index est spécifié
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
    public function retrieve_all($sql, $bindargurment=null, $index=null, $col=null, $fKeepIndex=null)
    {
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log($sql);

        if ( (false === $bindargurment) || (null === $bindargurment)) {
            $bindargurment=array();
        } elseif (!is_array($bindargurment)) {
            $bindargurment = array($bindargurment);
        }

        if (count($bindargurment) && ("Oracle" === $this->driver)) {
            // oracle does not support positional parameters
            $sql = $this->quoteIntoMultiple($sql, $bindargurment);
            $bindargurment = array();
        }

        try
        {
            /**
             * @var Zend_Db_Statement
             */
            $stmt=$this->_adapter->query($sql, $bindargurment);

            $fCol=(strlen($col)>0);   // True si on veut juste une valeur
            $fIdx=(strlen($index)>0); // True si on veut indexer

            $ret=array();
            if ($fCol && !$fIdx) {
                // on veut array[numero]=valeur de la colonne
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC))!==false ) {
                    $ret[]=$res[$col];
                }
            }
            elseif ($fCol && $fIdx) {
                // on veut array[index]=valeur de la colonne
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC))!==false ) {
                    $key=$res[$index];
                    $ret[$key]=$res[$col];
                }
            }
            elseif (!$fCol && !$fIdx) {
                //on veut juste un array[numero]=array(colonnes=>valeur)
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC))!==false ) {
                    $ret[]=$res;
                }
            }
            elseif (!$fCol && $fIdx) {
                //on veut un array[index]=array(colonnes=>valeur)
                while ( ($res=$stmt->fetch(Zend_Db::FETCH_ASSOC))!==false ) {
                    $key=$res[$index];
                    if (!$fKeepIndex) {
                        unset($res[$index]);
                    }
                    $ret[$key]=$res;
                }
            }
            $this->addTime(microtime(true)-$time);
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        return $ret;
    }

    /**
     * Renvoie la première ligne d'un select
     *
     * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
     * @param mixed|array[optional] $bindargurment exemple array("PE2") ou "PE2"
     * @param string[optional] $col Si spécifié, renvoie directement la valeur
     *
     * @return array|string|false
     * @throws Gb_Exception
     */
    public function retrieve_one($sql, $bindargurment=null, $col=null)
    {
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log($sql);

        if ( (false === $bindargurment) || (null === $bindargurment)) {
            $bindargurment=array();
        } elseif (!is_array($bindargurment)) {
            $bindargurment = array($bindargurment);
        }

        if (count($bindargurment) && ("Oracle" === $this->driver)) {
            // oracle does not support positional parameters
            $sql = $this->quoteIntoMultiple($sql, $bindargurment);
            $bindargurment = array();
        }

        try {
            $stmt=$this->_adapter->query($sql, $bindargurment);

            $fCol=(strlen($col)>0); // True si on veut juste une valeur

            $ret=array();
            if ($fCol) {
                // on veut juste la valeur
                $res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
                if ($res===false) {
                    $this->addTime(microtime(true)-$time);
                    return false;
                }
                $ret=$res[$col];
            } else {
                //on veut un array
                $res=$stmt->fetch(Zend_Db::FETCH_ASSOC);
                if ($res===false) {
                    $this->addTime(microtime(true)-$time);
                    return false;
                }
                $ret=$res;
            }
            $this->addTime(microtime(true)-$time);
        } catch (Exception $e){
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        return $ret;
    }

    /**
     * Renvoie la première ligne d'un select et s'assure qu'il n'y a qu'une seule ligne
     *
     * @param string $sql exemple "SELECT COUNT(*) FROM tusager WHERE usa_statut='?'
     * @param array[optional] $bindargurment exemple array("PE2")
     * @param string[optional] $col Si spécifié, renvoie directement la valeur
     *
     * @return array|string|false
     * @throws Gb_Exception
     */
    public function retrieve_unique($sql, $bindargurment=array(), $col="")
    {
        $ret=$this->retrieve_all($sql, $bindargurment, null, $col);
        if (count($ret)!=1) {
            throw new Gb_Exception("retrieve_unique returned ".count($ret)." rows");
        }
        return $ret[0];
    }

    public function beginTransaction()
    {
        $time=microtime(true);
        if ($this->fTransaction==true) {
            throw new Gb_Exception("Transaction déjà en cours, impossible d'en démarrer une nouvelle !");
        }
        $this->fTransaction=true;
        $ret=$this->_adapter->beginTransaction();
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    public function rollBack()
    {
        $time=microtime(true);
        $ret=$this->_adapter->rollBack();
        $this->fTransaction=false;
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    public function commit()
    {
        $time=microtime(true);
        $ret=$this->_adapter->commit();
        $this->fTransaction=false;

        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    /**
     * SQL update
     *
     * @param string $table
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @param string|array[optional] $where array("col='val'", $db->quoteInto("usr_id=?", $usr_id), ...)
     * @return int nombre de lignes modifiées
     * @throws Gb_Exception
     */
    public function update($table, array $data, $where=array())
    {
        if (count($data)==0) { return 0; }
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("UPDATE $table where", $where);

        try {
            $ret=$this->_adapter->update($table, $data, $where);
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    /**
     * SQL delete
     *
     * @param string $table
     * @param string|array[optional] $where array($db->quoteInto("col=?", "val"), ...)
     * @return int nombre de lignes modifiées
     * @throws Gb_Exception
     */
    public function delete($table, $where=array())
    {
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("DELETE $table where", $where);

        try {
            $ret=$this->_adapter->delete($table, $where);
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
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
        if (count($data)==0) { return 0; }
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("INSERT $table");

        try {
            $ret=$this->_adapter->insert($table, $data);
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

   /**
     * Regarde Combien de lignes correspondant à $where existe dans la table $data
     * 0?: INSERT. 1?: UPDATE. 2+: Throws exception
     *
     * @param string $table Table à mettre à jour
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @param string|array[optional] $where array("col='val'", $db->quoteInto("usr_id=?", $usr_id), ...)
     * @param array $moreDataInsert[optional] array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @param array $moreDataUpdate[optional] array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @return nombre delignes modifiées
     * @throws Gb_Exception
     */
    public function replace($table, array $data, $where=null, $moreDataInsert=null, array $moreDataUpdate=null)
    {
        if (count($data)==0) { return 0; }
        if (null === $where) { $where=array(); }
        if (null === $moreDataInsert) { $moreDataInsert=array(); }
        if (null === $moreDataUpdate) { $moreDataUpdate=array(); }

        if (is_string($where)) {$where=array($where);}
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("REPLACE $table where", $where);

        try {
            // compte le nombre de lignes correspondantes
            $select=$this->_adapter->select();
            $select->from($table, array("A"=>"COUNT(*)"));
            foreach ($where as $w) {
                $select->where($w);
            }
            $stmt=$this->_adapter->query($select);
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
                    elseif (strval($val) !== strval(intval($val))) {
                        throw new Gb_Exception("Pas de guillemets trouvés dans la clause where !");
                    }
                    $data[$col]=$val;
                }
                $data2=array_merge($data, $moreDataInsert);
                $ret=$this->_adapter->insert($table, $data2);
            } elseif ($nb==1) {
                // Une ligne existe déjà: mettre à jour
                $data2=array_merge($data, $moreDataUpdate);
                $ret=$this->_adapter->update($table, $data2, $where);
            } else {
                // Plus d'une ligne correspond: erreur de clé ?
                throw new Gb_Exception("replace impossible: plus d'une ligne correspond !");
            }
        } catch (Gb_Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw $e;
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
        return $ret;
    }







   /**
     * Insertion. Si l'insertion est impossible, supprime la ligne et la réinsere.
     *
     * @param string $table Table à mettre à jour
     * @param array $data array("col"=>"val", "col2"=>new Zend_Db_Expr("NOW()"), ...)
     * @throws Gb_Exception
     */
    public function insertOrDeleteInsert($table, array $data)
    {
        if (count($data)==0) { return 0; }
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("INSERTORDELETEINSERT $table");


        $where=array();
        $newdata=array();
        $this->developpeData($table, $data, $newdata, $where);
//        print_r($where);
//        print_r($newdata);

        // @todo NON NON et NON !!! Essayer d'insérer la ligne plutôt !!!
        try {
            // compte le nombre de lignes correspondantes
            $select=$this->_adapter->select();
            $select->from($table, array("A"=>"COUNT(*)"));
            foreach ($where as $w) {
                $select->where($w);
            }
            $stmt=$this->_adapter->query($select);
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
            $this->addTime(microtime(true)-$time);
            throw $e;
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
        return $ret;
    }


    protected function developpeData($table, $data, &$newdata, &$where)
    {
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

        $this->addTime(microtime(true)-$time);
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
        if (count($data)==0) { return 0; }
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("INSERTORUPDATENOTWORKING $table");


        $where=array();
        $newdata=array();
        $this->developpeData($table, $data, $newdata, $where);

        // @todo NON NON et NON !!! Essayer d'insérer la ligne plutôt !!!
        try {
            // compte le nombre de lignes correspondantes
            $select=$this->_adapter->select();
            $select->from($table, array("A"=>"COUNT(*)"));
            foreach ($where as $w) {
                $select->where($w);
            }
            $stmt=$this->_adapter->query($select);
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
            $this->addTime(microtime(true)-$time);
            throw $e;
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    public function insertOrUpdate($table, array $data)
    {
        if (count($data)==0) { return 0; }
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("INSERTORUPDATE $table");


        $where=array();
        $newdata=array();
        $this->developpeData($table, $data, $newdata, $where);

        // @todo NON NON et NON !!! Essayer d'insérer la ligne plutôt !!!
        try {
            // compte le nombre de lignes correspondantes
            $select=$this->_adapter->select();
            $select->from($table, array("A"=>"COUNT(*)"));
            foreach ($where as $w) {
                $select->where($w);
            }
            $stmt=$this->_adapter->query($select);
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
            $this->addTime(microtime(true)-$time);
            throw $e;
        } catch (Exception $e) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception($e->getMessage());
        }
        $this->addTime(microtime(true)-$time);
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
            $ret=$this->_adapter->quote($var);
        }
        $this->addTime(microtime(true)-$time);
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
           $text=$this->_adapter->quoteInto($text, $value);
        }

        $this->addTime(microtime(true)-$time);
        return $text;
    }

    /**
     * Quote an identifier
     *
     * @param string|array|Zend_Db_expr $ident
     * @return string the quoted string
     */
    public function quoteIdentifier($ident)
    {
        $time=microtime(true);
        $ret=$this->_adapter->quoteIdentifier($ident, false);
        $this->addTime(microtime(true)-$time);
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
        $this->initialize();
        $ret=(int) $this->_adapter->lastInsertId($tableName, $primaryKey);
        $this->addTime(microtime(true)-$time);
        return $ret;
    }

    /**
     * Renvoie la valeur suivante d'une séquence
     *
     *  la table doit etre de la forme::
     *  create table seq_sise_numero (id int not null) ENGINE = 'MyIsam';
     *  insert into seq_sise_numero values (0);
     *  le suivant est obtenu avec cette requete: update seq_sise_numero set id=LAST_INSERT_ID(id+1);
     *
     * @param string $tableName
     * @param string[optionel] $colName
     * @return integer
     * @throws Gb_Exception
     */
    public function sequenceNext($tableName, $colName="id")
    {
        $time=microtime(true);
        $this->initialize();

        $nb=$this->update($tableName, array( $colName=>new Zend_Db_Expr("LAST_INSERT_ID(".$this->_adapter->quoteIdentifier($colName)."+1)") ));
        if ($nb!=1) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception("erreur sequenceNext($tableName.$colName)");
        }
        $this->addTime(microtime(true)-$time);
        return (int) $this->_adapter->lastInsertId();
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
        $time=microtime(true);
        $this->initialize();
        self::$nbRequest++;
        $this->log("SEQUENCECURRENT $tableName");

        $sql="SELECT ".$this->_adapter->quoteIdentifier($colName)." FROM ".$this->_adapter->quoteIdentifier($tableName);
        $stmt=$this->_adapter->query($sql);
        $res=$stmt->fetch(Zend_Db::FETCH_NUM);
        if ($stmt->fetch(Zend_Db::FETCH_NUM)) {
            $this->addTime(microtime(true)-$time);
            throw new Gb_Exception("erreur sequenceCurrent($tableName.$colName)");
        }
        $res=$res[0];
        $this->addTime(microtime(true)-$time);
        return $res;
    }

    public function setProfiler($param)
    {
        $db=$this->_adapter;
        return $db->setProfiler($param);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param array   $values The value to quote.
     * @param array   $types  OPTIONAL SQL datatype
     */
    public function quoteIntoMultiple($text, $values, $types = null)
    {
        $index = 0;
        foreach ($values as $value) {
            if (strpos($text, '?') !== false) {
                $type = isset($types[$index]) ? ($types[$index]) : null;
                $text = substr_replace($text, $this->quote($value, $type), strpos($text, '?'), 1);
            }
            $index++;
        }
        return $text;
    }

}
