<?php

/**
 * Description
 *
 * @author
 * @copyright ioStudio
 * @package
 * @subpackage
 * @version
 *
 */

class ioSyncContentToolkit
{

  /**
   * This function takes a DSN and will return all the parameters
   *
   * @param string $dsn
   * @return array
   */
  public static function parseDsn($dsn)
  {
    preg_match("/([a-zA-Z0-9]+):(.*)/",$dsn,$a);
    // $a[0] = mysql:host=LOCALHOST;dbname=DATABASENAME
    // $a[1] = mysql
    // $a[2] = host=LOCALHOST;dbname=DATABASENAME

    $params['driver'] = $a[1];

    preg_match_all("/([a-zA-Z0-9]+)=([a-zA-Z0-9_\.]+)/",$a[2],$b);
    // $b[0][0] = host=LOCALHOST
    // $b[0][1] = dbname=DATABASENAME

    foreach ($b[0] as $param)
    {
      $c = explode('=',$param);
      $params[$c[0]] = $c[1];
    }
    
    return $params;
  }
}