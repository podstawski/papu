<?php
/**
 * @author Piotr Podstawski <piotr.podstawski@gammanet.pl>
 */

 
class Image
{
    private $source=null;
    private $src_w,$src_h,$ext;
    
    public function __construct($img)
    {
        if (file_exists($img) && strlen($img) > 0) {
            $pinfo = pathinfo($img);
            $this->ext = strtolower($pinfo['extension']);

            switch ($this->ext) {
                case 'jpg':
                case 'jpeg':
                    $this->source = imagecreatefromjpeg($img);
                    break;

                case 'png':
                    $this->source = imagecreatefrompng($img);
                    break;

                case 'gif':
                    $this->source = imagecreatefromgif($img);
                    break;
                
                default:
                    return false;
            }
            
            
            list ($this->src_w, $this->src_h) = getimagesize($img);
           
            
            
        }
         
        
    }
    
    
    
    public function min($dst, $dst_w, $dst_h, $scale = false, $crop = false)
    {
        
        
        
        if (!$this->source) return false;
        
        $ext=end(explode('.',strtolower($dst)));
        
        
        if ($dst_w && !$dst_h) $dst_h = round(($dst_w*$this->src_h)/$this->src_w);
        if ($dst_h && !$dst_w) $dst_w = round(($dst_h*$this->src_w)/$this->src_h);
        
        
    
        if ($crop) {
    
            $t = $this->image_crop_calc($this->src_w, $this->src_h, $dst_w, $dst_h);
    
            $thumb = $this->createimage($ext, $dst_w, $dst_h);
            
            imagecopyresampled($thumb, $this->source, 0, 0, $t['x'], $t['y'], $dst_w, $dst_h, $t['w'], $t['h']);
        } else {
            if ($scale) {
                $t = $this->image_scale_calc($this->src_w, $this->src_h, $dst_w, $dst_h);
                $thumb = $this->createimage($ext, $t['x'], $t['y']);
                imagecopyresampled($thumb, $this->source, 0, 0, 0, 0, $t['x'], $t['y'], $this->src_w, $this->src_h);
            } else {
                $thumb = $this->createimage($ext, $dst_w, $dst_h);
                imagecopyresampled($thumb, $this->source, 0, 0, 0, 0, $dst_w, $dst_h, $this->src_w, $this->src_h);
            }
        }
        
    
    
    
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumb, $dst, 80);
                break;
    
            case 'png':
                imagepng($thumb, $dst);
                break;
    
            case 'gif':
                imagegif($thumb, $dst);
                break;
        }
        
        return $dst;        
        
    }
    
    
    
    
    private function image_scale_calc($src_width, $src_height, $dst_width, $dst_height)
    {
        $ratio = $src_width / $src_height;

        if ($dst_width / $dst_height > $ratio) {
            $dst_width = $dst_height * $ratio;
        } else {
            $dst_height = $dst_width / $ratio;
        }

        return array('x' => round($dst_width), 'y' => round($dst_height));
    }

    private function image_crop_calc($src_width, $src_height, $dst_width, $dst_height)
    {

        if ($src_height / $src_width > $dst_height / $dst_width) {
            $ret['x'] = 0;
            $ret['y'] = round(($src_height - ($dst_height * $src_width) / $dst_width) / 2);
            $ret['w'] = $src_width;
            $ret['h'] = round(($src_width * $dst_height) / $dst_width);

        } else {
            $ret['x'] = round(($src_width - ($dst_width * $src_height) / $dst_height) / 2);
            $ret['y'] = 0;
            $ret['w'] = round(($src_height * $dst_width) / $dst_height);
            $ret['h'] = $src_height;
        }

        return $ret;
    }
    
    private function createimage($ext,$w,$h)
    {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    return imagecreatetruecolor($w,$h);

                case 'png':
                case 'gif':
                    $newImg=imagecreatetruecolor($w,$h);                   
                    imagealphablending($newImg, false);
                    imagesavealpha($newImg,true);
                    $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                    imagefilledrectangle($newImg, 0, 0, $w, $h, $transparent); 
                    return $newImg;
                    
                    //return imagecreate($w,$h);
            }        
    }
    
    
    public function w()
    {
        return $this->src_w;
    }
    
    public function h()
    {
        return $this->src_h;
    }
    
}
   