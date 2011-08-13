<?php

// Base on an algorithm by Javier Arevalo
// http://www.iguanademos.com/Jare/Articles.php?view=RectPlace

class RectanglePacker {
    public $width, $height;
    public $rectangles = array();

    private $anchors = array();

    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;

        $this->anchors[] = array(0, 0);
    }

    public function isEmpty() {
        return empty($this->rectangles);
    }

    public function occupiedSize() {
        $width = 0;
        $height = 0;

        foreach ($this->rectangles as $rectangle) {
            $width  = max($width , $rectangle[0] + $rectangle[2]);
            $height = max($height, $rectangle[1] + $rectangle[3]);
        }

        return array($width, $height);
    }

    public function tryInsert($width, $height, $data) {
        $anchor = $this->findGoodAnchor($width, $height);
        if ($anchor === null) {
            return false;
        }
        list($anchorX, $anchorY) = $anchor;

        $point = $this->getOptimalLocation($anchorX, $anchorY, $width, $height);
        list($pointX, $pointY) = $point;

        // If the rectangle covers the anchor point, remove it
        if (RectanglePacker::pointIntersectsRectangle($anchor, array($pointX, $pointY, $width, $height))) {
            array_splice($this->anchors, array_search($anchor, $this->anchors), 1);
        }

        // Create the new anchor points
        $this->anchors[] = array($pointX + $width, $pointY);
        $this->anchors[] = array($pointX, $pointY + $height);

        $this->rectangles[] = array($pointX, $pointY, $width, $height, 'data' => $data);
        return true;
    }

    private function getOptimalLocation($x, $y, $width, $height) {
        // Move the point as far left *or* up as possible
        $bestX = $x;
        while ($this->canInsertAt($bestX - 1, $y, $width, $height)) {
            --$bestX;
        }

        $bestY = $y;
        while ($this->canInsertAt($x, $bestY - 1, $width, $height)) {
            --$bestY;
        }

        if (($bestX - $x) > ($bestY - $y)) {
            // We managed to move x more
            return array($bestX, $y);
        } else {
            // We managed to move y more
            return array($x, $bestY);
        }
    }

    private function findGoodAnchor($width, $height) {
        foreach ($this->anchors as $anchor) {
            list($x, $y) = $anchor;

            if ($this->canInsertAt($x, $y, $width, $height)) {
                return $anchor;
            }
        }

        return null;
    }

    private function canInsertAt($x, $y, $width, $height) {
        // Check if we leave the bounding box's bounds
        if ($x < 0) return false;
        if ($y < 0) return false;
        if ($x + $width  > $this->width ) return false;
        if ($y + $height > $this->height) return false;

        // Check if we intersect any already-packed triangles
        $newRectangle = array($x, $y, $width, $height);

        foreach ($this->rectangles as $rectangle) {
            if (RectanglePacker::rectangleIntersectsRectangle($rectangle, $newRectangle)) {
                return false;
            }
        }

        return true;
    }

    private static function rectangleIntersectsRectangle($a, $b) {
        $leftA = $a[0];
        $leftB = $b[0];
        $topA = $a[1];
        $topB = $b[1];

        $rightA = $a[0] + $a[2];
        $rightB = $b[0] + $b[2];
        $bottomA = $a[1] + $a[3];
        $bottomB = $b[1] + $b[3];

        return (
            $leftA < $rightB &&
            $rightA > $leftB &&
            $topA < $bottomB &&
            $bottomA > $topB
        );
    }

    private static function pointIntersectsRectangle($a, $b) {
        if($a[0] < $b[0]) return false;
        if($a[1] < $b[1]) return false;
        if($a[0] >= $b[0] + $b[2]) return false;
        if($a[1] >= $b[1] + $b[3]) return false;
        return true;
    }
}
