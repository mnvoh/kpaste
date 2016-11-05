<?php
define('ROOT_PATH', __DIR__);
define('ROOT_NAME', basename(__DIR__));
define('TIMEZONE', 'Asia/Tehran');
//define a univeral captcha
$captcha = array(
    'name'      => 'captcha',
    'type'      => 'Zend\Form\Element\Captcha',
    'options'   => array(
        'captcha'   => array(
            'class'         => 'Image',
            'font'          => ROOT_NAME . "/images/fonts/captcha.otf",
            'fontSize'      => 32,
            'imgDir'        => ROOT_NAME . "/images/captcha",
            'imgUrl'        => '/images/captcha',
            'wordLen'       => 4,
            'timeout'       => 300,
            'dotNoiseLevel' => 0,
            'lineNoiseLevel'=> 0,
            'width'         => 120,
            'height'        => 50,
        ),
    ),
    'attributes'=> array(
        'class'     => 'captcha',
    )
);
define('CAPTCHA', serialize($captcha));

//the default number of rows per page
define('DEFAULT_ROWS_PER_PAGE', 20);
?>
