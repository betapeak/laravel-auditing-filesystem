<?php

namespace BetaPeak\Auditing\Drivers;

use DateTime;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;

class FilesystemDriver implements AuditDriver
{
    /**
     * @var FilesystemAdapter
     */
    protected $disk = null;

    /**
     * @var string
     */
    protected $dir = null;

    /**
     * @var string
     */
    protected $filename = null;

    /**
     * @var string
     */
    protected $auditFilepath = null;

    /**
     * @var string One of ['single', 'daily', 'hourly']
     */
    protected $fileLoggingType = null;

    /**
     * FileSystem constructor.
     */
    public function __construct()
    {
        $this->disk = Storage::disk(Config::get('audit.drivers.filesystem.disk', 'local'));
        $this->dir = Config::get('audit.drivers.filesystem.dir', '');
        $this->filename = Config::get('audit.drivers.filesystem.filename', 'audit.csv');
        $this->fileLoggingType = Config::get('audit.drivers.filesystem.logging_type', 'single');
        $this->auditFilepath = $this->auditFilepath();
    }

    /**
     * {@inheritdoc}
     */
    public function audit(Auditable $model): Audit
    {
        if (!$this->disk->exists($this->auditFilepath)) {
            $this->disk->put($this->auditFilepath, $this->auditModelToCsv($model, true));
        } else {
            $this->disk->append($this->auditFilepath, $this->auditModelToCsv($model));
        }

        $implementation = Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class);

        return new $implementation;
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Auditable $model): bool
    {
        return false;
    }

    protected function auditModelToCsv(Auditable $model, bool $includeHeader = false)
    {
        $writer = Writer::createFromFileObject(new \SplTempFileObject());

        $auditArray = $this->sanitize($this->getAuditFromModel($model));
        if ($includeHeader) {
            $writer->insertOne($this->headerRow($auditArray));
        }
        $writer->insertOne($auditArray);

        // Remove trailing newline
        return trim($writer->getContent());
    }

    /**
     * Sanitize audit data before inserting it as a row in a csv file.
     * Currently serializes the old and new values.
     *
     * @param array $audit
     *
     * @return array
     */
    protected function sanitize(array $audit)
    {
        $audit['old_values'] = json_encode($audit['old_values']);
        $audit['new_values'] = json_encode($audit['new_values']);

        return $audit;
    }

    /**
     * Dynamically determine the current audit filepath based on the logging type config setting.
     *
     * @return string
     */
    protected function auditFilepath()
    {
        switch ($this->fileLoggingType) {
            case 'single':
                return $this->dir.$this->filename;

            case 'daily':
                $date = (new \DateTime('now'))->format('Y-m-d');

                return $this->dir."audit-$date.csv";

            case 'hourly':
                $dateTime = (new \DateTime('now'))->format('Y-m-d-H');

                return $this->dir."audit-$dateTime-00-00.csv";

            default:
                throw new \InvalidArgumentException("File logging type {$this->fileLoggingType} unknown. Please use one of 'single', 'daily' or 'hourly'.");
        }
    }

    /**
     * Transform an Auditable model into an audit array.
     *
     * @param Auditable $model
     *
     * @return array
     */
    protected function getAuditFromModel(Auditable $model)
    {
        return $this->appendCreatedAt($model->toAudit());
    }

    /**
     * Append a created_at key to the audit array.
     *
     * @param array $audit
     *
     * @return array
     */
    protected function appendCreatedAt(array $audit)
    {
        return array_merge($audit, ['created_at' => (new DateTime('now'))->format('Y-m-d H:i:s')]);
    }

    /**
     * Generate a header row from an audit array, based on the key strings.
     *
     * @param $audit
     *
     * @return array
     */
    protected function headerRow(array $audit)
    {
        return array_map(function ($key) {
            return ucwords(str_replace('_', ' ', $key));
        }, array_keys($audit));
    }
}
