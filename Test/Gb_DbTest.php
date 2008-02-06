<?php
require_once 'Gb/Util.php';
/**
 * Gb_Util test case.
 */
class Gb_DbTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gb_Db
     */
    protected $db;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        Gb_Util::$projectName="testProject";
        $db=$this->db;
        $db->delete("test_gb_db_1", array());
        $db->delete("test_gb_db_2", array());

        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>1, "usa_nom"=>"Premier usager")));
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>2, "usa_nom"=>"Deuxième usager")));
        $this->assertEquals(1, $db->insert("test_gb_db_1", array("pkey"=>"key1", "val"=>"abc", "usr"=>1)));
        $this->assertEquals(1, $db->insert("test_gb_db_1", array("pkey"=>"key2", "val"=>"abc", "usr"=>2)));
        
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->db->delete("test_gb_db_1", array());
        $this->db->delete("test_gb_db_2", array());
    }

    /**
     * Constructs the test case.
     */
    public function __construct()
    {
        $this->db=new Gb_Db(array("type"=>"mysql", "host"=>"pollux3", "user"=>"test", "pass"=>"***REMOVED***", "dbname"=>"test"));
    }

/*
mysql> select * from test_gb_db_1;
+------+------+-----+
| pkey | val  | usr |
+------+------+-----+
| key1 | abc  |   1 | 
| key2 | abc  |   2 | 
+------+------+-----+

mysql> select * from test_gb_db_2;
+--------+-----------------+
| usa_id | usa_nom         |
+--------+-----------------+
|      1 | Premier usager  | 
|      2 | Deuxième usager | 
+--------+-----------------+
*/
    
    public function testgetTables()
    {
        $getTables=$this->db->getTables();

        // s'assure que la table test_gb_db_1 et 2 sont présentes
        $this->assertTrue(in_array("test.test_gb_db_1", $getTables), "table test_gb_db_1 non trouvée");
        $this->assertTrue(in_array("test.test_gb_db_2", $getTables), "table test_gb_db_2 non trouvée");
    }

    public function testgetTableDesc()
    {
        $db=$this->db;
        $td1=$db->getTableDesc("test.test_gb_db_1");
        $td2=$db->getTableDesc("test.test_gb_db_2");
        print_r($td1);
        print_r($td2);
        $expected1=array(
            "columns"=>array(
                array("COLUMN_NAME" => "pkey", "TYPE" => "varchar(5)",          "NULLABLE" => "NO",  "COMMENT" => ""),
                array("COLUMN_NAME" => "val",  "TYPE" => "varchar(50)",         "NULLABLE" => "YES", "COMMENT" => ""),
                array("COLUMN_NAME" => "usr",  "TYPE" => "int(10) unsigned",    "NULLABLE" => "NO",  "COMMENT" => ""))
            ,"pk"=>array(array("COLUMN_NAME" => "pkey"))
            ,"fks"=>array(array("COLUMN_NAME" => "usr", "FULL_NAME"=>"test_gb_db_2.usa_id"))
        );
        $expected2=array(
            "columns"=>array(
                array("COLUMN_NAME" => "usa_id",  "TYPE" => "int(10) unsigned", "NULLABLE" => "NO",  "COMMENT" => ""),
                array("COLUMN_NAME" => "usa_nom", "TYPE" => "varchar(45)",      "NULLABLE" => "NO",  "COMMENT" => ""))
            ,"pk"=>array(array("COLUMN_NAME" => "usa_id"))
            ,"fks"=>array()
        );
        $this->assertSame($expected1, $td1);
        $this->assertSame($expected2, $td2);
    }
    
    public function testInsert()
    {
        $db=$this->db;

        $this->assertEquals(1, $db->insert("test_gb_db_1", array("pkey"=>"keytest", "val"=>"test", "usr"=>1)));
        
        // insère une ligne avec une clé primaire déjà existante
        $ok=false;
        try {
            $db->insert("test_gb_db_1", array("pkey"=>"key2", "val"=>"abc", "usr"=>2));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("duplicate pkey not catched");
        }

        // insère une ligne avec une clé étrangère inexistante
        $ok=false;
        try {
            $db->insert("test_gb_db_1", array("pkey"=>"key3", "val"=>"abc", "usr"=>0));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign pkey not catched");
        }
        
    
    }

    public function testDelete()
    {
        $db=$this->db;

        // Essaie de supprimer une clé qui est référencée ailleurs
        $ok=false;
        try {
            $db->delete("test_gb_db_2", array($db->quoteInto("usa_id=?", 1)));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("Foreign key deletion not catched");
        }
        
        // supprime la ligne qui référence cette ligne
        $this->assertEquals(1, $db->delete("test_gb_db_1", array($db->quoteInto("pkey=?", "key1"))));

        // maintenant on peut supprimer la ligne
        $this->assertEquals(1, $db->delete("test_gb_db_2", array($db->quoteInto("usa_id=?", 1))));

        // Si on re-supprime la ligne, on ne doit rien trouver
        $this->assertEquals(0, $db->delete("test_gb_db_2", array($db->quoteInto("usa_id=?", 1))));
        
    }

    public function testUpdate()
    {
        $db=$this->db;

        // essaie d'attribuer une clé étrangere qui n'existe pas
        $ok=false;
        try {
            $db->update("test_gb_db_1", array("usr"=>"99"),  array($db->quoteInto("pkey=?", "key1")));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }

        // update sans changement
        $this->assertEquals(0, $db->update("test_gb_db_1", array("usr"=>"1"),  array($db->quoteInto("pkey=?", "key1"))));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // update 1 changement, 2 lignes concernées
        $this->assertEquals(1, $db->update("test_gb_db_1", array("usr"=>"1")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"1"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // update 2 changements
        $this->assertEquals(2, $db->update("test_gb_db_1", array("usr"=>"2"),  array($db->quoteInto("usr=?", "1"))));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"2"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
    }

    public function testReplace()
    {
        $db=$this->db;

        // essaie d'attribuer une clé étrangere qui n'existe pas
        $ok=false;
        try {
            $db->replace("test_gb_db_1", array("usr"=>"99"),  array($db->quoteInto("pkey=?", "key1")));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }

        // replace sans changement
        $this->assertEquals(0, $db->replace("test_gb_db_1", array("usr"=>"1"),  array($db->quoteInto("pkey=?", "key1"))));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // replace 1 changement, 2 lignes concernées -> doit faire exception
        $ok=false;
        try {
            $db->replace("test_gb_db_1", array("usr"=>"1"));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }
        
        // replace 1 changement
        $this->assertEquals(1, $db->replace("test_gb_db_1", array("usr"=>"2"),  array($db->quoteInto("usr=?", "1"))));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"2"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
    }
    
}

