<?php

class UOJProblemCandidateDataManager {
    public UOJProblem $problem;
    public string $svnusr;
    public string $svnpwd;
    public string $blame;
    private $lock_fp;

    public function __construct(UOJProblem $problem) {
        $this->problem = $problem;
		$this->svnusr = UOJConfig::$data['svn']['our-root']['username'];
		$this->svnpwd = UOJConfig::$data['svn']['our-root']['password'];
        $this->blame = Auth::check() ? Auth::id() : 'An anonymous user';
    }

    public function dataPath() {
        return $this->problem->getCandidateDataPath();
    }

    public function svnPath() {
        return $this->problem->getCandidateDataSVNPath();
    }

    public function workCopyPath() {
        return $this->svnPath()."/cur/{$this->problem->info['id']}";
    }

    public function svnCmd(array $cmd) {
        return ['svn', ...$cmd, '--username', $this->svnusr, '--password', $this->svnpwd];
    }

    public function svnUpdate() {
        if (!$this->lock()) {
            return false;
        }
        $ret = UOJLocalRun::execAnd([
            ['cd', $this->workCopyPath()],
            $this->svnCmd(['update', '--force'])
        ]);
        $this->unlock();
        return $ret;
    }

    public function svnInit() {
        if (!$this->start_update()) {
            return false;
        }
        $ret = UOJLocalRun::execAnd([
            ['cd', $this->workCopyPath()],
            ['mkdir', '1']
        ]);
        if (!$this->end_update()) {
            return false;
        }
        return $ret;
    }

    public function lock() {
		$this->lock_fp = fopen($this->svnPath().'/cur_lock', 'c');
		return flock($this->lock_fp, LOCK_EX | LOCK_NB);
    }

    public function unlock() {
        flock($this->lock_fp, LOCK_UN | LOCK_NB);
        $this->lock_fp = null;
    }

    public function start_update() {
        return $this->lock();
    }

    public function end_update($msg = null) {
        if ($msg === null) {
            $msg = "{$this->blame} updates the data via broswer";
        }
        $ret = UOJLocalRun::execAnd([
            ['cd', $this->workCopyPath()],
            $this->svnCmd(['add', '.', '--no-ignore', '--force']),
            $this->svnCmd(['commit', '-m', $msg])
        ]);
        $this->unlock();
        return $ret !== false;
    }

    public function rename($src, $dest) {
        return UOJLocalRun::exec($this->svnCmd(['mv', $src, $dest]));
    }

    public function copy($src, $dest) {
        return UOJLocalRun::exec($this->svnCmd(['cp', $src, $dest]));
    }

    public function unlink($path) {
        return UOJLocalRun::exec($this->svnCmd(['rm', $path]));
    }

    public function rmdir($path) {
        return UOJLocalRun::exec($this->svnCmd(['rm', $path]));
    }
}