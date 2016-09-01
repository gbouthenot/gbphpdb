<?php
/**
 * Gb_Db_Engine
 *
 * @author Gilles Bouthenot
 */

if (!defined("_GB_PATH")) {
    define("_GB_PATH", realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
} elseif (_GB_PATH !== realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR) {
    throw new Exception("gbphpdb roots mismatch");
}

require_once(_GB_PATH."Exception.php");
require_once(_GB_PATH."Db.php");


class Gb_Db_Migration
{
    /**
     * @var Gb_Db
     */
    protected $_db;

    public function __construct(Gb_Db $db) {
        $this->_db = $db;
    }

    /**
     * get the implemented versions
     * @return array sorted array of integers
     */
    public function getImplementedVersions() {
        $rc = new ReflectionClass($this);
        $aReflectionMethods = $rc->getMethods();

        // keep only "migration_to_version_nnnnnn" methods
        $aReflectionMethods = array_filter($aReflectionMethods, function($rm) {
            if (substr($rm->name, 0, 19)==="migrate_to_version_") {
                // ensure it is a number yyyymmddhhmmss
                $version = substr($rm->name, 19, 14);
                return strlen($version)===14 && is_numeric($version);
            }
            return false;
        });

        // get only the versions number
        $aVersions = array_map(function($rm){
            return substr($rm->name, 19);
        }, $aReflectionMethods);
        sort($aVersions);

        return $aVersions;
    }



    /**
     * get the deprecated versions
     * @return array sorted array of integers
     */
    public function getDeprecatedVersions() {
        $rc = new ReflectionClass($this);
        $aReflectionMethods = $rc->getMethods();

        // keep only "migration_to_version_nnnnnn" methods
        $aReflectionMethods = array_filter($aReflectionMethods, function($rm) {
            if (substr($rm->name, 0, 30)==="migrate_to_deprecated_version_") {
                // ensure it is a number yyyymmddhhmmss
                $version = substr($rm->name, 30, 14);
                return strlen($version)===14 && is_numeric($version);
            }
            return false;
        });

        // get only the versions number
        $aVersions = array_map(function($rm){
            return substr($rm->name, 30);
        }, $aReflectionMethods);
        sort($aVersions);

        return $aVersions;
    }



    /**
     * get the migrated versions
     * @return array sorted array of integers
     */
    public function getMigratedVersions() {
        $sql = "SELECT version FROM schema_migrations";
        try {
            $aVersions = $this->_db->retrieve_all($sql, null, null, "version");
            if (false === $aVersions) {
                $aVersions = array();
            }
        } catch (Gb_Exception $e) {
            $aVersions = array();
        }
        sort($aVersions);

        return $aVersions;
    }


    /**
     * Migrate the database up to the latest version
     */
    public function migrateUp() {
        $aImplementedVersions = $this->getImplementedVersions();
        $aMigratedVersions    = $this->getMigratedVersions();

        foreach ($aImplementedVersions as $version) {
            if (!in_array($version, $aMigratedVersions)) {
                $this->migrateVersion($version, "up");
            }
        }
    }



    /**
     * Migrate the database up to the latest version. Migrate down the unimplemented migrations
     */
    public function migrate() {
        $aImplementedVersions = $this->getImplementedVersions();
        $aDeprecatedVersions  = $this->getDeprecatedVersions();
        $aMigratedVersions    = $this->getMigratedVersions();

        $aVersions = array_merge($aImplementedVersions, $aMigratedVersions);
        $aVersions = array_unique($aVersions);
        sort($aVersions);

        foreach ($aVersions as $version) {
            if (in_array($version, $aDeprecatedVersions)) {
                $this->migrateVersion($version, "down", array("deprecated"=>true));
            } elseif (!in_array($version, $aMigratedVersions)) {
                $this->migrateVersion($version, "up");
            }
        }
    }


    /**
     * Call migrate_to_version_$version("up")
     * @param integer $version
     */
    public function migrateVersion($version, $direction, $opts=null) {
        if (null === $opts) {
            $opts = array();
        }
        if (isset($opts["deprecated"]) && $opts["deprecated"]) {
            $name = "migrate_to_deprecated_version_" . $version;
            Gb_Log::logNotice("DATABASE: migrating $direction deprecated version $version");
        } else {
            $name = "migrate_to_version_" . $version;
            Gb_Log::logNotice("DATABASE: migrating $direction version $version");
        }

        $this->_db->beginTransaction();
        $this->$name($direction);
        $this->setVersion($version, $direction);
        $this->_db->commit();

    }




    public function currentVersion() {
        $sql = "SELECT MAX(version) FROM schema_migrations";
        try {
            $version = $this->_db->retrieve_one($sql, null, "version");
        } catch (Gb_Exception $e) {
            $version = 0;
        }
        return $version;
    }

    /**
     * update table schema_migrations
     * @param string $version
     * @param string $type "up", "down"
     */
    public function setVersion($version, $type) {
        $sql = 'CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT NOT NULL);';
        $this->_db->getAdapter()->query($sql);

        if ("up" === $type) {
            $sql = 'SELECT version FROM schema_migrations WHERE version=?';
            $res = $this->_db->retrieve_one($sql, $version);
            if (false === $res) {
                $this->_db->insert("schema_migrations", array("version"=>$version));
            }
        } elseif ("down" === $type) {
            $this->_db->delete("schema_migrations", array("version=?"=>$version));
        }

        Gb_Log::logNotice("DATABASE: migrated $type version=$version");
    }
}


/**********/
/* SAMPLE */
/**********/

/*
class Application_Migration extends Gb_Db_Migration {
    public function migrate_to_version_20130101235959_initial_state($type) {
        if ("up" === $type) {
            $sql = <<<EOF
            CREATE TABLE IF NOT EXISTS users (
                id           INTEGER PRIMARY KEY,
                login        TEXT COLLATE NOCASE NOT NULL DEFAULT "",      -- login du ldap
                lastname     TEXT COLLATE NOCASE          DEFAULT "",
                firstname    TEXT COLLATE NOCASE          DEFAULT "",
                email        TEXT COLLATE NOCASE,
                usertype     TEXT COLLATE NOCASE NOT NULL DEFAULT "",      -- ""/admin
                created_at   datetime    NOT NULL,
                updated_at   datetime    NOT NULL
            );
EOF;
            $this->_db->getAdapter()->query($sql);
        } else if ("down" === $type) {
            $sql = "DROP TABLE users";
            $this->_db->getAdapter()->query($sql);
        }
    }

    // deprecated migration : will be migrated down, if it has been committed in the db
    public function migrate_to_deprecated_version_20140114140009($type) {
        if ("up" === $type) {
            $sql = "ALTER TABLE  rubriques  ADD categorie TEXT NOT NULL DEFAULT 'etu'";
            $this->_db->getAdapter()->query($sql);
        } else if ("down" === $type) {
            $sql = "CREATE TABLE rubriques_backup(id, is_active, intitule, created_at, created_by, updated_at, updated_by)";
            $this->_db->getAdapter()->query($sql);
            $sql = "INSERT INTO rubriques_backup  SELECT  id, is_active, intitule, created_at, created_by, updated_at, updated_by  FROM  rubriques";
            $this->_db->getAdapter()->query($sql);
            $sql = "DROP TABLE rubriques";
            $this->_db->getAdapter()->query($sql);
            $sql = "ALTER TABLE  rubriques_backup  RENAME TO  rubriques";
            $this->_db->getAdapter()->query($sql);
        }
    }

    // SKELETON FOR FUTURE MIGRATIONS
    public function migrate_to_version_yyyymmddhhmmss_custom_message($type) {
        if ("up" === $type) {
        } else if ("down" === $type) {
        }
    }
}

// END OF SAMPLE
*/
