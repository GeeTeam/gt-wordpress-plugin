<?php 
require_once dirname(__FILE__) . '/geetestlib.php';
$config = include dirname(__FILE__) . '/config.php';
$GtSdk = new geetestLib();
session_start();
$return = $GtSdk->register($config['public_key']);
if ($return) {
    $_SESSION['gtserver'] = 1;
    if ($config['challenge'] == 1) {
        $challenge = md5($GtSdk->challenge.$config['private_key']);
        $result = array(
                'success' => 1,
                'gt' => $config['public_key'],
                'challenge' => $challenge
            );
    }else if ($config['challenge'] == 0){
        $result = array(
                'success' => 1,
                'gt' => $config['public_key'],
                'challenge' => $GtSdk->challenge
            );
    }
    echo json_encode($result);
}else{
    $_SESSION['gtserver'] = 0;
    $rnd1 = md5(rand(0,100));
    $rnd2 = md5(rand(0,100));
    $challenge = $rnd1 . substr($rnd2,0,2);
    $result = array(
            'success' => 0,
            'gt' => $config['public_key'],
            'challenge' => $challenge
        );
    $_SESSION['challenge'] = $result['challenge'];
    echo json_encode($result);
}
        

 ?>