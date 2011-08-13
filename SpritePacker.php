<?php

require_once 'RectanglePacker.php';

class SpritePacker {
    private $packers = array();
    private $defaultWidth, $defaultHeight;

    public function __construct($defaultWidth, $defaultHeight) {
        $this->defaultWidth  = $defaultWidth;
        $this->defaultHeight = $defaultHeight;
    }

    public function insertFiles($filenames) {
        $datas = array_map(array($this, 'getFileData'), $filenames);
        usort($datas, array('SpritePacker', 'sortFileDatas'));

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

        return $aDim > $bDim ? -1 : $aDim < $bDim ? 1 : 0;
    }

    private function getFileData($filename) {
        $image = imagecreatefrompng($filename);

        if (!$image) {
            error('Failed to load image file: ' . $file);
        }

        return array(
            'width'  => imagesx($image),
            'height' => imagesy($image),
            'data' => array(
                'image' => $image,
                'file' => $filename
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
        $spriteSheet = imagecreatetruecolor($packer->width, $packer->height);
        imagealphablending($spriteSheet, false);
        imagesavealpha($spriteSheet, true);

        $background = imagecolorallocatealpha($spriteSheet, 0, 0, 0, 127);
        imagefilledrectangle($spriteSheet, 0, 0, $packer->width, $packer->height, $background);

        foreach ($packer->rectangles as $rectangle) {
            $this->writeSprite($rectangle, $spriteSheet);
        }

        imagepng($spriteSheet, $filename, 0);
        imagedestroy($spriteSheet);
    }

    private function writeSprite($sprite, $spriteSheet) {
        imagecopy(
            $spriteSheet, $sprite['data']['image'],
            $sprite[0], $sprite[1],
            0, 0,
            $sprite[2], $sprite[3]
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
            )
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
}
