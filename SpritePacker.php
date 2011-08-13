<?php

require_once 'RectanglePacker.php';

class SpritePacker {
    private $packers = array();
    private $defaultWidth, $defaultHeight;
    private $trim;

    public function __construct($defaultWidth, $defaultHeight, $trim) {
        $this->defaultWidth  = $defaultWidth;
        $this->defaultHeight = $defaultHeight;
        $this->trim = $trim;
    }

    public function insertFiles($filenames) {
        $datas = array_map(array($this, 'getFileData'), $filenames);
        usort($datas, array('SpritePacker', 'sortFileDatas'));
        $datas = array_reverse($datas);

        foreach ($datas as $data) {
            $this->insertFileData($data);
        }
    }

    public function insertFile($filename) {
        $this->insertFileData($this->getFileData($filename));
    }

    private static function sortFileDatas($a, $b) {
        $aDim = max($a['width'], $a['height']);
        $bDim = max($b['width'], $b['height']);

        if ($aDim < $bDim) return -1;
        if ($aDim > $bDim) return 1;
        return 0;
    }

    private function getFileData($filename) {
        $image = imagecreatefrompng($filename);

        if (!$image) {
            error('Failed to load image file: ' . $file);
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if ($this->trim) {
            $dest = SpritePacker::trim($image);
        } else {
            $dest = array(0, 0, $width, $height);
        }

        return array(
            'width'  => $dest[2],
            'height' => $dest[3],
            'data' => array(
                'image' => $image,
                'file' => $filename,
                'dest' => $dest
            )
        );
    }

    private function insertFileData($fileData) {
        $width = $fileData['width'];
        $height = $fileData['height'];
        $data = $fileData['data'];

        if (!$this->tryInsert($width, $height, $data)) {
            $packer = $this->createRectanglePacker();

            if (!$packer->tryInsert($width, $height, $data)) {
                // Our default size is too small; make a packer just large 
                // enough for this file
                $packer = new RectanglePacker($width, $height);

                if (!$packer->tryInsert($width, $height, $data)) {
                    error('Universe is off balance');
                }
            }

            $this->packers[] = $packer;
        }
    }

    // I should probably split the side effect from the return value...
    public function writeSpriteSheets($outputDir) {
        foreach ($this->packers as $i => $packer) {
            $this->writeSpriteSheet($packer, $outputDir . '/' . $this->getSpriteSheetFileName($i));
        }
    }

    private function writeSpriteSheet($packer, $filename) {
        list($width, $height) = $packer->occupiedSize();

        $spriteSheet = imagecreatetruecolor($width, $height);
        imagealphablending($spriteSheet, false);
        imagesavealpha($spriteSheet, true);

        $background = imagecolorallocatealpha($spriteSheet, 0, 0, 0, 127);
        imagefilledrectangle($spriteSheet, 0, 0, $width, $height, $background);

        foreach ($packer->rectangles as $rectangle) {
            $this->writeSprite($rectangle, $spriteSheet);
        }

        imagepng($spriteSheet, $filename, 0);
        imagedestroy($spriteSheet);
    }

    private function writeSprite($sprite, $spriteSheet) {
        $dest = $sprite['data']['dest'];

        imagecopy(
            $spriteSheet, $sprite['data']['image'],
            $sprite[0], $sprite[1],
            $dest[0], $dest[1],
            $dest[2], $dest[3]
        );
    }

    public function getSpriteSheetDefinitions() {
        $out = array();

        foreach ($this->packers as $i => $packer) {
            $out[] = array(
                'file' => $this->getSpriteSheetFileName($i),
                'images' => array_map(array($this, 'getSpriteDefinition'), $packer->rectangles)
            );
        }

        return $out;
    }

    private function getSpriteDefinition($sprite) {
        return array(
            'file' => $sprite['data']['file'],
            'src' => array(
                $sprite[0],
                $sprite[1],
                $sprite[2],
                $sprite[3]
            ),
            'dest' => $sprite['data']['dest']
        );
    }

    private function createRectanglePacker() {
        return new RectanglePacker($this->defaultWidth, $this->defaultHeight);
    }

    private function tryInsert($width, $height, $data) {
        foreach ($this->packers as $packer) {
            if ($packer->tryInsert($width, $height, $data)) {
                return true;
            }
        }

        return false;
    }

    private function getSpriteSheetFileName($i) {
        return 'sprite' . $i . '.png';
    }

    private static function trim($image) {
        $width  = imagesx($image);
        $height = imagesy($image);

        $left   = 0;       while (SpritePacker::isClear($image, $left     , 0          , 0, 1)) ++$left;
        $top    = 0;       while (SpritePacker::isClear($image, 0         , $top       , 1, 0)) ++$top;
        $right  = $width;  while (SpritePacker::isClear($image, $right - 1, 0          , 0, 1)) --$right;
        $bottom = $height; while (SpritePacker::isClear($image, 0         , $bottom - 1, 1, 0)) --$bottom;

        return array($left, $top, max(0, $right - $left), max(0, $bottom - $top));
    }

    private static function isClear($image, $x, $y, $dx, $dy) {
        $width  = imagesx($image);
        $height = imagesy($image);

        if (!($x >= 0 && $y >= 0 && $x < $width && $y < $height)) {
            return false;
        }

        while ($x >= 0 && $y >= 0 && $x < $width && $y < $height) {
            $color = imagecolorat($image, $x, $y);

            $alpha = $color >> 24;

            if ($alpha !== 127) {
                return false;
            }

            $x += $dx;
            $y += $dy;
        }

        return true;
    }
}
