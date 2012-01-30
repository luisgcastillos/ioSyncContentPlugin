<?php

class ioMysqlloadTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name','frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      new sfCommandOption('mysqldump-options', null, sfCommandOption::PARAMETER_REQUIRED, 'Options to pass to mysqldump', '--skip-opt --add-drop-table --create-options --disable-keys --extended-insert --set-charset'),
      new sfCommandOption('backup', null, sfCommandOption::PARAMETER_NONE, 'Backup database before loading sql'),
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


    if ($options['backup'])
    {
      $cmd = sprintf('mysqldump %s -u %s -p%s -h "%s" "%s" > %s/sql/%s.sql',
          $options['mysqldump-options'],
          escapeshellarg($db->getParameter('username')),
          escapeshellarg($db->getParameter('password')),
          $dsn['host'],
          $dsn['dbname'],
          sfConfig::get('sf_data_dir'),
          time()
      );
      $this->getFilesystem()->mkdirs(sfConfig::get('sf_data_dir') . '/sql');
      system($cmd);
    }

    $cmd = sprintf('mysql -u %s -p%s -h "%s" "%s"',
      escapeshellarg($db->getParameter('username')),
      escapeshellarg($db->getParameter('password')),
      $dsn['host'],
      $dsn['dbname']
    );
    passthru($cmd);
  }
}
