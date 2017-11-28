<?php

use Robo\Tasks;

/**
 * Class RoboFile.
 */
class RoboFile extends Tasks {

  use Boedah\Robo\Task\Drush\loadTasks;
  use NuvoleWeb\Robo\Task\Config\loadTasks;

  /**
   * Install site.
   *
   * @command project:setup
   * @aliases pi
   */
  public function projectSetup() {
    $collection = [];
    $collection[] = $this->taskFilesystemStack()->chmod('build/sites', 0775, 0000, TRUE);
    $collection[] = $this->taskFilesystemStack()->symlink($this->root(), $this->root() . '/build/sites/all/modules/' . $this->getProjectName());
    $collection[] = $this->taskWriteConfiguration('build/sites/default/drushrc.php')->setConfigKey('drush');
    $collection[] = $this->taskAppendConfiguration('build/sites/default/default.settings.php')->setConfigKey('settings');
    if (file_exists('phpunit.xml.dist')) {
      $collection[] = $this->taskFilesystemStack()->copy('phpunit.xml.dist', 'phpunit.xml');
      $collection[] = $this->taskReplaceInFile('phpunit.xml')->from('%DRUPAL_ROOT%')->to($this->root() . '/build');
    }

    return $this->collectionBuilder()->addTaskList($collection);
  }

  /**
   * Install site.
   *
   * @command project:install
   * @aliases pi
   */
  public function projectInstall() {
    return $this->collectionBuilder()->addTaskList([
      $this->getInstallTask()->siteInstall($this->config('site.profile')),
      $this->getDrush()->drush('en ' . implode(' ', $this->config('modules.enable'))),
      $this->getDrush()->drush('dis ' . implode(' ', $this->config('modules.disable'))),
    ]);
  }

  /**
   * Get installation task.
   *
   * @return \Boedah\Robo\Task\Drush\DrushStack
   *   Drush installation task.
   */
  protected function getInstallTask() {
    return $this->getDrush()
      ->siteName($this->config('site.name'))
      ->siteMail($this->config('site.mail'))
      ->locale($this->config('site.locale'))
      ->accountMail($this->config('account.mail'))
      ->accountName($this->config('account.name'))
      ->accountPass($this->config('account.password'))
      ->dbPrefix($this->config('database.prefix'))
      ->dbUrl(sprintf("mysql://%s:%s@%s:%s/%s",
        $this->config('database.user'),
        $this->config('database.password'),
        $this->config('database.host'),
        $this->config('database.port'),
        $this->config('database.name')));
  }

  /**
   * Get configured Drush task.
   *
   * @return \Boedah\Robo\Task\Drush\DrushStack
   *   Drush installation task.
   */
  protected function getDrush() {
    return $this->taskDrushStack($this->config('bin.drush'))
      ->drupalRootDirectory($this->root() . '/build');
  }

  /**
   * Get root directory.
   *
   * @return string
   *   Root directory.
   */
  protected function root() {
    return getcwd();
  }

  /**
   * Get project name from composer.json.
   *
   * @return string
   *   Project name.
   */
  protected function getProjectName() {
    $package = json_decode(file_get_contents('./composer.json'));
    list(, $name) = explode('/', $package->name);
    return $name;
  }

}
