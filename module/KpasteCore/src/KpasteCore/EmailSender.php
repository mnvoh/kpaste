<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    EmailSender.php
 * @createdat    Jul 30, 2013 4:46:11 PM
 */

namespace KpasteCore;

class EmailSender 
{
    private $hostname;
    private $translator;
    private $lang;
    private $direction;
    private $textalign;
    public function __construct($hostname, $translator, $lang) 
    {
        $this->hostname = str_replace('http://','', $hostname);
        $this->translator = $translator;
        $this->lang = $lang;
        
        $directions = array(
            'en'     => 'ltr',
            'fa'     => 'rtl',
        );
        $textaligns = array(
            'en'     => 'left',
            'fa'     => 'right',
        );
        
        $this->direction = $directions[$lang];
        $this->direction = ($this->direction) ? $this->direction : 'ltr';
        $this->textalign = $textaligns[$lang];
        $this->textalign = ($this->textalign) ? $this->textalign : 'left';
    }
    
    public function sendPasswordChangeEmail($to, $url, $name = null)
    {
        $link = 'http://' . $this->hostname . $url;
        $message = sprintf(
            $this->translator->translate(
                'Hi %s <br>We have recieved a request to change the account associated with this ' . 
                'email address. If you haven\'t made such request just ignore this email. Otherwise ' .
                'follow this link <br>' .
                '%s <br>' . 
                'Or copy and paste this ' . 
                '%s<br>' .
                'into your browser to complete the process.'
            ),
            $name, 
            "<a class='button' href='$link'>Continue Changing Your Password</a>",
            $link
        );
        $this->sendMessage(array($to), false, 
                $this->translator->translate('Confirm Password Change Request'), $message);
    }
    
    public function sendEmailVerificationEmail($to, $url, $name = null)
    {
        $link = 'http://' . $this->hostname . $url;
        $verifyEmailMessage = sprintf(
            $this->translator->translate(
                'Hi %s <br>Your account has been successfully created. In order to ' . 
                'use all functionallities you have to activate your account by verifying ' .
                'your email address. In order to do so, please follow the below link: <br>' .
                '%s <br>' . 
                'Or copy and paste the following in your browser: ' . 
                '%s'
            ),
            $name, 
            "<a class='button' href='$link'>Verify Email Address</a>",
            $link
        );
        $this->sendMessage(array($to), false, 
                $this->translator->translate('Verify Your Email Address'),$verifyEmailMessage);
    }
    
    public function sendInvoice($description, $amount, $date, $to, $paid = false)
    {
        $status = "<span style='color: #990000;'>" . $this->translator->translate('Unpaid') . "</span>";
        if($paid)
            $status = "<span style='color: #009900;'>" . $this->translator->translate('Paid') . "</span>";
        $date = \KpasteCore\KDateTime\KDateTime::PrefDate($date);
        $title = $this->translator->translate("Your invoice has been created");
        if($paid)
            $title = $this->translator->translate("Your invoice has been paid");
        $message = $this->translator->translate("Hi<br />$title:<br />");
        
        
        $message .= '<table><tr>';
        $message .= '<th>' . $this->translator->translate('Title') . '</th>';
        $message .= '<th>' . $this->translator->translate('Amount') . '</th>';
        $message .= '</tr>';
        $message .= "<tr><td>$description</td><td>$amount</td></tr>";
        $message .= "<tr><td>" . $this->translator->translate('Extra Tax') . "</td><td>0</td></tr>";
        $message .= "<tr><td>" . $this->translator->translate("Gross") . "</td><td>$amount</td></tr>";
        $message .= "<tr><td>" . $this->translator->translate("Status") . "</td><td>$status</td></tr>";
        $message .= "<tr><td>" . $this->translator->translate("Date") . "</td><td>$date</td></tr>";
        $message .= "</table>";
        $this->sendMessage(array($to), false, $title,$message);
    }
    
    public function sendMessage($to, $from, $title, $message)
    {
        if(preg_match('/\.loc\.com/', $this->hostname))
            return;
        
        if(!$from)
        {
            $from = "no-reply@" . $this->hostname;
        }
        
        $message .= "<br />" . $this->translator->translate('Regards, kPaste Team');
        
        $email = file_get_contents(ROOT_PATH . '/email.phtml');
        $email = preg_replace('/%TITLE%/', $title, $email);
        $email = preg_replace('/%DIRECTION%/', $this->direction, $email);
        $email = preg_replace('/%TEXTALIGN%/', $this->textalign, $email);
        $email = preg_replace('/%MESSAGE%/', $message, $email);
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mb_internal_encoding('UTF-8');
        
        if(is_array($to))
        {
            foreach($to as $t)
            {
                mb_send_mail($t, $title, $email, $headers);
            }
        }
        else
        {
            mb_send_mail($to, $title, $email, $headers);
        }
    }
}

?>
