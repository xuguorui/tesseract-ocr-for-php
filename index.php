<?php
require 'TesseractOCR.php';
function weizhang($car_code, $fdjh)
{
  $shanghui = mb_substr($car_code, 0, 1, 'utf-8');
  $pre = array(
    '冀' => 'he',
    '云' => 'yn'
  );
  $url_pre = $pre[$shanghui];
  $headers = array(
    'Host: '.$url_pre.'.122.gov.cn',
    'Origin: http://'.$url_pre.'.122.gov.cn',
    'Referer: http://'.$url_pre.'.122.gov.cn/views/inquiry.html?q=j',
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.75 Safari/537.36 QQBrowser/4.1.4132.400'
  );
  //初始化变量
  $cookie_file = 'cookie.txt';
  $login_url = "http://$url_pre.122.gov.cn/views/inquiry.html?q=j";
  $post_url = "http://$url_pre.122.gov.cn/m/publicquery/vio";
  $verify_code_url = "http://$url_pre.122.gov.cn/captcha?nocache=".time();
  $curl = curl_init();
  $timeout = 5;
  curl_setopt($curl, CURLOPT_URL, $login_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file); //获取COOKIE并存储
  $contents = curl_exec($curl);
  curl_close($curl);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $verify_code_url);
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $img = curl_exec($curl);
  curl_close($curl);
  $fp = fopen("verifyCode.jpg", "w");
  fwrite($fp, $img);
  fclose($fp);
  $code = (new TesseractOCR('verifyCode.jpg'))->psm(7)->run();
 $code = explode("\n", $code);
 $code = $code[1];
  echo $code.PHP_EOL;
  if (strlen($code) != 4) {
    return json_encode(array('code'=>500));
  }
  $data = array(
    'hpzl'=>'02',
    'hphm1b' => substr($car_code, -6),
    'hphm' => $car_code,
    'fdjh' => $fdjh,
    'captcha' => $code,
    'qm' => 'wf',
    'page' => 1
  );
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $post_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
  $result = curl_exec($curl);
  curl_close($curl);
  //unlink($cookie_file);
  //unlink('verifyCode.jpg');
  return $result;
}
$count = 0;
// 车牌号
$car_code = '冀Dxxxxx';
// 发动机后6位
$fdjh = 'xxxxxx';
while (true) {
  $count++;
  if ($count>50) {
    exit('查询失败');
  }
  $res = weizhang($car_code, $fdjh);
  $info = json_decode($res, true);
  echo $res.PHP_EOL;
  if ($info['code'] == 200) {
    echo '车牌号: '. $car_code.PHP_EOL;
    echo '未处理违章数: '.$info['data']['content']['zs'];
    exit();
  }
}