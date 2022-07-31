<?php

class SourceLogger {

    private $source_dir, $target_dir, $log_dir, $execution_file;

    /**
     * Constructor
     * @throws Exception
     */
    public function __construct() {
        $this->source_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'source';
        $this->target_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'target';
        $this->log_dir  = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'log';
        $this->execution_file = 'execution.log';

        /**
         * Terminate script if source dir not exits AND create log and target directories
         */
        $this->checkMainDirectoriesExists();
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->log_dir;
    }

    /**
     * @return string
     */
    public function getSourceDir(): string
    {
        return $this->source_dir;
    }

    /**
     * @return string
     */
    public function getTargetDir(): string
    {
        return $this->target_dir;
    }

    /**
     * Main function to check differences
     * @return void
     * @throws Exception
     */
    public function logState() {
        /**
         * Terminate script if source dir not exits AND create log and target directories
         */
        $this->checkMainDirectoriesExists();

        /**
         * Creating and appending execution logger
         */
        $date_today = date('Y-m-d');
        $hour_today = date('H:i:s');
        $execution_file_path =
            $this->log_dir .
            DIRECTORY_SEPARATOR .
            $date_today .
            '_' .
            $this->execution_file;
        $stream = fopen($execution_file_path, 'a+');
        $data = '-- Log started in: ' . $date_today . ' ' . $hour_today . ' -- ' . PHP_EOL;
        fwrite($stream, $data);

        /**
         * Recursive get of all target files
         */
        $file_list = $this->scanAllDir($this->target_dir);

        /**
         * Iterating file list and writing logger file
         */
        foreach ($file_list as $file_path) {
            $source_file_path = $this->source_dir . DIRECTORY_SEPARATOR . $file_path;
            $target_file_path = $this->target_dir . DIRECTORY_SEPARATOR . $file_path;
            if (!file_exists($source_file_path)) {
                $data = '- File ' . $source_file_path . ' NOT FOUND' . PHP_EOL;
                fwrite($stream, $data);
            } else {
                $file_check = $this->files_identical($source_file_path, $target_file_path, $file_path);
                if (!$file_check['status']) {
                    $data = '- File ' . $source_file_path . ' NOT EQUALS TO ' . $target_file_path .
                        ' , MESSAGE: ' . $file_check['msg'] . PHP_EOL;
                    fwrite($stream, $data);
                }
            }
        }

        /**
         * Closing stream
         */
        $date_today_end = date("Y-m-d H:i:s");
        $data = '-- Log terminated in: ' . $date_today_end . ' -- ' . PHP_EOL . PHP_EOL;
        fwrite($stream, $data);
        fclose($stream);
    }

    /**
     * Recursive delete directory
     * @param $dirPath
     * @return void
     */
    public function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != DIRECTORY_SEPARATOR) {
            $dirPath .= DIRECTORY_SEPARATOR;
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    /**
     * Recursive get of all target files
     * @param $target_dir
     * @return array
     */
    private function scanAllDir($target_dir): array
    {
        $result = [];

        foreach(scandir($target_dir) as $filename) {
            if ($filename[0] === '.') continue;
            $filePath = $target_dir . DIRECTORY_SEPARATOR . $filename;

            if (is_dir($filePath)) {
                foreach ($this->scanAllDir($filePath) as $childFilename) {
                    $result[] = $filename . DIRECTORY_SEPARATOR . $childFilename;
                }
            } else {
                $result[] = $filename;
            }
        }

        return $result;
    }

    /**
     * Check if two files are identical
     * @param $fn1
     * @param $fn2
     * @return array
     */
    private function files_identical($fn1, $fn2, $filepath): array
    {
        if(filetype($fn1) !== filetype($fn2)) {
            return array(
                'status' => false,
                'msg' => 'filetype not equals: ' . filetype($fn1) . ' and ' . filetype($fn2)
            );
        }

//        if(filesize($fn1) !== filesize($fn2)) {
//            return array(
//                'status' => false,
//                'msg' => 'filesize not equals: ' . filesize($fn1) . ' and ' . filesize($fn2)
//            );
//        }

        if(!$fp1 = fopen($fn1, 'rb')) {
            return array(
                'status' => false,
                'msg' => 'cannot open fn1 in rb mode'
            );
        }

        if(!$fp2 = fopen($fn2, 'rb')) {
            fclose($fp1);
            return array(
                'status' => false,
                'msg' => 'cannot open fn2 in rb mode'
            );
        }

        $same = array(
            'status' => true,
            'msg' => ''
        );
        $filesize_sum = filesize($fn1) + filesize($fn2);
        if ($filesize_sum == 0) {
            $filesize_sum = 10000;
        }
        while(!feof($fp1)) {
            $content_fp1 = fread($fp1, $filesize_sum);
            $content_fp2 = fread($fp2, $filesize_sum);
            $content_fp1 = preg_replace('~\R~u', "\r\n", $content_fp1);
            $content_fp2 = preg_replace('~\R~u', "\r\n", $content_fp2);
            if($content_fp1 != $content_fp2) {
                $same = array(
                    'status' => false,
                    'msg' => 'fread of fn1 is different from fn2'
                );
                $target_file_path_fp1 = $this->getLogDir() . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . $filepath;
                $target_file_path_fp2 = $this->getLogDir() . DIRECTORY_SEPARATOR . 'target' . DIRECTORY_SEPARATOR . $filepath;
                $list_target_dir = explode('\\', $filepath);
                $initial_dir = '';
                foreach($list_target_dir as $target_dir) {
                    if (strpos($target_dir, '.') !== false) {
                       continue;
                    }
                    if ($initial_dir == '') {
                        $initial_dir = $target_dir;
                    } else {
                        $initial_dir = $initial_dir . DIRECTORY_SEPARATOR . $target_dir;
                    }
                    $partial_source = $this->getLogDir() . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . $initial_dir;
                    $partial_target = $this->getLogDir() . DIRECTORY_SEPARATOR . 'target' . DIRECTORY_SEPARATOR . $initial_dir;
                    if (!file_exists($partial_source)) {
                        mkdir($partial_source);
                    }
                    if (!file_exists($partial_target)) {
                        mkdir($partial_target);
                    }
                }
                $stream_fp1 = fopen($target_file_path_fp1, 'w');
                $stream_fp2 = fopen($target_file_path_fp2, 'w');
                if ($stream_fp1 && $stream_fp2) {
                    fwrite($stream_fp1, $content_fp1);
                    fwrite($stream_fp2, $content_fp2);
                    fclose($stream_fp1);
                    fclose($stream_fp2);
                }
                break;
            }
        }

        if(feof($fp1) !== feof($fp2)) {
            $same = array(
                'status' => false,
                'msg' => 'feof fp1 is differnet from feof fp2'
            );
        }

        fclose($fp1);
        fclose($fp2);

        return $same;
    }

    /**
     * Check if main directories (source, log and target) exist
     * @throws Exception
     */
    private function checkMainDirectoriesExists()
    {
        /**
         * Terminate script if source dir not exits
         */
        if (!file_exists($this->source_dir)) {
            throw new Exception('SOURCE DIRECTORY DOES NOT EXISTS');
        }
        /**
         * Creating (if not exists) main directories
         */
        if (!file_exists($this->log_dir)) {
            mkdir($this->log_dir);
        }
        if (!file_exists($this->log_dir . DIRECTORY_SEPARATOR . 'source')) {
            mkdir($this->log_dir . DIRECTORY_SEPARATOR . 'source');
        }
        if (!file_exists($this->log_dir . DIRECTORY_SEPARATOR . 'target')) {
            mkdir($this->log_dir . DIRECTORY_SEPARATOR . 'target');
        }
        if (!file_exists($this->target_dir)) {
            mkdir($this->target_dir);
        }
    }
}
