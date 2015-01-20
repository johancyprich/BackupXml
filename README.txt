BackupXml
=========

Version: 1.0.1
Date: January 19, 2015
License: The MIT License (MIT)

Author: Johan Cyprich
Author E-mail: jcyprich@live.com
Author URL: www.cyprich.com


REQUIREMENTS
------------
PHP 5.x
MySQL or PostgreSQL
Install BackupXml on Linux


DESCRIPTION
-----------
Backups website folders and the associated databases (MySQL or PostgreSQL) with it. This can be
automated as a cron job.


SETUP
-----
The xml/backup.xml file needs to be setup correctly in order for this app to work. The 
following describes each variable to set:


Set the e-mail server information and the backup path.
  
Configuration
  - AdminUsername
  - AdminPassword
  - EmailReport: 1 = send notification, 0 = don't send
  - UseSmtpAuthorization: 1 = use SMTP authentication, 0 = don't use
  - SmtpAddress: address of mail server
  - SmtpUsername: user name to login to mail server
  - SmtpPassword: password of user for mail server
  - FromEmail: e-mail address of sender for notifications
  - FromName: name of user sending notifications
  - Prefix: name of backup folder
  - UseHost: 1 = use --host option in database backup, 0 = don't use
  - UsePort: 1 = use --port option in database backup, 0 = don't use 
  - UseUsername: 1 = use --user or --username option in database backup, 0 = don't use
  - BackupPath: path to backup data
  
  
Set the recipients for the log file.
  
Users
  - User: e-mail of user to receive logs
  
  
Set the connection info for each database. DatabaseServer is either MySQL or PostgreSQL.
  
Databases
  - DatabaseServer: MySQL or PostgreSQL
  - Host: --host of database server
  - Port: --port of database server
  - Username: user for accessing database
  - Password: password of user
  - Database: database to backup


Set the folders that are to be backup up.
  
Folders
  - Folder: folder to backup