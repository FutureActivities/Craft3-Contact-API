<?php
namespace futureactivities\contactapi\console\controllers;

use Craft;
use craft\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use futureactivities\contactapi\elements\Contact;
use craft\helpers\FileHelper;

class PurgeController extends Controller
{
    // Allow skipping of confirmation step
    public bool $force = false;

    // Run database backup first?
    public bool $backup = true;

    // Number of database backups to retain
    public int $keep = 3;

    // Database backup prefix
    protected string $prefix = 'contactapi_';

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['force','backup','keep']);
    }

    /**
     * Purge contact records older than the date specified
     */
    public function actionRun(int $days = 365)
    {
        if (!$this->force && !$this->confirm('This will remove all contact records older than '.$days.' days. This cannot be reversed. Continue?', false)) {
            $this->stdout("Aborted.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        // Backup database
        if ($this->backup) {
            $backupPath = Craft::$app->getPath()->getDbBackupPath();
            $filePath = $backupPath . DIRECTORY_SEPARATOR . $this->prefix . date('Y-m-d_His') . '.sql.gz';
            Craft::$app->getDb()->backupTo($filePath);
            $this->pruneBackups($this->keep, $this->prefix);
        }
        
        // Get all records older than date specified
        $cutoffDate = new \DateTime("-{$days} days");
        $records = Contact::find()->dateCreated("< {$cutoffDate->format(\DateTime::ATOM)}")->all();

        foreach($records AS $record) {
            Craft::$app->getElements()->deleteElement($record, true);
        }

        return ExitCode::OK;
    }

    /**
     * Remove old backups
     */
    protected function pruneBackups(int $keep = 5, ?string $namePrefix = null): int
    {
        $dir = Craft::$app->getPath()->getDbBackupPath();

        // Find .sql and .sql.gz files in the backup folder
        $files = FileHelper::findFiles($dir, [
            'only' => ['*.sql', '*.sql.gz'],
            'recursive' => false,
        ]);

        // If you name your backups with a prefix (e.g. env), limit pruning to those
        if ($namePrefix) {
            $files = array_values(array_filter($files, fn($f) =>
                str_starts_with(basename($f), $namePrefix)
            ));
        }

        // Newest first
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        // Delete everything after the first $keep files
        $toDelete = array_slice($files, $keep);
        $deleted = 0;
        foreach ($toDelete as $file) {
            @unlink($file);
            $deleted++;
        }

        return $deleted;
    }
}