<?php

  include 'lib/PHPMailer/class.phpmailer.php';

  class BackupXml
  
  /////////////////////////////////////////////////////////////////////////////
  // class.BackupXml.php
  // VERSION: 1.00
  // DATE: November 14, 2007
  //
  // COPYRIGHT: Copyright (C) 2006-2007 Johan Cyprich. All rights reserved.
  // LICENSE: The MIT License
  // AUTHOR: Johan Cyprich
  // AUTHOR_EMAIL: jcyprich@live.com
  //
  // PURPOSE:
  //   Backs up folders and databases on a Linux system.
  /////////////////////////////////////////////////////////////////////////////
  
  {
    const ERROR_NONE = 0;               // no error
    const ERROR_UNKNOWN = 1;            // unknown error
    const ERROR_NO_SERVER = 2;          // server was not found
    const ERROR_NO_LOGIN = 3;           // no user is logged in
    const ERROR_NO_DATABASE = 4;        // database was not found
    const ERROR_NO_USER = 5;            // no user is logged in

    /**************************************************************************
     * Public Properties
     **************************************************************************/
    
    public $Error;                      // current error status

    /**************************************************************************
     * Private Properties
     **************************************************************************/
    
    private $m_sSettingsFile;
    
    private $m_sAdminUsername;
    private $m_sAdminPassword;
    
    private $m_bEmailReport;            // should a success report be e-mailed?
    private $m_bUseSmtpAuthorization;
    private $m_sSmtpAddress;
    private $m_sSmtpUsername;
    private $m_sSmtpPassword;
    private $m_sFromEmail;
    private $m_sFromName;
    
    private $m_sPrefix;
    private $m_sUseHost;
    private $m_sUsePort;
    private $m_sUseUsername;
    
    private $m_sBackupDate;             // start date and time of backup
    private $m_sBackupStartLongDate;    // long date format of time backup started

    private $m_sBackupPath;
    private $m_fLogFile;
    
    
    /**************************************************************************
     * Public Methods
     **************************************************************************/

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Read settings file and set date to initialize global variables.
    ///////////////////////////////////////////////////////////////////////////
              
    public function __construct ($sSettings)
    {
      // Get current date and time for creating backup folder and log file.
      
      $this->m_sBackupDate = date ("Y-m-d-H-i-s");

      // Save long format of time backup started.
      
      $this->m_sBackupStartLongDate = date ("l, F d, Y, h:i:s A");
      
      // Get database connect information from AuthenticateUser.Settings.xml.
      
      $this->m_sSettingsFile = $sSettings;
      
      $xml = simplexml_load_file ($this->m_sSettingsFile);

      $this->m_sAdminUsername = $xml->Configuration->AdminUsername;
      $this->m_sAdminPassword = $xml->Configuration->AdminPassword;
      
      $this->m_bEmailReport = $xml->Configuration->EmailReport;
      $this->m_bUseSmtpAuthorization = $xml->Configuration->UseSmtpAuthorization;
      $this->m_sSmtpAddress = $xml->Configuration->SmtpAddress;
      $this->m_sSmtpUsername = $xml->Configuration->SmtpUsername;
      $this->m_sSmtpPassword = $xml->Configuration->SmtpPassword;
      $this->m_sFromEmail = $xml->Configuration->FromEmail;
      $this->m_sFromName = $xml->Configuration->FromName;
      
      $this->m_sPrefix = $xml->Configuration->Prefix;
      $this->m_sUseHost = $xml->Configuration->UseHost;
      $this->m_sUseUsername = $xml->Configuration->UseUsername;
      $this->m_sBackupPath = $xml->Configuration->BackupPath;

      $this->CreateBackupFolder ();
      $this->CreateLogFile ();

      $this->Error = self::ERROR_NONE;    
    } // public function __construct ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Backup data and send notifications.
    ///////////////////////////////////////////////////////////////////////////
        
    public function FullBackup ()
    {
      $this->DisplayLogHeader ();

      $this->DisplayBackupLocation ();
      $this->BackupFolders ();
      $this->BackupDatabases ();
      $this->DisplayDiskInfo ();

      $this->CloseLogFile ();
      
      $this->EmailReports ();
    } // function FullBackup ()

    
    /**************************************************************************
     * Private Methods
     **************************************************************************/
    
    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Backup all databases and writes results to the log.
    ///////////////////////////////////////////////////////////////////////////
    
    private function BackupDatabases ()
    {
      // Write header information to log.
      
      $this->WriteLineToLog ('Databases');
      $this->WriteLineToLog ('=========');
      $this->WriteLineToLog ('');
            
      // Backup databases.
      
      $xml = simplexml_load_file ($this->m_sSettingsFile);

      foreach ($xml->Databases as $databases)
        foreach ($databases->Database as $database)
        {
          $sFolder = "{$this->m_sBackupPath}/databases/{$databases->DatabaseServer}/"; 

          if (!file_exists ($sFolder))
            mkdir ($sFolder);
          
          $this->BackupDatabase ($databases->DatabaseServer,
                                 $databases->Host,
                                 $databases->Port,
                                 $databases->Username,
                                 $databases->Password,
                                 $database);
        } // foreach ($databases->Database as $database)

      // Write footer information to log.
        
      $this->WriteLineToLog ('');
      $this->WriteLineToLog ('');
    } // function BackupDatabases ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Backs up a single database for MySQL or PostgreSQL. This variable
    //   is set in the <DatabaseServer> option in the settings file.
    ///////////////////////////////////////////////////////////////////////////
        
    function BackupDatabase ($sServer, $sHost, $sPort, $sUsername, $sPassword, $sDatabase)
    {
      $sDatabaseName = "";
      
      if ($this->m_sUseHost == "1")
        $sDatabaseName = "$sHost-";

      if ($this->m_sUsePort == "1")
        $sDatabaseName .= "$sPort-";
        
      if ($this->m_sUseUsername == "1")
        $sDatabaseName .= "$sUsername-";
        
      $sDatabaseName .= $sDatabase;
      
      switch ($sServer)
      {
        case 'MySQL' :
          $sCommand = "mysqldump --host=$sHost --user=$sUsername --password=$sPassword $sDatabase"
                    . " | gzip >{$this->m_sBackupPath}/databases/$sServer/$sDatabaseName.sql.gz";
          system ($sCommand);
          
          $this->WriteLineToLog ("$sServer: $sDatabase");

          break;
          
        case 'PostgreSQL' :
          $sCommand = "pg_dump --host=$sHost --username=$sUsername $sDatabase"
                    . " | gzip >{$this->m_sBackupPath}/databases/$sServer/$sDatabaseName.sql.gz";
                    
          system ($sCommand);
          
          $this->WriteLineToLog ("$sServer: $sDatabase");
           
          break;

        default :
          // Error in database server name.
      } // switch ($sServer)
    } // function BackupDatabase ($sServer, $sHost, $sPort, $sUsername, $sPassword, $sDatabase)


    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Backups up folders and writes results to the log.
    ///////////////////////////////////////////////////////////////////////////

    private function BackupFolders ()
    {
      // Write header information to log.

      $this->WriteLineToLog ('Folders');
      $this->WriteLineToLog ('=======');
      $this->WriteLineToLog ('');

      // Backup folderss.

      $xml = simplexml_load_file ($this->m_sSettingsFile);

      foreach ($xml->Folders as $folders)
        foreach ($folders->Folder as $folder)
        {
          $sBase = $folder ['Base'];
          $sFilename = strtr ($sBase, "/", "-") . $folder;

          $sCommand = "tar -zcf {$this->m_sBackupPath}/folders/$sFilename.tar.gz {$sBase}{$folder}";
          system ($sCommand);

          $this->WriteLineToLog ("Backed up: $folder");
        } // foreach ($folders->Folder as $folder)

      // Write footer information to log.

      $this->WriteLineToLog ('');
      $this->WriteLineToLog ('');
    } // function BackupFolders ()


    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Creates the nested folders required for the backups.
    ///////////////////////////////////////////////////////////////////////////

    private function CreateBackupFolder ()
    {
      $sBackupFolder = $this->m_sBackupPath . $this->m_sPrefix . '-' . $this->m_sBackupDate;
      $this->m_sBackupPath = $sBackupFolder;
      
      if (!file_exists ($sBackupFolder))
        mkdir ($sBackupFolder);
        
      if (!file_exists ($sBackupFolder . '/folders'))
        mkdir ($sBackupFolder . '/folders');

      if (!file_exists ($sBackupFolder . '/databases'))
        mkdir ($sBackupFolder . '/databases');
    } // private function CreateBackupFolder ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes data to the log.
    ///////////////////////////////////////////////////////////////////////////
    
    private function CreateLogFile ()
    {
      $sLogFile = 'backup/' . $this->m_sPrefix . '-' . $this->m_sBackupDate . '.log';
      
      $this->m_fLogFile = fopen ($sLogFile, 'w');
    } // private function CreateLogFile ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes final data to the log and closes it.
    ///////////////////////////////////////////////////////////////////////////
    
    private function CloseLogFile ()
    {
      $sNow = date ("l, F d, Y, h:i:s A");
      
      $this->WriteLineToLog ('---------------------------------------------');
      $this->WriteLineToLog ("Backup started on {$this->m_sBackupStartLongDate}.");
      $this->WriteLineToLog ("Backup completed on $sNow.");
      
      fclose ($this->m_fLogFile);
    } // private function CloseLogFile ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes the path of the backup folder to the log.
    ///////////////////////////////////////////////////////////////////////////
        
    private function DisplayBackupLocation ()
    {
      $sBackupLocation = 'backup/' . $this->m_sPrefix . '-' . $this->m_sBackupDate;
 
      $this->WriteLineToLog ('Backup Location');
      $this->WriteLineToLog ('===============');
      $this->WriteLineToLog ('');
      $this->WriteLineToLog ($sBackupLocation);
       
      $this->WriteLineToLog ('');
      $this->WriteLineToLog ('');
    } // function DisplayBackupLocation ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes the total amount of disk space and free disk space to the log.
    ///////////////////////////////////////////////////////////////////////////
         
    private function DisplayDiskInfo ()
    {
      // Write header information to log.
      
      $this->WriteLineToLog ('Hard Disk Usage');
      $this->WriteLineToLog ('===============');
      $this->WriteLineToLog ('');
      
      // Calculate free disk space in MB.
      
      $fFreeSpace = disk_free_space ('/') / 1024000.0;
      $fTotalSpace = disk_total_space ('/') / 1024000.0;
      
      // Convert free and total space to strings with 1 decimal place.
      
      $sFreeSpace = sprintf ("%.1f", $fFreeSpace);
      $sTotalSpace = sprintf ("%.1f", $fTotalSpace);
      
      $this->WriteLineToLog ("Free space: $sFreeSpace MB");
      $this->WriteLineToLog ("Total space: $sTotalSpace MB");
      $this->WriteLineToLog ('');
      
      // Calculate percentage of free space available and convert it to a string
      // with 1 decimal place.
      
      $fPercentFree = ($fFreeSpace / $fTotalSpace) * 100.0;
      $sPercentFree = sprintf ("%.1f", $fPercentFree);
      
      $this->WriteLineToLog ("Percent free: $sPercentFree%");

      // Write footer information to log.
        
      $this->WriteLineToLog ('');
      $this->WriteLineToLog ('');
    } // function DisplayDiskInfo ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes header information to the log.
    ///////////////////////////////////////////////////////////////////////////
        
    private function DisplayLogHeader ()
    {
      $this->WriteLineToLog ('BackupXML 1.00');
      $this->WriteLineToLog ('Copyright (C) 2006-2007 Johan Cyprich. All rights reserved.');
      $this->WriteLineToLog ('---------------------------------------------');
      $this->WriteLineToLog ('');
    } // private function DisplayLogHeader ()
    
    
    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   E-mails the log to the Users in the settings file.
    ///////////////////////////////////////////////////////////////////////////
        
    private function EmailReports ()
    {
      if ($this->m_bEmailReport)
      {
        $xml = simplexml_load_file ($this->m_sSettingsFile);

        foreach ($xml->Users as $users)
          foreach ($users->User as $user)
            $this->EmailReportToUser ($user);
      } // if ($this->m_bEmailReport)
    } // private function EmailReports ()
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Compose e-mail to the send to a user.
    ///////////////////////////////////////////////////////////////////////////
        
    private function EmailReportToUser ($sUser)
    {
      // Get name of log file.
      
      $sLogFile = 'backup/' . $this->m_sPrefix . '-' . $this->m_sBackupDate . '.log'; 
      
      // Send e-mail.
      
      $mail = new PHPMailer ();

      //$mail->IsSMTP ();
      $mail->Mailer = "mail";

      // Find out if SMTP authentication is being used.

      // if ($this->m_bUseSmtpAuthorization == "1")
      //   $mail->SMTPAuth = "true";

      // else
      //   $mail->SMTPAuth = "false";

      // Retrieve variables for required mail properties.

      // $mail->Host = $this->m_sSmtpAddress;

      $mail->From = $this->m_sFromEmail;
      $mail->FromName = $this->m_sFromName;

      $mail->AddAddress ($sUser, $sUser);

      $mail->Subject = "BackupXML log file for {$this->m_sBackupDate}";
      $mail->Body = file_get_contents ($sLogFile);

      $bSent = $mail->Send ();

//      if ($bSent)
//        $this->WriteLineToLog ($sUser);
//
//      else
//        $this->WriteLineToLog ("Could not send e-mail to $sUser.");
    } // private function EmailReportToUser ($sUser)
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes a string with a carriage return to the log.
    ///////////////////////////////////////////////////////////////////////////
    
    private function WriteLineToLog ($sText)
    {
      fwrite ($this->m_fLogFile, "$sText\n");
    } // private function WriteLineToLog ($sText)
    

    ///////////////////////////////////////////////////////////////////////////
    // PURPOSE:
    //   Writes a string without a carriage return to the log.
    ///////////////////////////////////////////////////////////////////////////
        
    private function WriteToLog ($sText)
    {
      fwrite ($this->m_fLogFile, $sText);
    } // private function WriteToLog ($sText)
     
  } // class BackupXML

?>
