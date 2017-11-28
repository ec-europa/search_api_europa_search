<?php

use Robo\Tasks;

/**
 * Class RoboFile.
 */
class RoboFile extends Tasks {

  use Boedah\Robo\Task\Drush\loadTasks;
  use NuvoleWeb\Robo\Task\Config\loadTasks;

  /**
   * Setup project.
   *
   * This command will create the necessary symlinks and scaffolding files.
   *
   * @command project:setup
   * @aliases ps
   */
  public function projectSetup() {
    $collection = $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()->chmod($this->getSiteRoot() . '/sites', 0775, 0000, TRUE),
      $this->taskFilesystemStack()->symlink($this->getProjectRoot(), $this->getSiteRoot() . '/sites/all/modules/' . $this->getProjectName()),
      $this->taskWriteConfiguration($this->getSiteRoot() . '/sites/default/drushrc.php')->setConfigKey('drush'),
      $this->taskAppendConfiguration($this->getSiteRoot() . '/sites/default/default.settings.php')->setConfigKey('settings'),
    ]);

    if (file_exists('behat.yml.dist')) {
      $collection->addTask($this->projectSetupBehat());
    }

    if (file_exists('phpunit.xml.dist')) {
      $collection->addTask($this->projectSetupPhpUnit());
    }

    return $collection;
  }

  /**
   * Setup PHPUnit.
   *
   * This command will copy phpunit.xml.dist in phpunit.xml and replace
   * %DRUPAL_ROOT% and %BASE_URL% with configuration values provided in
   * robo.yml.dist (overridable by robo.yml).
   *
   * @command project:setup-phpunit
   * @aliases psp
   *
   * @return \Robo\Collection\CollectionBuilder
   *   Collection builder.
   */
  public function projectSetupPhpUnit() {
    return $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()->copy('phpunit.xml.dist', 'phpunit.xml'),
      $this->taskReplaceInFile('phpunit.xml')
        ->from(['%DRUPAL_ROOT%', '%BASE_URL%'])
        ->to([$this->getSiteRoot(), $this->config('site.base_url')]),
    ]);
  }

  /**
   * Setup Behat.
   *
   * This command will copy behat.yml.dist in behat.yml and replace
   * %DRUPAL_ROOT% and %BASE_URL% with configuration values provided in
   * robo.yml.dist (overridable by robo.yml).
   *
   * @command project:setup-behat
   * @aliases psb
   *
   * @return \Robo\Collection\CollectionBuilder
   *   Collection builder.
   */
  public function projectSetupBehat() {
    return $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()->copy('behat.yml.dist', 'behat.yml'),
      $this->taskReplaceInFile('behat.yml')
        ->from(['%DRUPAL_ROOT%', '%BASE_URL%'])
        ->to([$this->getSiteRoot(), $this->config('site.base_url')]),
    ]);
  }

  /**
   * Install target site.
   *
   * This command will install the target site using configuration values
   * provided in robo.yml.dist (overridable by robo.yml).
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
      ->drupalRootDirectory($this->getSiteRoot());
  }

  /**
   * Get getProjectRoot directory.
   *
   * @return string
   *   Root directory.
   */
  protected function getProjectRoot() {
    return getcwd();
  }

  /**
   * Get getProjectRoot directory.
   *
   * @return string
   *   Root directory.
   */
  protected function getSiteRoot() {
    return $this->getProjectRoot() . '/' . $this->config('site.root');
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
