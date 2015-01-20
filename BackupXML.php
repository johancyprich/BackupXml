#! /usr/local/bin/php5

<!-- Use this page to run the backup script from a web browser. -->

<?php

  include 'lib/class.BackupXml.php';

  $backup = new BackupXml ('xml/backup.xml');
  
  $backup->FullBackup ();
  
?>