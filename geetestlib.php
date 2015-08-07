<?php
define('GT_API_SERVER', 'http://api.geetest.com');
define('GT_SDK_VERSION', 'wordpress_1.0');

class geetestlib{
  function __construct() {
    $this->challenge = "";
  }

  function register($pubkey) {
    $config = include dirname(__FILE__) . '/config.php';
    if ($config['http_options'] == '1') {
      $api = "https://api.geetest.com/";
    }else if ($config['http_options'] == '0') {
      $api = "http://api.geetest.com/";
    }
    $url = $api . 'register.php?gt=' . $pubkey;
    $this->challenge = $this->send_request($url);
    if (strlen($this->challenge) != 32) {
      return 0;
    }
    return 1;
  }
  private function send_request($url){
        if(function_exists('curl_exec')){
      $ch = curl_init();
      curl_setopt ($ch, CURLOPT_URL, $url);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
      $data = curl_exec($ch);
      curl_close($ch);
    }else{
      $opts = array(
          'http'=>array(
            'method'=>"GET",
            'timeout'=>2,
            ) 
          );
      $context = stream_context_create($opts);
      $data = file_get_contents($url, false, $context);
    }
    return $data;
  }

  /**
   * Gets the challenge HTML (javascript and non-javascript version).
   * This is called from the browser, and the resulting GeeTest HTML widget
   * is embedded within the HTML form it was called from.
   * @param string $pubkey A public key for GeeTest
   */
  function geetest_get_html($pubkey,$product="float") {
    if ($pubkey == null || $pubkey == '') {
      die ("To use GeeTest you must get an API key from <a href='http://www.geetest.com/'>http://www.geetest.com/</a>");
    }
    $params = array(
      "gt" => $pubkey,
      "challenge" => $this->challenge,
      "product" => $product,
    );
    return '<div id="geetest_unique_id"><script type="text/javascript" src="'.GT_API_SERVER.'/get.php?'.http_build_query($params).'"></script><div style="clear:both;"></div></div>';
  }

  /**
    * Calls an HTTP POST function to verify if the user's guess was correct
    * @param string $privkey
    * @param string $remoteip
    * @param string $challenge
    * @param string $response
    * @param array $extra_params an array of extra variables to post to the server
    * @return ReCaptchaResponse
    */
  function geetest_check_answer($privkey, $challenge, $validate, $seccode) {
      if ($privkey == null || $privkey == '') {
          die ("To use GeeTest you must get an API key from <a href='http://www.geetest.com/'>http://www.geetest.com/</a>");
      }

      return $this->geetest_validate($privkey, $challenge, $validate, $seccode);
  }

  function geetest_validate($privkey, $challenge, $validate, $seccode) {
      $apiserver = 'api.geetest.com';
      if (strlen($validate) > 0 && $validate == md5($privkey.'geetest'.$challenge)) {
          $query = http_build_query(array("seccode"=>$seccode,"sdk"=>GT_SDK_VERSION));
          $servervalidate = $this->_http_post($apiserver, '/validate.php', $query);
          if (strlen($servervalidate) > 0 && $servervalidate == md5($seccode)) {
              return TRUE;
          }
      }

      return FALSE;
  }

  function _http_post($host, $path, $data, $port = 80) {
      $http_request  = "POST $path HTTP/1.0\r\n";
      $http_request .= "Host: $host\r\n";
      $http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
      $http_request .= "Content-Length: " . strlen($data) . "\r\n";
      $http_request .= "\r\n";
      $http_request .= $data;

      $response = '';
      if (($fs = @fsockopen($host, $port, $errno, $errstr, 10)) == false) {
          die ('Could not open socket! ' . $errstr);
      }

      fwrite($fs, $http_request);

      while (!feof($fs))
          $response .= fgets($fs, 1160);
      fclose($fs);

      $response = explode("\r\n\r\n", $response, 2);
      return $response[1];
  }
}
?>