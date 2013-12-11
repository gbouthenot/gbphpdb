#!/usr/bin/php
<?php


error_reporting(E_STRICT|E_ALL);

        $conn_str="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=172.26.0.44)(PORT=1521))(CONNECT_DATA=(SID=PROT)))";
        $hDbId=ocilogon("gbouthenot", "***REMOVED***", $conn_str);
        if ($hDbId) {
            ocilogoff($hDbId);
        }

exit(1);





