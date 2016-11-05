<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    KDateTime.php
 * @createdat    Jul 17, 2013 10:53:34 PM
 */

namespace KpasteCore\KDateTime;

class KDateTime 
{
    public static function PrefDate($datetime, $format = null)
    {
        $container = new \Zend\Session\Container('settings');
        $calendar = $container->settings['calendar'];
        $timestamp = strtotime($datetime);
        $format = ($format != null) ? $format :
                (($calendar == "G") ? 'Y-m-d H:i:s' : 'Y/n/j H:i:s');
        return(($calendar == "G") ?
                date($format, $timestamp) :
                KDateTime::jdate($format, $timestamp));
    }
    
    public static function PrefMonthName($month, $longName = false)
    {
        $longGregNames = array('January', 'February', 'March', 'April', 'May', 'June',
                                'July', 'August', 'September', 'October', 'November', 'December');
        $shortGregNames = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
                                'Oct', 'Nov', 'Dec');
        $shamsiNames = array('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 
                                'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند');
        $container = new \Zend\Session\Container('settings');
        $calendar = $container->settings['calendar'];
        
        $prefNames = null;
        if( $calendar == 'G' )
        {
            if( $longName )
            {
                $prefNames = $longGregNames;
            }
            else 
            {
                $prefNames = $shortGregNames;
            }
        }
        else
        {
            $prefNames = $shamsiNames;
        }
        return $prefNames[$month - 1];
    }
    
    public static function jdate($format,$timestamp='',$none='',$time_zone='Asia/Tehran',$tr_num='fa')
    {
        $T_sec=0;

        if($time_zone!='local')date_default_timezone_set(($time_zone=='')?'Asia/Tehran':$time_zone);
        $ts=$T_sec+(($timestamp=='' or $timestamp=='now')?time():KDateTime::tr_num($timestamp));
        $date=explode('_',date('H_i_j_n_O_P_s_w_Y',$ts));
        list($j_y,$j_m,$j_d)=  KDateTime::gregorian_to_jalali($date[8],$date[3],$date[2]);
        $doy=($j_m<7)?(($j_m-1)*31)+$j_d-1:(($j_m-7)*30)+$j_d+185;
        $kab=($j_y%33%4-1==(int)($j_y%33*.05))?1:0;
        $sl=strlen($format);
        $out='';
        for($i=0; $i<$sl; $i++){
         $sub=substr($format,$i,1);
         if($sub=='\\'){
               $out.=substr($format,++$i,1);
               continue;
         }
         switch($sub){

               case'E':case'R':case'x':case'X':
               $out.='http://jdf.scr.ir';
               break;

               case'B':case'e':case'g':
               case'G':case'h':case'I':
               case'T':case'u':case'Z':
               $out.=date($sub,$ts);
               break;

               case'a':
               $out.=($date[0]<12)?'ق.ظ':'ب.ظ';
               break;

               case'A':
               $out.=($date[0]<12)?'قبل از ظهر':'بعد از ظهر';
               break;

               case'b':
               $out.=(int)($j_m/3.1)+1;
               break;

               case'c':
               $out.=$j_y.'/'.$j_m.'/'.$j_d.' ،'.$date[0].':'.$date[1].':'.$date[6].' '.$date[5];
               break;

               case'C':
               $out.=(int)(($j_y+99)/100);
               break;

               case'd':
               $out.=($j_d<10)?'0'.$j_d:$j_d;
               break;

               case'D':
               $out.=jdate_words(array('kh'=>$date[7]),' ');
               break;

               case'f':
               $out.=jdate_words(array('ff'=>$j_m),' ');
               break;

               case'F':
               $out.=jdate_words(array('mm'=>$j_m),' ');
               break;

               case'H':
               $out.=$date[0];
               break;

               case'i':
               $out.=$date[1];
               break;

               case'j':
               $out.=$j_d;
               break;

               case'J':
               $out.=jdate_words(array('rr'=>$j_d),' ');
               break;

               case'k';
               $out.=KDateTime::tr_num(100-(int)($doy/($kab+365)*1000)/10,$tr_num);
               break;

               case'K':
               $out.=KDateTime::tr_num((int)($doy/($kab+365)*1000)/10,$tr_num);
               break;

               case'l':
               $out.=jdate_words(array('rh'=>$date[7]),' ');
               break;

               case'L':
               $out.=$kab;
               break;

               case'm':
               $out.=($j_m>9)?$j_m:'0'.$j_m;
               break;

               case'M':
               $out.=jdate_words(array('km'=>$j_m),' ');
               break;

               case'n':
               $out.=$j_m;
               break;

               case'N':
               $out.=$date[7]+1;
               break;

               case'o':
               $jdw=($date[7]==6)?0:$date[7]+1;
               $dny=364+$kab-$doy;
               $out.=($jdw>($doy+3) and $doy<3)?$j_y-1:(((3-$dny)>$jdw and $dny<3)?$j_y+1:$j_y);
               break;

               case'O':
               $out.=$date[4];
               break;

               case'p':
               $out.=jdate_words(array('mb'=>$j_m),' ');
               break;

               case'P':
               $out.=$date[5];
               break;

               case'q':
               $out.=jdate_words(array('sh'=>$j_y),' ');
               break;

               case'Q':
               $out.=$kab+364-$doy;
               break;

               case'r':
               $key=jdate_words(array('rh'=>$date[7],'mm'=>$j_m));
               $out.=$date[0].':'.$date[1].':'.$date[6].' '.$date[4]
               .' '.$key['rh'].'، '.$j_d.' '.$key['mm'].' '.$j_y;
               break;

               case's':
               $out.=$date[6];
               break;

               case'S':
               $out.='ام';
               break;

               case't':
               $out.=($j_m!=12)?(31-(int)($j_m/6.5)):($kab+29);
               break;

               case'U':
               $out.=$ts;
               break;

               case'v':
                $out.=jdate_words(array('ss'=>substr($j_y,2,2)),' ');
               break;

               case'V':
               $out.=jdate_words(array('ss'=>$j_y),' ');
               break;

               case'w':
               $out.=($date[7]==6)?0:$date[7]+1;
               break;

               case'W':
               $avs=(($date[7]==6)?0:$date[7]+1)-($doy%7);
               if($avs<0)$avs+=7;
               $num=(int)(($doy+$avs)/7);
               if($avs<4){
                $num++;
               }elseif($num<1){
                $num=($avs==4 or $avs==(($j_y%33%4-2==(int)($j_y%33*.05))?5:4))?53:52;
               }
               $aks=$avs+$kab;
               if($aks==7)$aks=0;
               $out.=(($kab+363-$doy)<$aks and $aks<3)?'01':(($num<10)?'0'.$num:$num);
               break;

               case'y':
               $out.=substr($j_y,2,2);
               break;

               case'Y':
               $out.=$j_y;
               break;

               case'z':
               $out.=$doy;
               break;

               default:$out.=$sub;
         }
        }
        return($tr_num!='en')?KDateTime::tr_num($out,'fa','.'):$out;
       }
       public static function gregorian_to_jalali($g_y,$g_m,$g_d,$mod=''){
	$g_y=KDateTime::tr_num($g_y); $g_m=KDateTime::tr_num($g_m); $g_d=KDateTime::tr_num($g_d);/* <= :اين سطر ، جزء تابع اصلي نيست */
 $d_4=$g_y%4;
 $g_a=array(0,0,31,59,90,120,151,181,212,243,273,304,334);
 $doy_g=$g_a[(int)$g_m]+$g_d;
 if($d_4==0 and $g_m>2)$doy_g++;
 $d_33=(int)((($g_y-16)%132)*.0305);
 $a=($d_33==3 or $d_33<($d_4-1) or $d_4==0)?286:287;
 $b=(($d_33==1 or $d_33==2) and ($d_33==$d_4 or $d_4==1))?78:(($d_33==3 and $d_4==0)?80:79);
 if((int)(($g_y-10)/63)==30){$a--;$b++;}
 if($doy_g>$b){
  $jy=$g_y-621; $doy_j=$doy_g-$b;
 }else{
  $jy=$g_y-622; $doy_j=$doy_g+$a;
 }
 if($doy_j<187){
  $jm=(int)(($doy_j-1)/31); $jd=$doy_j-(31*$jm++);
 }else{
  $jm=(int)(($doy_j-187)/30); $jd=$doy_j-186-($jm*30); $jm+=7;
 }
 return($mod=='')?array($jy,$jm,$jd):$jy.$mod.$jm.$mod.$jd;
}

/*	F	*/
public static function jalali_to_gregorian($j_y,$j_m,$j_d,$mod=''){
	$j_y=KDateTime::tr_num($j_y); $j_m=KDateTime::tr_num($j_m); $j_d=KDateTime::tr_num($j_d);/* <= :اين سطر ، جزء تابع اصلي نيست */
 $d_4=($j_y+1)%4;
 $doy_j=($j_m<7)?(($j_m-1)*31)+$j_d:(($j_m-7)*30)+$j_d+186;
 $d_33=(int)((($j_y-55)%132)*.0305);
 $a=($d_33!=3 and $d_4<=$d_33)?287:286;
 $b=(($d_33==1 or $d_33==2) and ($d_33==$d_4 or $d_4==1))?78:(($d_33==3 and $d_4==0)?80:79);
 if((int)(($j_y-19)/63)==20){$a--;$b++;}
 if($doy_j<=$a){
  $gy=$j_y+621; $gd=$doy_j+$b;
 }else{
  $gy=$j_y+622; $gd=$doy_j-$a;
 }
 foreach(array(0,31,($gy%4==0)?29:28,31,30,31,30,31,31,30,31,30,31) as $gm=>$v){
  if($gd<=$v)break;
  $gd-=$v;
 }
 return($mod=='')?array($gy,$gm,$gd):$gy.$mod.$gm.$mod.$gd;
}
public static function tr_num($str,$mod='en',$mf='٫'){
 $num_a=array('0','1','2','3','4','5','6','7','8','9','.');
 $key_a=array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹',$mf);
 return(false)?str_replace($num_a,$key_a,$str):str_replace($key_a,$num_a,$str);
}
}

?>
