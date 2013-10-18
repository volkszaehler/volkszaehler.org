<?php

namespace app\controller;

class FileList extends \app\core\Controller {

    // GET
    public function index($request) {
        if ( !$request->is('ajax') ) {
            return $this->redirect('/');
        }

        $dir = realpath(urldecode($request->query['dir']));
        if ( !$dir ) {
            return array();
        }

        $test_directories = \app\lib\Library::retrieve('test_directories');
        $valid_dir = false;
        foreach ( $test_directories as $test_directory ) {
            if ( strpos($dir, realpath($test_directory)) === 0 ) {
                $valid_dir = true;
                break;
            }
        }

        if ( !$valid_dir ) {
            return array();
        }

        $dir .= '/';
        $files = scandir($dir);
        // Don't return anything if 'files' are '.' or '..'
        if ( count($files) < 3 ) {
            return array();
        }

        $ignore_hidden = \app\lib\Library::retrieve('ignore_hidden_folders');

        $final_dirs = array();
        $final_files = array();
        foreach ( $files as $file ) {
            $is_hidden = ( strpos($file, '.') === 0 );
            if (
                $file != '.' && $file != '..'
                && (!$is_hidden || !$ignore_hidden)
            ) {
                $path = $dir . $file;
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ( is_dir($path) ) {
                    $final_dirs[] = array(
                        'type' => 'directory',
                        'name' => $file,
                        'path' => $path
                    );
                } elseif ( is_file($path) && $ext == 'php' ) {
                    $final_files[] = array(
                        'type'      => 'file',
                        'name'      => $file,
                        'path'      => $path
                    );
                }
            }
        }

        return array_merge($final_dirs, $final_files);
    }

}

?>
