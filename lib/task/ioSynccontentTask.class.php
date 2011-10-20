<?php

class ioSynccontentTask extends sfBaseTask
{

  protected
  $outputBuffer = '',
  $errorBuffer = '';

  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('src', sfCommandArgument::REQUIRED, 'Source'),
      new sfCommandArgument('dest', sfCommandArgument::REQUIRED, 'Destination'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      new sfCommandOption('rsync-options', null, sfCommandOption::PARAMETER_REQUIRED, 'Options to use when rsyncing', '-avz'),
      new sfCommandOption('include-database', null, sfCommandOption::PARAMETER_NONE, 'Include the database'),
      new sfCommandOption('include-content', null, sfCommandOption::PARAMETER_NONE, 'Include files and folders as defined in app.yml'),
      new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Dry run, does not sync anything'),
      new sfCommandOption('mysqldump-options', null, sfCommandOption::PARAMETER_REQUIRED, 'Options to pass to mysqldump', '--skip-opt --add-drop-table --create-options --disable-keys --extended-insert --set-charset'),
      // add your own options here
    ));

    $this->namespace = 'io';
    $this->name = 'sync-content';
    $this->briefDescription = 'Syncs content from one server to another';
    $this->detailedDescription = <<<EOF
The [io:sync-content|INFO] task does things.
Call it with:

  [php symfony io:sync-content production localhost|INFO]

This will sync content from the production machine to the local machine.

NOTE: "localhost" will always refer to your local machine.

To sync content from your machine to another machine

  [php symfony io:sync-content localhost beta|INFO]

Sends content from your local machine to the beta server

See properties.ini for a list of machines you can push/pull to

See app.yml for settings

EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    $settings = parse_ini_file(sfConfig::get('sf_root_dir') . '/config/properties.ini', true);

    /**
     * Sync the databases
     */
    if(strcmp($arguments['src'], 'localhost') != 0 && array_key_exists($arguments['src'], $settings) && $options['include-database'])
    {
      $cmd = sprintf('ssh -p%s %s@%s \'cd %s; ./symfony io:mysqldump --application=%s --env=%s --connection=%s --mysqldump-options="%s"\' | ./symfony io:mysql-load --application=%s --env=%s --connection=%s',
          empty($settings[$arguments['src']]['port']) ? '22' : $settings[$arguments['src']]['port'],
          $settings[$arguments['src']]['user'],
          $settings[$arguments['src']]['host'],
          $settings[$arguments['src']]['dir'],
          $options['application'],
          $options['env'],
          $options['connection'],
          $options['mysqldump-options'],
          $options['application'],
          $options['env'],
          $options['connection']
      );
      if ($options['dry-run'])
      {
        $this->logSection('dry-run', 'syncing databases');
      }
      else
      {
        system($cmd);
      }
    }
    elseif(strcmp($arguments['dest'], 'localhost') != 0 && array_key_exists($arguments['dest'], $settings) && $options['include-database'])
    {
      throw new sfException('Not yet implimented');
    }
    elseif($options['include-database'])
    {
      throw new sfException(sprintf('Could not find host in properties.ini'));
    }

    /**
     * This will rsync the files and folders that you have in the app.yml
     */
    if(strcmp($arguments['src'], 'localhost') != 0 && array_key_exists($arguments['src'], $settings) && $options['include-content'] && strcmp($arguments['dest'], 'localhost') == 0)
    {
      if($contentArray = sfConfig::get('app_ioSyncContent_content', false))
      {
        $this->logSection('sync-content', 'Syncing content from remote server to localhost');
        foreach($contentArray as $content)
        {
          $cmd = sprintf('rsync %s %s -e "ssh -p%s" %s@%s:%s %s',
              $options['dry-run'] ? '--dry-run' : '',
              $options['rsync-options'],
              empty($settings[$arguments['src']]['port']) ? '22' : $settings[$arguments['src']]['port'],
              $settings[$arguments['src']]['user'],
              $settings[$arguments['src']]['host'],
              $settings[$arguments['src']]['dir'] . '/' . $content,
              $content
          );

          $this->getFilesystem()->execute($cmd, array($this, 'logOutput'), array($this, 'logErrors'));
          $this->clearBuffers();
        }
      }
    }
    elseif(strcmp($arguments['dest'], 'localhost') != 0 && array_key_exists($arguments['dest'], $settings) && $options['include-content'] && strcmp($arguments['src'], 'localhost') == 0)
    {
      if($contentArray = sfConfig::get('app_ioSyncContent_content', false))
      {
        $this->logSection('sync-content', 'Syncing content from localhost to remote server');
        foreach($contentArray as $content)
        {
          $cmd = sprintf('rsync %s %s -e "ssh -p%s" %s %s@%s:%s',
              $options['dry-run'] ? '--dry-run' : '',
              $options['rsync-options'],
              empty($settings[$arguments['dest']]['port']) ? '22' : $settings[$arguments['dest']]['port'],
              $content,
              $settings[$arguments['dest']]['user'],
              $settings[$arguments['dest']]['host'],
              $settings[$arguments['dest']]['dir'] . '/' . $content
          );

          $this->getFilesystem()->execute($cmd, array($this, 'logOutput'), array($this, 'logErrors'));
          $this->clearBuffers();
        }
      }
    }
    elseif($options['include-content'])
    {
      throw new sfException(sprintf('Could not find host in properties.ini'));
    }
  }

  public function logOutput($output)
  {
    if(false !== $pos = strpos($output, "\n"))
    {
      $this->outputBuffer .= substr($output, 0, $pos);
      $this->log($this->outputBuffer);
      $this->outputBuffer = substr($output, $pos + 1);
    }
    else
    {
      $this->outputBuffer .= $output;
    }
  }

  public function logErrors($output)
  {
    if(false !== $pos = strpos($output, "\n"))
    {
      $this->errorBuffer .= substr($output, 0, $pos);
      $this->log($this->formatter->format($this->errorBuffer, 'ERROR'));
      $this->errorBuffer = substr($output, $pos + 1);
    }
    else
    {
      $this->errorBuffer .= $output;
    }
  }

  protected function clearBuffers()
  {
    if($this->outputBuffer)
    {
      $this->log($this->outputBuffer);
      $this->outputBuffer = '';
    }

    if($this->errorBuffer)
    {
      $this->log($this->formatter->format($this->errorBuffer, 'ERROR'));
      $this->errorBuffer = '';
    }
  }

}
