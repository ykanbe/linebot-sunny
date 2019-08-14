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
    "text" => $filemessage."\nhttps://".$_SERVER['SERVER_NAME'] . "/img/".$filename
  ];
	$massage0 = '（画像添付）';
	$email->setSubject($messageId)
		  ->setHtml('[category rakuten04][tags '.$userId.']'.$massage1.$filemessage.$massageend.$massage2.$massage0.$massageend.$imagetag);
	$sendgrid->send($email);
	
} else if ($text == '購入前です') {
  $response_format_text = [
    "type" => "template",
	"altText" => "購入前です",
    "template" => [
      "type" => "buttons",
      "text" => "納期や在庫確認のご質問以外の場合、楽天ショップのお問い合わせフォームを表示しますので、そちらにご入力をお願いいたします。\nサニープライズは①、ハッピーサニーショップは②を選択してください",
      "actions" => [
          [
            "type" => "message",
            "label" => "納期について",
            "text" => "商品の納期を知りたい"
          ],
          [
            "type" => "message",
            "label" => "在庫確認",
            "text" => "商品の在庫を知りたい"
          ],
          [
            "type" => "uri",
            "label" => "①その他",
            "uri" => "https://ask.step.rakuten.co.jp/inquiry-form/?page=simple-inquiry-top&act=login&shop_id=316908"
          ],
          [
            "type" => "uri",
            "label" => "②その他",
            "uri" => "https://ask.step.rakuten.co.jp/inquiry-form/?page=simple-inquiry-top&act=login&shop_id=316906"
          ]
      ]
    ]
  ];
} else if ($text == '購入済です') {
  $response_format_text = [
    "type" => "template",
	"altText" => "購入済です",
    "template" => [
      "type" => "carousel",
      "columns" => [
          [
            "title" => "●●レストラン",
            "text" => "こちらにしますか？",
            "actions" => [
              [
                  "type" => "postback",
                  "label" => "予約する",
                  "data" => "action=rsv&itemid=111"
              ],
              [
                  "type" => "postback",
                  "label" => "電話する",
                  "data" => "action=pcall&itemid=111"
              ],
              [
                  "type" => "uri",
                  "label" => "詳しく見る（ブラウザ起動）",
                  "uri" => "https://" . $_SERVER['SERVER_NAME'] . "/"
              ]
            ]
          ],
          [
            "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img2-2.jpg",
            "title" => "▲▲レストラン",
            "text" => "それともこちら？（２つ目）",
            "actions" => [
              [
                  "type" => "postback",
                  "label" => "予約する",
                  "data" => "action=rsv&itemid=222"
              ],
              [
                  "type" => "postback",
                  "label" => "電話する",
                  "data" => "action=pcall&itemid=222"
              ],
              [
                  "type" => "uri",
                  "label" => "詳しく見る（ブラウザ起動）",
                  "uri" => "https://" . $_SERVER['SERVER_NAME'] . "/"
              ]
            ]
          ],
          [
            "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img2-3.jpg",
            "title" => "■■レストラン",
            "text" => "はたまたこちら？（３つ目）",
            "actions" => [
              [
                  "type" => "postback",
                  "label" => "予約する",
                  "data" => "action=rsv&itemid=333"
              ],
              [
                  "type" => "postback",
                  "label" => "電話する",
                  "data" => "action=pcall&itemid=333"
              ],
              [
                  "type" => "uri",
                  "label" => "詳しく見る（ブラウザ起動）",
                  "uri" => "https://" . $_SERVER['SERVER_NAME'] . "/"
              ]
            ]
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
} else {
  $response_format_text = [
    "type" => "template",
	"altText" => "default",
    "template" => [
        "type" => "confirm",
        "text" => "メッセージありがとうございます。\nこのアカウントは自動応答のみでのご対応になります。\nご質問がある場合、お手数ですが下記より質問の回答をお願い致します。",
        "actions" => [
            [
              "type" => "message",
              "label" => "ご購入前",
              "text" => "購入前です"
            ],
            [
              "type" => "message",
              "label" => "ご購入済",
              "text" => "購入済です"
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
