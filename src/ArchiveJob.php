<?php

namespace MatinUtils\DataArchive;


class ArchiveJob extends Job
{
    protected $configs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
        if (empty($this->configs['archiveDatabaseConnection'])) {
            if (!empty($this->configs['messageHandler'])) {
                return $this->configs['messageHandler']('Archive database connection is required');
            }
            return;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $archivables = $this->configs['archivables'] ?? [];
        foreach ($archivables as $archivable) {
            archiveProccess($this->configs['archiveDatabaseConnection'], $archivable, $this->configs['messageHandler'] ?? '');
        }
    }
}
