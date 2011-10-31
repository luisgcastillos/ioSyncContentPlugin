<?php

error_reporting(E_ALL);

class ioMysqldumpTask extends sfBaseTask
{

  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      new sfCommandOption('mysqldump-options', null, sfCommandOption::PARAMETER_REQUIRED, 'Options to pass to mysqldump', '--skip-opt --add-drop-table --create-options --disable-keys --extended-insert --set-charset'),
    ));

    $this->namespace = 'io';
    $this->name = 'mysqldump';
    $this->briefDescription = 'Does a database dump';
    $this->detailedDescription = <<<EOF
The [io:mysql-dump|INFO] task does things.
Call it with:

  [php symfony io:mysqldump|INFO]

To save the output to a file, call it with

  [php symfony io:mysqldump > database.yml|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    if (!function_exists('system'))
    {
      throw new sfException('You cannot use this task as you do not have access to the system function.');
    }

    $databaseManager = new sfDatabaseManager($this->configuration);
    
    /* @var $db sfDoctrineDatabase */
    $db = $databaseManager->getDatabase($options['connection']);

    $dsn = ioSyncContentToolkit::parseDsn($db->getParameter('dsn'));

    $cmd = sprintf('mysqldump %s -u %s -p%s -h "%s" "%s"',
        $options['mysqldump-options'],
        escapeshellarg($db->getParameter('username')),
        escapeshellarg($db->getParameter('password')),
        $dsn['host'],
        $dsn['dbname']
    );
    system($cmd);
  }

}
