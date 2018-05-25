<?php

namespace SE\Shop;

class Image extends Base
{
    private $dir;

    public function fetch()
    {
        $listFiles = array();
        $count = 0;
        $searchStr = null;
        $protocol = $this->protocol;
        $this->dir = $this->dirImages . $this->input["path"];
        if (file_exists($this->dir) && is_dir($this->dir)) {
            $handleDir = opendir($this->dir);
            $i = 0;
            while (($file = readdir($handleDir)) !== false) {
                if ($file == '.' || $file == '..' || is_dir($this->dir .$file))
                    continue;
                if ($searchStr && (strpos(mb_strtolower($file), $searchStr) === false))
                    continue;
                $count++;
                if ($i++ < $this->offset)
                    continue;

                if ($count <= $this->limit + $this->offset) {
                    $fileName = $file;
                    $item["fileName"] = $fileName;
                    $item["imagePath"] = $this->input["path"] . $fileName;
                    $item["url"] = $protocol . "://" . HOSTNAME . "/" . $this->dirImages . $this->input["path"] . $fileName;
                    $ext = getExtFile($this->input["path"] . $fileName);
                    $size = "{$this->imagePreviewSize}x{$this->imagePreviewSize}";
                    $file = md5("/" . $this->dirImages . $this->input["path"] . $fileName) . "_s{$size}." . $ext;
                    $path = $this->dirThumbs . "/" . substr($file, 0, 2) . '/';
                    $path .= substr($file, 2, 2) . '/';
                    if (file_exists(DOCUMENT_ROOT . "/" . $path . $file))
                        $item["urlPreview"] = $this->protocol . "://" . HOSTNAME . "/"  . $path . $file;
                    else {
                        $image = '/' . $this->dirImages . $this->input["path"] . $fileName;
                        $item["urlPreview"] = $this->protocol . "://" .
                            HOSTNAME . "/"  . (new \plugin_image())->getImage($image, $size);
                    }
                    $files[$file] = $item;
                    $listFiles[] = $item;
                }
            }
            closedir($handleDir);
        }
        $this->result['count'] = $count;
        $this->result['items'] = $listFiles;
        return $listFiles;
    }

    public function post()
    {
        $this->input = $_POST;
        $countFiles = count($_FILES);
        $ups = 0;
        $files = array();
        $items = array();
        $protocol = $this->protocol;
        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $this->input["path"] = substr($this->input["path"], -1) == "/" ? $this->input["path"] : $this->input["path"] . "/";
        $path = DOCUMENT_ROOT . "/" . $this->dirImages . $this->input["path"];

        for ($i = 0; $i < $countFiles; $i++) {
            $file = $_FILES["file$i"]['name'];
            $uploadFile = $path . $file;
            $fileTemp = $_FILES["file$i"]['tmp_name'];
            if (!getimagesize($fileTemp)) {
                $this->error = "Ошибка! Найден файл не являющийся изображением!";
                return;
            }

            if (!filesize($fileTemp) || move_uploaded_file($fileTemp, $uploadFile)) {
                if (file_exists($uploadFile)) {
                    $files[] = $uploadFile;
                    $item = array();
                    $item["name"] = $file;
                    $item["title"] = $file;
                    $item["weight"] = number_format(filesize($uploadFile), 0, '', ' ');
                    list($width, $height, $type, $attr) = getimagesize($uploadFile);
                    $item["sizeDisplay"] = $width . " x " . $height;
                    $item["imagePath"] = $this->input["path"] . urlencode($file);
                    $item["imageUrl"] = $protocol . "://" . HOSTNAME . "/" . $this->dirImages .
                        $item["imagePath"];
                    $item["imageUrlPreview"] = $protocol . "://" . HOSTNAME . "/" . $this->dirImages .
                        $item["imagePath"];
                    $items[] = $item;
                }
                $ups++;
            }
        }
        if ($ups == $countFiles)
            $this->result['items'] = $items;
        else $this->error = "Не удается загрузить файлы!";

        return $items;
    }

}
