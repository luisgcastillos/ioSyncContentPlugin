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
      new sfCommandOption('only-models', null, sfCommandOption::PARAMETER_OPTIONAL, 'List of model names'),
    ));

    $this->namespace = 'io';
    $this->name = 'mysqldump';
    $this->briefDescription = 'Does a database dump';
    $this->detailedDescription = <<<EOF
The [io:mysql-dump|INFO] task does things.
Call it with:

  [php symfony io:mysqldump|INFO]

To save the output to a file, call it with

  [php symfony io:mysqldump > database.sql|INFO]
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

    $cmd = sprintf('mysqldump %s -u %s -p%s -h "%s" "%s" %s',
        $options['mysqldump-options'],
        escapeshellarg($db->getParameter('username')),
        escapeshellarg($db->getParameter('password')),
        $dsn['host'],
        $dsn['dbname'],
        $this->getTablesToDump()
    );
    system($cmd);
  }

  /**
   * This function will return the tables that we want to sync
   *
   * @return string
   */
  protected function getTablesToDump()
  {
    /* @var $import Doctrine_Import_Mysql */
    $import = Doctrine_Manager::connection()->import;
    $tables = $import->listTables();
    $exclude_models = array_map(array('sfInflector','tableize'),sfConfig::get('app_ioSyncContent_database_ignore', array()));
    $tables = array_diff($tables,$exclude_models);;
    return implode(' ', $tables);
  }

}
