<?php
require_once 'Gb/Util.php';
require_once 'Gb/Db.php';
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
        Gb_Glue::$projectName="testProject";
        $db=$this->db;
        $db->delete("test_gb_db_1", array());
        $db->delete("test_gb_db_2", array());

        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>1, "usa_nom"=>"Premier usager")));
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>2, "usa_nom"=>"Deuxi�me usager")));
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
        $this->db=new Gb_Db(array("type"=>"mysql", "host"=>"localhost", "user"=>"test", "pass"=>"***REMOVED***", "dbname"=>"test"));
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
|      2 | Deuxi�me usager | 
+--------+-----------------+
*/
    
    public function testgetTables()
    {
        $getTables=$this->db->getTables();

        // s'assure que la table test_gb_db_1 et 2 sont pr�sentes
        $this->assertTrue(in_array("test.test_gb_db_1", $getTables), "table test_gb_db_1 non trouv�e");
        $this->assertTrue(in_array("test.test_gb_db_2", $getTables), "table test_gb_db_2 non trouv�e");

        // le deuxi�me appel doit donner le m�me r�sultat
        $this->assertSame($getTables, $this->db->getTables());
        
    }

    public function testgetTableDesc()
    {
        $db=$this->db;
        $td1=$db->getTableDesc("test.test_gb_db_1");
        $td2=$db->getTableDesc("test.test_gb_db_2");
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

        // le deuxi�me appel doit donner le m�me r�sultat
        $this->assertSame($expected1, $td1);
        $this->assertSame($expected2, $td2);
    }
    
    public function testInsert()
    {
        $db=$this->db;

        $this->assertEquals(1, $db->insert("test_gb_db_1", array("pkey"=>"keyte", "val"=>"test", "usr"=>1)));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "keyte"=>array("val"=>"test", "usr"=>"1"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // ins�re une ligne avec une cl� primaire d�j� existante
        $ok=false;
        try {
            $db->insert("test_gb_db_1", array("pkey"=>"key2", "val"=>"abc", "usr"=>2));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("duplicate pkey not catched");
        }

        // ins�re une ligne avec une cl� �trang�re inexistante
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

    public function testLastInsertId()
    {
        $db=$this->db;
        
        // insertion en pr�cisant la valeur autoincr�ment�e
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Trois")));
        $this->assertEquals(3, $db->lastInsertId());
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Trois");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));
    
        // insertion sans pr�ciser la valeur autoincr�ment�e
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_nom"=>"Autre")));
        $id=$db->lastInsertId();
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Trois", $id=>"Autre");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));
        
    }
    
    public function testDelete()
    {
        $db=$this->db;

        // Essaie de supprimer une cl� qui est r�f�renc�e ailleurs
        $ok=false;
        try {
            $db->delete("test_gb_db_2", array($db->quoteInto("usa_id=?", 1)));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("Foreign key deletion not catched");
        }
        
        // supprime la ligne qui r�f�rence cette ligne
        $this->assertEquals(1, $db->delete("test_gb_db_1", array($db->quoteInto("pkey=?", "key1"))));

        // maintenant on peut supprimer la ligne
        $this->assertEquals(1, $db->delete("test_gb_db_2", array($db->quoteInto("usa_id=?", 1))));

        // Si on re-supprime la ligne, on ne doit rien trouver
        $this->assertEquals(0, $db->delete("test_gb_db_2", array($db->quoteInto("usa_id=?", 1))));
        
    }

    public function testUpdate()
    {
        $db=$this->db;

        // essaie d'attribuer une cl� �trangere qui n'existe pas
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
        
        // update 1 changement, 2 lignes concern�es
        $this->assertEquals(1, $db->update("test_gb_db_1", array("usr"=>"1")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"1"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // update 2 changements
        $this->assertEquals(2, $db->update("test_gb_db_1", array("usr"=>"2"),  array($db->quoteInto("usr=?", "1"))));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"2"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // insertion en pr�cisant la valeur autoincr�ment�e
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Trois")));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Trois");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));

        // modifie la valeur:
        $this->assertEquals(1, $db->update("test_gb_db_2", array("usa_nom"=>"Troi"), array($db->quoteInto("usa_id=?", "3"))));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Troi");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));

        // modifie la cl�:
        $this->assertEquals(1, $db->update("test_gb_db_2", array("usa_id"=>"4"), array($db->quoteInto("usa_id=?", "3"))));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "4"=>"Troi");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));
    }

    public function testReplace()
    {
        $db=$this->db;

        // essaie d'attribuer une cl� �trangere qui n'existe pas
        $ok=false;
        try {
            $db->replace("test_gb_db_1", array("usr"=>"99"),  array($db->quoteInto("pkey=?", "key1")));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }

        // essaie d'attribuer une cl� �trangere qui n'existe pas sur une cl� primaire qui n'existe pas non plus
        $ok=false;
        try {
            $db->replace("test_gb_db_1", array("usr"=>"99"),  array($db->quoteInto("pkey=?", "keyx")));
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
        
        // replace 1 changement, 2 lignes concern�es -> doit faire exception
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


        // insertion en pr�cisant la valeur autoincr�ment�e
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Trois")));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Trois");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));

        // modifie la valeur:
        $this->assertEquals(1, $db->replace("test_gb_db_2", array("usa_nom"=>"Troi"), array($db->quoteInto("usa_id=?", "3"))));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Troi");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));

        // modifie la cl�:
        $this->assertEquals(1, $db->replace("test_gb_db_2", array("usa_id"=>"4"), array($db->quoteInto("usa_id=?", "3"))));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "4"=>"Troi");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));
        
    }

    public function testInsertOrUpdate()
    {
        $db=$this->db;

        // v�rifie que la bdd est bien dans la bon �tat
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // essaie de modifier avec une cl� �trangere qui n'existe pas
        $ok=false;
        try {
            $db->insertOrUpdate("test_gb_db_1", array("pkey"=>"key1", "val"=>"def", "usr"=>"99"));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }
        // v�rifie que la bdd n'a pas �t� modifi�e
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // essaie d'ins�rer avec une cl� �trangere qui n'existe pas
        $ok=false;
        try {
            $db->insertOrUpdate("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"99"));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }
        // v�rifie que la bdd n'a pas �t� modifi�e
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // insertion
        $this->assertEquals(1, $db->insertOrUpdate("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"1")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "key9"=>array("val"=>"def", "usr"=>"1"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // modification
        $this->assertEquals(1, $db->insertOrUpdate("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"2")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "key9"=>array("val"=>"def", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // aucune modification: doit renvoyer 0 lignes modifi�es
        $this->assertEquals(0, $db->insertOrUpdate("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"2")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "key9"=>array("val"=>"def", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));


        // insertion en pr�cisant la valeur autoincr�ment�e
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Trois")));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Trois");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));

        // modifie la valeur:
        $this->assertEquals(1, $db->insertOrUpdate("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Troi")));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Troi");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));
    }

    public function testInsertOrDeleteInsert()
    {
        $db=$this->db;

        // v�rifie que la bdd est bien dans la bon �tat
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // essaie de modifier avec une cl� �trangere qui n'existe pas
        $ok=false;
        try {
            $db->insertOrDeleteInsert("test_gb_db_1", array("pkey"=>"key1", "val"=>"def", "usr"=>"99"));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }
        // v�rifie que la bdd n'a pas �t� modifi�e
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // essaie d'ins�rer avec une cl� �trangere qui n'existe pas
        $ok=false;
        try {
            $db->insertOrDeleteInsert("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"99"));
        } catch (Gb_Exception $e) {
            $e;$ok=true;
        }
        if (!$ok) {
            $this->fail("unknown foreign key not catched");
        }
        // v�rifie que la bdd n'a pas �t� modifi�e
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));
        
        // insertion
        $this->assertEquals(1, $db->insertOrDeleteInsert("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"1")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "key9"=>array("val"=>"def", "usr"=>"1"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // modification
        $this->assertEquals(1, $db->insertOrDeleteInsert("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"2")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "key9"=>array("val"=>"def", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));

        // aucune modification: doit renvoyer 1 quand m�me. (car suppression puis modification)
        $this->assertEquals(1, $db->insertOrDeleteInsert("test_gb_db_1", array("pkey"=>"key9", "val"=>"def", "usr"=>"2")));
        $expected=array("key1"=>array("val"=>"abc", "usr"=>"1"), "key2"=>array("val"=>"abc", "usr"=>"2"), "key9"=>array("val"=>"def", "usr"=>"2"));
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_1", array(), "pkey"));


        // insertion en pr�cisant la valeur autoincr�ment�e
        $this->assertEquals(1, $db->insert("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Trois")));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Trois");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));

        // modifie la valeur:
        $this->assertEquals(1, $db->insertOrDeleteInsert("test_gb_db_2", array("usa_id"=>"3", "usa_nom"=>"Troi")));
        $expected=array("1"=>"Premier usager", "2"=>"Deuxi�me usager", "3"=>"Troi");
        $this->assertSame($expected, $db->retrieve_all("SELECT * FROM test_gb_db_2", array(), "usa_id", "usa_nom"));
    }
    
}

