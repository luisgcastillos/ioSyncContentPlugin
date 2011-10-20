<?php

class ioMysqlloadTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name','frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace        = 'io';
    $this->name             = 'mysql-load';
    $this->briefDescription = 'Loads information from a mysqldump into the database';
    $this->detailedDescription = <<<EOF
The [io:mysql-load|INFO] task does things.
Call it with:

  [php symfony io:mysql-load|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    if (!function_exists('passthru'))
    {
      throw new sfException('You cannot use this task as you do not have access to the passthru function.');
    }

    $databaseManager = new sfDatabaseManager($this->configuration);
    
    /* @var $db sfDoctrineDatabase */
    $db = $databaseManager->getDatabase($options['connection']);

    $dsn = ioSyncContentToolkit::parseDsn($db->getParameter('dsn'));

    $cmd = sprintf('mysql -u "%s" -p"%s" -h "%s" "%s"',
      $db->getParameter('username'),
      $db->getParameter('password'),
      $dsn['host'],
      $dsn['dbname']
    );
    passthru($cmd);
  }
}
