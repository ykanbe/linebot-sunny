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
//ユーザー名取得
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
$response = $bot->getProfile($userId);
$profile = $response->getJSONDecodedBody();
$userName = $profile['displayName'];
//ReplyToken取得
$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};

//Sendgrid-1
require __DIR__ . '/../vendor/autoload.php';
$sendgrid = new SendGrid(getenv('SENDGRID_USERNAME'), getenv('SENDGRID_PASSWORD'));
$email    = new SendGrid\Email();
$email->addTo('wpbot@azo.jp')
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
  //確認メッセージを送信
  $response_format_text = [
    "type" => "text",
    "text" => $filemessage."\nhttps://".$_SERVER['SERVER_NAME'] . "/img/".$filename
  ];
  
	//Sendgrid-2
	$email->setSubject('[rakuten03]' . $messageId)
		  ->setText('tags:' . $userId . '<br>' . $userName);
	$sendgrid->send($email);
	
} else if ($text == 'はい') {
  $response_format_text = [
    "type" => "template",
    "altText" => "こちらの〇〇はいかがですか？",
    "template" => [
      "type" => "buttons",
      "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img1.jpg",
      "title" => "○○レストラン",
      "text" => "お探しのレストランはこれですね",
      "actions" => [
          [
            "type" => "postback",
            "label" => "予約する",
            "data" => "action=buy&itemid=123"
          ],
          [
            "type" => "postback",
            "label" => "電話する",
            "data" => "action=pcall&itemid=123"
          ],
          [
            "type" => "uri",
            "label" => "詳しく見る",
            "uri" => "https://" . $_SERVER['SERVER_NAME'] . "/"
          ],
          [
            "type" => "message",
            "label" => "違うやつ",
            "text" => "違うやつお願い"
          ]
      ]
    ]
  ];
} else if ($text == 'いいえ') {
  exit;
} else if (strpos($text,'bc') !== false){
  $response_format_text = [
    "type" => "text",
    "text" => "Hello, world"
  ];
} else if ($text == '違うやつお願い') {
  $response_format_text = [
    "type" => "template",
    "altText" => "候補を３つご案内しています。",
    "template" => [
      "type" => "carousel",
      "columns" => [
          [
            "thumbnailImageUrl" => "https://" . $_SERVER['SERVER_NAME'] . "/img2-1.jpg",
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
} else {
  $response_format_text = [
    "type" => "template",
    "altText" => "こんにちわ 何かご用ですか？（はい／いいえ）",
    "template" => [
        "type" => "confirm",
        "text" => "メッセージありがとうございます".chr(0x10000A)."\nこのアカウントは自動応答のみで対応しています(cellphone)\n何かご質問がございますか？",
        "actions" => [
            [
              "type" => "message",
              "label" => "はい",
              "text" => "はい"
            ],
            [
              "type" => "message",
              "label" => "いいえ",
              "text" => "いいえ"
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
