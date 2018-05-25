<?php

namespace SE\Shop;

class FileFolder extends Base
{
    protected $tableName = 'file_folder';
    protected $tableAlias = 'f';

    public function fetch()
    {
        $protocol = $this->protocol;
        if ($this->input["treeMode"])
            return $this->getTreeFolders();

        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $path = DOCUMENT_ROOT . "/" . $this->dirFiles . $this->input["path"];
        $iterator = new \RecursiveDirectoryIterator($path);
        $fileList = [];
        $dirs = [];
        $files = [];
        foreach ($iterator as $entry) {
            if ($entry->getFilename() == '.' || $entry->getFilename() == '..')
                continue;

            $fileInfo["name"] = $entry->getFilename();
            $fileInfo["isDir"] = $entry->isDir();
            if (!$fileInfo["isDir"]) {
                $fileInfo["url"] = $protocol . "://" . HOSTNAME . "/" . $this->dirFiles . $this->input["path"] . $fileInfo["name"];

                $ext = getExtFile($this->input["path"] . $fileInfo["name"]);
                $fileInfo["urlPreview"] = "https://placeholdit.imgix.net/~text?txtsize=30&txtclr=ffffff&bg=2196F3&txt={$ext}&w=64&h=64&txttrack=0";

                $files[$fileInfo["name"]] = $fileInfo;
            } else $dirs[$fileInfo["name"]] = $fileInfo;
        }
        ksort($dirs);
        foreach ($dirs as $dir)
            $fileList[] = $dir;
        ksort($files);
        foreach ($files as $file)
            $fileList[] = $file;


        $this->result["items"] = $fileList;
    }

    private function getFolders($dir)
    {
        $folders = [];
        $r = opendir($dir);
        while (($file = readdir($r)) !== false)
        {
            $folderInfo = [];
            if ($file == '.' || $file == '..')
                continue;

            if (is_dir($path = $dir.DIRECTORY_SEPARATOR.$file))
            {
                $folderInfo["name"] = $file;
                $folderInfo["path"] = '/' . preg_replace("#^{$this->dirFiles}/#", "", $path) . '/';
                $childs = $this->getFolders($path);;
                if (count($childs))
                    $folderInfo["childs"] = $childs;
                $folders[$file] = $folderInfo;
            }
        }
        closedir($r);
        ksort($folders);

        $items = [];
        foreach ($folders as $folder)
            $items[] = $folder;

        return $items;
    }

    public function getTreeFolders()
    {
        $dir = $this->dirImages;
        $this->result["items"] = $this->getFolders($dir);

        return $this->result["items"];

    }

    public function save($isTransactionMode = true)
    {
        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $path = DOCUMENT_ROOT . "/" . $this->dirImages . $this->input["path"];
        $cmd = $this->input["cmd"] ? $this->input["cmd"] : "create";
        $name = $this->input["name"];
        if ($cmd == "create" && !empty($name)) {
            $path .= "/{$name}";
            if (!mkdir($path))
                $this->error = "Не удаётся создать папку с именем: {$name}!";
        }
        if ($cmd == "rename" && !empty($name)) {
            $newName = $path . "/" . $this->input["newName"];
            $path .= "/{$name}";
            if (!rename($path, $newName) || !$this->renameInBase($path, $newName, is_dir($newName)))
                $this->error = "Не удаётся переименовать указанный файл или папку";
        }
    }

    private function renameInBase($name, $newName, $idDir)
    {
        return true;
    }

    private function removeDirectory($dir)
    {
        if ($objList = glob($dir . "/*")) {
            foreach ($objList as $obj) {
                is_dir($obj) ? $this->removeDirectory($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }

    public function delete()
    {
        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $path = DOCUMENT_ROOT . "/" . $this->dirFiles . $this->input["path"];
        $files = $this->input["files"];
        foreach ($files as $file) {
            $file = substr($file, -1) == "/" ? $path . $file : $path . "/" . $file;
            if (!is_dir($file))
                unlink($file);
            else $this->removeDirectory($file);
        }
    }

}