<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'SourceLogger.php';
class FtpApplication
{
    private $host, $ftp_conn, $user, $psw;

    public function __construct($host, $user, $psw)
    {
        $this->host = $host;
        $this->user = $user;
        $this->psw = $psw;
        $this->ftp_conn = null;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return null
     */
    public function getFtpConn()
    {
        return $this->ftp_conn;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    private function getPsw()
    {
        return $this->psw;
    }

    /**
     * Download all resources listed by directories via FTP
     * @param SourceLogger $sourceLogger
     * @param $directories
     * @param $force_refresh
     * @param $is_target
     * @return bool
     * @throws Exception
     */
    public function downloadResources($sourceLogger, $directories, $force_refresh, $is_target): bool
    {
        //CONNECTING TO FTP
        $this->connect();
        //REMOVING TARGET DIRECTORIES IF EXIST AND FLAG IS TRUE
        if ($force_refresh) {
            foreach ($directories as $directory) {
                if ($is_target) {
                    chdir($sourceLogger->getTargetDir());
                } else {
                    chdir($sourceLogger->getSourceDir());
                }
                if (file_exists($directory) && is_dir($directory)) {
                    $sourceLogger->deleteDir($directory);
                }
            }
        }
        //COPYING TARGET DIRECTORIES
        foreach ($directories as $directory) {
            if ($is_target) {
                chdir($sourceLogger->getTargetDir());
            } else {
                chdir($sourceLogger->getSourceDir());
            }
            $this->ftpCopyDir($directory, $sourceLogger);
        }
        //CLOSING FTP CONNECTION
        $this->close();
        return true;
    }

    /**
     * Setup FTP connection
     * @throws Exception
     */
    protected function connect()
    {
        $conn = ftp_connect($this->getHost()) or die("Couldn't connect to host: " . $this->getHost());
        // Login
        if (ftp_login($conn, $this->getUser(), $this->getPsw())) {
            // Return the resource
            $this->ftp_conn = $conn;
            ftp_pasv($this->getFtpConn(), true);
        } else {
            $this->close();
            // Or return false
            throw new Exception("CANNOT LOGIN TO HOST: " . $this->getHost() .
                ', WITH USER: ' . $this->getUser() .
                ', AND PASSWORD: ' . $this->getPsw() .
                ', ERROR: ' . error_get_last());
        }
    }

    /**
     * Close FTP Connection
     * @return bool
     */
    protected function close(): bool
    {
        if ($this->getFtpConn() != null) {
            ftp_close($this->getFtpConn());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Recursive ftp dir structure copy
     * @param $dir
     * @param SourceLogger $sourceLogger
     * @return void
     * @throws Exception
     */
    private function ftpCopyDir($dir, $sourceLogger)
    {
        if ($dir != ".") {
            if (ftp_chdir($this->getFtpConn(), $dir) == false) {
                $this->close();
                throw new Exception("CANNOT CHANGE DIR: " . $dir);
            }
            if (!(is_dir($dir))) {
                mkdir($dir);
            }
            chdir($dir);
        }

        $contents = ftp_nlist($this->getFtpConn(), ".");
        foreach ($contents as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (@ftp_chdir($this->getFtpConn(), $file)) {
                ftp_chdir($this->getFtpConn(), "..");
                $this->ftpCopyDir($file, $sourceLogger);
            } else {
                ftp_get($this->getFtpConn(),
                    $file,
                    $file,
                    FTP_BINARY
                );
            }
        }
        ftp_chdir ($this->getFtpConn(), "..");
        chdir("..");
    }
}
