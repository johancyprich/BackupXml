BackupXml
=====

Version: 1.0.1<br />
Date: January 19, 2015<br />
License: The MIT License (MIT)

Author: Johan Cyprich<br />
Author E-mail: jcyprich@live.com<br />
Author URL: www.cyprich.com


REQUIREMENTS
-----
PHP 5.x<br />
MySQL or PostgreSQL<br />
Install BackupXml on Linux


DESCRIPTION
-----
Backups website folders and the associated databases (MySQL or PostgreSQL) with it. This can be
automated as a cron job.


SETUP
-----
The xml/backup.xml file needs to be setup correctly in order for this app to work. The 
following describes each variable to set:


Set the e-mail server information and the backup path.
  
*Configuration*<br />
  - AdminUsername<br />
  - AdminPassword<br />
  - EmailReport: 1 = send notification, 0 = don't send<br />
  - UseSmtpAuthorization: 1 = use SMTP authentication, 0 = don't use<br />
  - SmtpAddress: address of mail server<br />
  - SmtpUsername: user name to login to mail server<br />
  - SmtpPassword: password of user for mail server<br />
  - FromEmail: e-mail address of sender for notifications<br />
  - FromName: name of user sending notifications<br />
  - Prefix: name of backup folder<br />
  - UseHost: 1 = use --host option in database backup, 0 = don't use<br />
  - UsePort: 1 = use --port option in database backup, 0 = don't use <br />
  - UseUsername: 1 = use --user or --username option in database backup, 0 = don't use<br />
  - BackupPath: path to backup data
  
  
Set the recipients for the log file.
  
*Users*<br />
  - User: e-mail of user to receive logs
  
  
Set the connection info for each database. DatabaseServer is either MySQL or PostgreSQL.
  
*Databases*<br />
  - DatabaseServer: MySQL or PostgreSQL<br />
  - Host: --host of database server<br />
  - Port: --port of database server<br />
  - Username: user for accessing database<br />
  - Password: password of user<br />
  - Database: database to backup


Set the folders that are to be backup up.
  
*Folders*<br />
  - Folder: folder to backup