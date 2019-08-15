<?php
$accessToken = getenv('LINE_CHANNEL_ACCESS_TOKEN');

//ユーザーからのメッセージ取得
$json_string = file_get_contents('php://input');
$jsonObj = json_decode($json_string);

$type = $jsonObj->{"events"}[0]->{"message"}->{"type"};
//メッセージ取得
$text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
//メッセージID取得
$messageId = $jsonObj->{"events"}[0]->{"message"}->{"id"};
//ユーザーID取得
$userId = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
//ReplyToken取得
$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};
//massage0
$massage0 = '';
//massage1(BOT)
$massage1 = '<br>[word_balloon id="2" position="R" size="S" balloon="line" name_position="under_avatar" radius="true" avatar_border="false" avatar_shadow="false"balloon_shadow="true" avatar_hide="false" font_size="12"]';
//massage2(User)
$massage2 = '[word_balloon id="1" position="L" size="S" balloon="talk" name_position="under_avatar" radius="true" avatar_border="false" avatar_shadow="false" balloon_shadow="true" avatar_hide="false" font_size="12"]';
//massageend()
$massageend = '[/word_balloon]';
$massageshop = '';
$massagecat = '';

//Sendgrid-1
require __DIR__ . '/../vendor/autoload.php';
$sendgrid = new SendGrid(getenv('SENDGRID_USERNAME'), getenv('SENDGRID_PASSWORD'));
$email    = new SendGrid\Email();
$email->addTo('hele483cobi@post.wordpress.com')
	  ->setFrom('linebot@azo.jp');

//メッセージ以外のときは何も返さず終了
if($type != "text" && $type != "image"){
	exit;
}

//メッセージから店舗名を取得
if (strpos($text,'ハッピーサニーショップ') !== false) {
	$massagecat = '316906';
	$massageshop = 'ハッピーサニーショップ';
} else if (strpos($text,'サニープライズ') !== false) {
	$massagecat = '316908';
	$massageshop = 'サニープライズ';
}

//返信データ作成
//画像の場合、サーバーに保存
if($type == "image"){
  //画像ファイルのバイナリ取得
  $ch = curl_init("https://api.line.me/v2/bot/message/".$messageId."/content");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
   'Content-Type: application/json; charser=UTF-8',
   'Authorization: Bearer ' . $accessToken
   ));
  $result = curl_exec($ch);
  curl_close($ch);
  //画像ファイルの作成
  $filename = date('Ymd-His').'.jpg';
  $filemessage = '';
  $fp = fopen('./img/'.$filename, 'wb');
  if ($fp){
      if (flock($fp, LOCK_EX)){
          if (fwrite($fp,  $result ) === FALSE){
              $filemessage = '画像の受け取りに失敗しました';
          }else{
              $filemessage = '画像を受け取りました！';
          }
          flock($fp, LOCK_UN);
      }else{
          $filemessage = '画像の受け取りに失敗しました';
      }
  }
  fclose($fp);
  $filePath = "https://".$_SERVER['SERVER_NAME'] . "/img/".$filename;
  $imagetag = '<img src="'.$filePath.'">';
  //確認メッセージを送信
  $response_format_text = [
    "type" => "text",
    "text" => $filemessage
  ];
	$massage0 = '（画像添付）';
	$email->setSubject($messageId)
		  ->setHtml('[tags '.$userId.']'.$massage1.$filemessage.$massageend.$massage2.$massage0.$massageend.$imagetag);
	$sendgrid->send($email);
	
} else if (strpos($text,'購入予定です（') !== false) {
  $response_format_text = [
    "type" => "template",
	"altText" => "購入前",
    "template" => [
      "type" => "buttons",
	  "title" => "ショップへ問い合わせ",
      "text" => $massageshop."楽天市場店のお問い合わせフォームが開きます",
      "actions" => [
          [
            "type" => "uri",
            "label" => "問い合わせ",
            "uri" => "https://ask.step.rakuten.co.jp/inquiry-form/?page=simple-inquiry-top&act=login&shop_id=".$massagecat
          ]
      ]
    ]
  ];
} else if (strpos($text,'で注文済です') !== false) {
  $response_format_text = [
    "type" => "template",
	"altText" => "購入前",
    "template" => [
      "type" => "buttons",
	  "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img/rakuten01.png",
	  "imageAspectRatio" => "square",
	  "title" => "ショップへ問い合わせ",
      "text" => "楽天市場店の購入履歴よりお問い合わせください",
      "actions" => [
          [
            "type" => "uri",
            "label" => "購入履歴",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ],
          [
            "type" => "uri",
            "label" => "購入履歴",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ],
          [
            "type" => "uri",
            "label" => "購入履歴",
            "uri" => "https://sp.order.my.rakuten.co.jp/?fidomy=1"
          ],
          [
            "type" => "message",
            "label" => "よくある質問",
            "text" => $massageshop."のよくある質問を見たい"
          ]
      ]
    ]
  ];
} else if (strpos($text,'のよくある質問を見たい') !== false) {
  $response_format_text = [
    "type" => "template",
	"altText" => "質問",
    "template" => [
      "type" => "buttons",
	  "title" => "よくある質問",
      "text" => "よくある質問はこちら",
      "actions" => [
          [
            "type" => "message",
            "label" => "配送状況を確認したい",
            "text" => $massageshop."の納期や配送状況を知りたい⚠"
          ],
          [
            "type" => "message",
            "label" => "決済について確認したい",
            "text" => $massageshop."の商品の返品・交換・キャンセルをしたい⚠"
          ],
          [
            "type" => "message",
            "label" => "返品や交換、キャンセルしたい",
            "text" => $massageshop."の領収書発行について⚠"
          ],
          [
            "type" => "message",
            "label" => "その他",
            "text" => $massageshop."その他よくある質問⚠"
          ]
      ]
    ]
  ];
} else if (strpos($text,'316908') !== false){
  $massage0 = $text;
  $email->setSubject($messageId)
        ->setHtml('[category rakuten07][tags '.$userId.']'.$massage2.$massage0.$massageend);
  $sendgrid->send($email);
} else if (strpos($text,'316906') !== false){
  $massage0 = $text;
  $email->setSubject($messageId)
        ->setHtml('[category rakuten08][tags '.$userId.']'.$massage2.$massage0.$massageend);
  $sendgrid->send($email);
} else if (strpos($text,'⚠') !== false){
  exit;
} else {
  $response_format_text = [
    "type" => "template",
	"altText" => "default",
    "template" => [
        "type" => "buttons",
        "text" => "このアカウントは自動応答のみでのご対応になります。\nご質問がある場合、ご利用店舗とお客様の情報を選択してください。\n①サニープライズ\n②ハッピーサニーショップ",
        "actions" => [
            [
              "type" => "message",
              "label" => "①でご購入予定",
              "text" => "購入予定です（サニープライズ）"
            ],
            [
              "type" => "message",
              "label" => "①でご注文済",
              "text" => "サニープライズで注文済です"
            ],
            [
              "type" => "message",
              "label" => "②でご購入予定",
              "text" => "購入予定です（ハッピーサニーショップ）"
            ],
            [
              "type" => "message",
              "label" => "②でご注文済",
              "text" => "ハッピーサニーショップで注文済です"
            ]
        ]
    ]
  ];
}

$post_data = [
	"replyToken" => $replyToken,
	"messages" => [$response_format_text]
	];

$ch = curl_init("https://api.line.me/v2/bot/message/reply");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json; charser=UTF-8',
    'Authorization: Bearer ' . $accessToken
    ));
$result = curl_exec($ch);
curl_close($ch);
