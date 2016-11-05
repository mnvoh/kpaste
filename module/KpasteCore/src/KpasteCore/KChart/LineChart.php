<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    LineChart.php
 * @createdat    Aug 29, 2013 4:13:01 PM
 */
namespace KpasteCore\KChart;

class LineChart 
{
    private $multiSample;
    private $width;
    private $height;
    private $chartWidth;
    private $chartHeight;
    private $chartUpperBound;
    private $range;
    private $unitWidth;
    private $unitHeight;
    private $rotateTextOnX;
    private $yscale;
    private $points;
    private $yPoints;
    private $font;
    private $lineWidth;
    //-----------
    private $smallMargin; 
    private $mediumMargin;
    private $bigMargin;           
    private $fontSize;
    private $textRotation;
    private $circlesRadius;
    //-----------
    private $backgroundColor;
    private $chartBorderColor;
    private $chartGridColor;
    private $circleColor;
    private $chartLineColor;
    private $textColor;
    private $hintColor;
    private $hintBackgroundColor;
    
    public function __construct($width, $height, $kPoints, $multisample = 4)
    {
        $this->multiSample = $multisample;
        $this->width = $width * $multisample;
        $this->height = $height * $multisample;
        $this->points = $kPoints;
        
        $this->font = ROOT_PATH . '/images/fonts/kPasteFont.ttf';
        
        $this->smallMargin = (int)($this->height / 100);
        $this->mediumMargin = (int)($this->height / 30);
        $this->bigMargin = (int)($this->height / 15);
        $this->fontSize = (int)($this->height / 30);
        $this->unitHeight = (int)($this->height / 12);
        $this->textRotation = -45;
        $this->circlesRadius = (int)($this->height / 18);
        $this->lineWidth = (int)($this->height / 50);
        
        $this->backgroundColor = array(255,255,255);
        $this->chartBorderColor = array(0,0,0);
        $this->chartGridColor = array(0xaa, 0xaa, 0xaa);
        $this->circleColor = array(0x8, 0x7f, 0xe6);
        $this->chartLineColor = array(0xe6, 0xaf, 0x08);
        $this->textColor = array(0,0,0);
        $this->hintColor = array(255,255,0);
        $this->hintBackgroundColor = array(0,0,0);
        
        $this->unitDimensions();
    }
    
    private function unitDimensions()
    {
        if(is_array($this->points))
        {
            $points = KPoint::ArrayPointsToArray($this->points);

            //find the max text width on the y axis
            $maxLength = 0;
            foreach($points['ys'] as $y)
            {
                if(strlen($y) > $maxLength)
                    $maxLength = strlen($y);
            }
            $test = "N";
            for($i = 1; $i < $maxLength; $i++)
            {
                $test .= "N";
            }
            
            $maxTextWidthY = $this->getTextWidth($this->fontSize, $test, 0);
        
            $this->chartWidth = $this->width - $maxTextWidthY - $this->bigMargin;
                        
            $this->unitWidth = (int)($this->chartWidth / (count($points['xs']) + 1));
            
            //find the max text height on the x axis
            $maxLength = 0;
            foreach($points['xs'] as $x)
            {
                if(strlen($x) > $maxLength)
                    $maxLength = strlen($x);
            }
            $test = "N";
            for($i = 1; $i < $maxLength; $i++)
            {
                $test .= "N";
            }
            
            $maxTextWidthX = $this->getTextWidth($this->fontSize, $test, 0);
            
            if($maxTextWidthX > $this->unitWidth)
                $this->rotateTextOnX = true;
            else
                $this->rotateTextOnX = false;
            
            $maxTextHeightX = $this->getTextHeight($this->fontSize, $test, 
                    ($this->rotateTextOnX) ? $this->textRotation : 0);
            
            $this->chartHeight = $this->height - $maxTextHeightX - $this->bigMargin;
            
            //calculate the unit dimensions
            //but first, determine what are the indecies on the y axis
            $this->determineYData();
            
            //check to see if we have to scale down the y axis
            $yRange = max($this->yPoints) - min($this->yPoints) < 0 ? min($this->yPoints) : 0;
            
            if($this->fontSize > $this->unitHeight)
            {
                $newYPoints = array();
                for($i = 0; $i < count($this->yPoints); $i += (int)($this->yscale))
                    $newYPoints[] = $this->yPoints[$i];
                $this->yPoints = $newYPoints;
            }
        }
    }
    
    private function determineYData()
    {
        if(is_array($this->points))
        {
            $points = KPoint::ArrayPointsToArray($this->points);
            $ys = $points['ys'];
            
            $min = min($ys) < 0 ? min($ys) : 0;
            $max = max($ys);
            $maxMagnitude = strlen($max) - 1;
            $tmax = pow(10, $maxMagnitude);
            for($i = 1; $i <= 10; $i++)
            {
                if($tmax * $i >= $max)
                {
                    $max = $tmax * $i;
                    break;
                }
            }
            
            $range = $max - $min;
            $chunks = ceil($this->chartHeight / $this->unitHeight) - 1;
            $inc = ceil($range / ($chunks - 1));
            
            for($i = 0; $i < $chunks; $i++)
                $this->yPoints[$i] = $min + $i * $inc;
            
            $this->chartUpperBound = ($chunks - 1) * $this->unitHeight;
            $this->range = $this->yPoints[count($this->yPoints) - 1] - $this->yPoints[0];
        }
    }
    
    public function drawChart($filename = NULL)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
                
        //allocate colors;
        $backgroundColor = imagecolorallocate($image, $this->backgroundColor[0], 
                $this->backgroundColor[1], $this->backgroundColor[2]);
        $chartBorderColor = imagecolorallocate($image, $this->chartBorderColor[0], 
                $this->chartBorderColor[1], $this->chartBorderColor[2]);
        $chartGridColor = imagecolorallocate($image, $this->chartGridColor[0], 
                $this->chartGridColor[1], $this->chartGridColor[2]);
        $circleColor = imagecolorallocate($image, $this->circleColor[0], 
                $this->circleColor[1], $this->circleColor[2]);
        $chartLineColor = imagecolorallocate($image, $this->chartLineColor[0], 
                $this->chartLineColor[1], $this->chartLineColor[2]);
        $textColor = imagecolorallocate($image, $this->textColor[0], 
                $this->textColor[1], $this->textColor[2]);
        $hintColor = imagecolorallocate($image, $this->hintColor[0], 
                $this->hintColor[1], $this->hintColor[2]);
        $hintBackgroundColor = imagecolorallocate($image, $this->hintBackgroundColor[0], 
                $this->hintBackgroundColor[1], $this->hintBackgroundColor[2]);
        
        imagefill($image, 0, 0, $backgroundColor);
        
        //Draw the grid
        $chunksX = floor($this->chartWidth / $this->unitWidth) - 1;
        $chunksY = floor($this->chartHeight / $this->unitHeight);
        
        for($i = 0; $i < $chunksX; $i++)
        {
            $c1 = $this->getCordinates($i, $chunksY);
            $c2 = $this->getCordinates($i, 0);
            imagedashedline($image, $c1['x'], $c1['y'], $c2['x'], $c2['y'], $chartGridColor);
        }
        
        for($i = 1; $i < $chunksY; $i++)
        {
            $c1 = $this->getCordinates(0, $i);
            $c2 = $this->getCordinates($chunksX, $i);
            imagedashedline($image, $c1['x'] - $this->unitWidth / 2, $c1['y'], 
                    $this->width, $c2['y'], $chartGridColor);
        }
        
        imageline($image, $this->width - $this->chartWidth, 0, $this->width - $this->chartWidth, 
                $this->chartHeight, $chartBorderColor);
        imageline($image, $this->width - $this->chartWidth, $this->chartHeight, $this->width, 
                $this->chartHeight, $chartBorderColor);
        
        //Draw the values on the y axis
        $i = 0;
        foreach($this->yPoints as $yPoint)
        {
            $x = $this->width - $this->chartWidth - 
                    $this->getTextWidth($this->fontSize, $yPoint, 0) - $this->smallMargin;
            $y = $this->chartHeight - ($i * $this->unitHeight);
            imagettftext($image, $this->fontSize, 0, $x, $y, $textColor, $this->font, $yPoint);
            $i++;
        }
        
        //Draw the values on the x axis
        $i = 0;
        $y = $this->chartHeight + $this->mediumMargin;
        
        foreach($this->points as $point)
        {
            $width = $this->getTextWidth($this->fontSize, $point->x, $this->rotateTextOnX ? $this->textRotation : 0);
            $height = $this->getTextHeight($this->fontSize, $point->x, $this->rotateTextOnX ? $this->textRotation : 0);
            $x = ($this->width - $this->chartWidth) + ($this->unitWidth * $i) + $this->unitWidth / 2;
            imagettftext($image, $this->fontSize, $this->rotateTextOnX ? $this->textRotation : 0,
                    $x, $y + $this->fontSize, $textColor, $this->font, $point->x);
            $i++;
        }
        
        
        //draw the points and lines
        $i = 0;
        imagesetthickness($image, $this->lineWidth);
        $prevx = null;
        $prevy = null;
        foreach($this->points as $point)
        {
            $y = $this->chartHeight - ($this->chartUpperBound  / $this->range) * $point->y;
            $x = $this->getCordinates($i, 0);
            $x = $x['x'];
            
            if($prevx && $prevy)
            {
                imageline($image, $prevx, $prevy, $x, $y, $chartLineColor);
            }
            
            $i++;
            $prevx = $x;
            $prevy = $y;
        }
        
        $i = 0;
        foreach($this->points as $point)
        {
            $y = $this->chartHeight - ($this->chartUpperBound  / $this->range) * $point->y;
            $x = $this->getCordinates($i, 0);
            $x = $x['x'];
            imagefilledellipse($image, $x, $y, $this->circlesRadius, $this->circlesRadius, $circleColor);
            
            
            $width = $this->getTextWidth($this->fontSize, $point->y, 0);
            $height = $this->getTextHeight($this->fontSize, $point->y, 0);
            imagefilledrectangle($image, $x - $width / 2 - $this->smallMargin, $y - $height - $this->mediumMargin, 
                    $x + $width / 2 + $this->smallMargin, $y - $this->mediumMargin + $this->smallMargin, $hintBackgroundColor);
            imagettftext($image, $this->fontSize, 0, $x - $width / 2, $y - $height,
                    $hintColor, $this->font, $point->y);
            
            $i++;
        }

        $output = imagecreatetruecolor($this->width / $this->multiSample, $this->height / $this->multiSample);
        imagecopyresampled($output, $image, 0, 0, 0, 0, $this->width / $this->multiSample,
                $this->height / $this->multiSample, $this->width, $this->height);
        
        if($filename)
        {
            imagepng($output, $filename);
        }
        else
        {
            header('Content-Type: image/png');
            imagepng($output);
        }
        imagedestroy($image);
        imagedestroy($output);
    }
    
    private function getTextWidth($fontSize, $text, $degree)
    {
        $box = imagettfbbox($fontSize, $degree, $this->font, $text);
        return abs($box[4] - $box[0]);
    }
    
    private function getTextHeight($fontSize, $text, $degree)
    {
        $box = imagettfbbox($fontSize, $degree, $this->font, $text);
        return abs($box[5] - $box[1]);
    }
    
    private function getCordinates($indexX, $indexY)
    {
        $x = $this->width - $this->chartWidth + ($indexX * $this->unitWidth) + ($this->unitWidth / 2);
        $y = $this->chartHeight - ($indexY * $this->unitHeight);
        return(array('x' => $x, 'y' => $y));
    }
}

?>
