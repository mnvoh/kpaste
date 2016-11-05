<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Utilities.php
 * @createdat    Jul 24, 2013 5:04:59 PM
 */

namespace KpasteCore;

class Utilities 
{
    public static function getBannerSize($bannerType)
    {
        switch($bannerType)
        {
            case 'square_button_ltr':
            case 'square_button_b':
                return array('width' => 125, 'height' => 125);
            case 'vertical_banner':
                return array('width' => 120, 'height' => 240);
            case 'leaderboard_t':
            case 'leaderboard_b':
                return array('width' => 728, 'height' => 90);
            default:
                return null;
        }
    }
    
    public static function getFlashMarkup($url, $size) 
    {
        return <<< FLASH
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="{$size['width']}" height="{$size['height']}">
    <param name="movie" value="$url" />
    <param name="quality" value="high" />
    <param name="play" value="true" />
    <param name="loop" value="true" />
    <param name="wmode" value="window" />
    <param name="scale" value="showall" />
    <param name="menu" value="true" />
    <param name="devicefont" value="false" />
    <param name="salign" value="" />
    <param name="allowScriptAccess" value="never" />
    <!--[if !IE]>-->
    <object type="application/x-shockwave-flash" data="$url" width="{$size['width']}" height="{$size['height']}">
        <param name="movie" value="$url" />
        <param name="quality" value="high" />
        <param name="play" value="true" />
        <param name="loop" value="true" />
        <param name="wmode" value="window" />
        <param name="scale" value="showall" />
        <param name="menu" value="true" />
        <param name="devicefont" value="false" />
        <param name="salign" value="" />
        <param name="allowScriptAccess" value="never" />
    <!--<![endif]-->
        <a href="http://www.adobe.com/go/getflash">
                <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash Player" />
        </a>
    <!--[if !IE]>-->
    </object>
    <!--<![endif]-->
</object>
FLASH;
    }
    
    public static function getMime($filename)
    {
        $ext = end(explode('.', $filename));
        switch($ext)
        {
            case 'swf':
                return 'application/x-shockwave-flash';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'gif':
                return 'image/gif';
            case 'png':
                return 'image/png';
            case 'bmp':
                return 'image/bmp';
            case 'zip':
                return 'application/zip';
            default:
                return 'application/octet-stream';
        }
    }
}

?>
