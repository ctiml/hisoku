<?php

class CronJobRow extends Pix_Table_Row
{
    public function runJob()
    {
        echo $this->id . "\n";
    //    $this->update(array('last_run_at' => time()));

        $node = WebNode::getUnusedNode($this->project);
    }

    public function getNextRunAt()
    {
        if ($this->start_at > time()) {
            return $this->start_at;
        }

        if (!$this->last_run_at) {
            return time();
        }

        return $this->last_run_at + CronJob::getPeriodTime($this->period);
    }
}

class CronJob extends Pix_Table
{
    protected static $_period_map = array(
        1 => 600,
        2 => 3600,
        3 => 86400,
    );

    public function init()
    {
        $this->_name = 'cron_job';
        $this->_rowClass = 'CronJobRow';

        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['project_id'] = array('type' => 'int');
        // 1 - 10minutes, 2 - hourly, 3 - daily
        $this->_columns['period'] = array('type' => 'tinyint');
        $this->_columns['start_at'] = array('type' => 'int');
        $this->_columns['last_run_at'] = array('type' => 'int');
        $this->_columns['job'] = array('type' => 'text');

        $this->_relations['project'] = array('rel' => 'has_one', 'type' => 'Project', 'foreign_key' => 'project_id');

        $this->addIndex('project', array('project_id'));
        $this->addIndex('period_lastrunat', array('period', 'last_run_at'));
    }

    public static function getPeriodTime($period_id)
    {
        return self::$_period_map[$period_id];
    }

    public static function runPendingJobs()
    {
        foreach (self::$_period_map as $period_id => $time) {
            foreach (self::search(array('period' => $period_id))->search("last_run_at < " . (time() - $time)) as $cronjob) {
                try {
                    $cronjob->runJob();
                } catch (Exception $e) {
                    // TODO: log it ...
                }
            }
        }
    }
}