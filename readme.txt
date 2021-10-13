Personal php library / collection of utilities
Language: PHP 5.4

This has been developped and used from 2001 to 2017.
Used Subversion (up to 2013-02-26), then Mercurial (hosted on bitbucket)
Converted to git (hosted on github) on 2021-10-13 because bitbucket deleted all the mercurial repositories.

Demo/readline/sql.php -> complete cli application similar to the mysql console, but using Gb_Db. It includes search in lines and column and schema description

Gb_Db         : database abstraction library (sqlite, postgres, mysql, oracle) (use Zend Framework 1)
Gb_Db_Migrate : simple migration model
Gb_Form       : manage html form, with javascript verification
Gb_Form2      : manage html form, with javascript verification and backend (ie database).
Gb_Cache      : provide cache (use Zend Framework 1)
Gb_Emailverif : handle email verification (with mail and database)
Gb_Excelxml   : read Office 2003 xml
Gb_File       : manage a filesystem backend stored in a db
Gb_Glue       : manage plugins
Gb_Ldap       : ldap library
Gb_Log        : log engine, with plugins for Gb_Response
Gb_Mafu       : manage multiple file uploads
Gb_Mail       : mail sender
Gb_Model      : to manage Objects in database. Use cache. Inspired by ruby on rails. Incredible complex code !
Gb_Mvc        : simple framework with controllers/view/model
Gb_Session    : provide object storage and expiration
Gb_Source     : analyse php code. Can compact
Gb_Ssh2       : remote controlling
Gb_String     : utilities about string, Format_table, remove_accents, ...
Gb_Util       : Common utilities
Gb_Request    : handle http request
Gb Response   : handle http response
Gb_Timer      : time some actions. Includes Pause
Gb/incub/*    : experiments with byte / bit programming

