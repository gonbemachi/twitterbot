<?php
require __DIR__ . '/vendor/autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

// 使用するファイルの場所
const COUNTFILE = "count.csv"; // ツイート文数や、カウンタを管理
const TWEETFILE = "tweetlist.csv"; // つぶやく内容を管理
const AUTHFILE = "authdata.json"; // Twitter APIの情報を管理

//Twitter Developer Portalから取得したキーを設定
$authdata = json_decode(file_get_contents(AUTHFILE), true);
$consumer_key = $authdata["API_KEY"];
$consumer_secret = $authdata["API_SECRET_KEY"];
$access_token = $authdata["ACCESS_TOKEN"];
$access_token_secret = $authdata["ACCESS_TOKEN_SECRET"];

//////////////////////////////////////////////////////////////////////////////
// ツイート作成
//////////////////////////////////////////////////////////////////////////////
// ツイートインデックスがゼロの時に
// つぶやきリストをシャッフルし、ツイート数を保存。
// 先頭から順にツイートしていき、管理ファイルのツイート数ぶんツイートすると
// 次つぶやくツイートインデックスをゼロに戻す
//////////////////////////////////////////////////////////////////////////////
// カウントファイルを読み込み
// ファイル構成
//　ツイート数, 次つぶやくツイートインデックス（初期値ゼロ）
$fp = fopen(COUNTFILE, "r");
$cntdata = fgetcsv($fp);
fclose($fp);

$twilist = getList();

//インデックスがゼロの時にツイートリストのシャッフルとツイート数の保存を行う。
if ($cntdata[1] == 0) {
    shuffleList($twilist);
    writeList($twilist);
    resetCount(count($twilist));
}

//リストからつぶやく内容を生成
$content = getTweet($twilist[intval($cntdata[1])]);

//API認証し、APIバージョンを設定
$client = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
$client->setApiVersion('2');

//ツイート
$client->post('tweets', ['text' => $content]);

//カウンタを更新
setCount($cntdata[0], ++$cntdata[1]);

exit;

abstract class tweet
{
    abstract function originalTweet();
}
// ツイート文作成
// 文例
// 今日の一節
//
//
// ～歌詞～
//
// 頑張っていきましょう！
//
// 曲名
// アーティスト
// ////////////////////////////////////////////////////////////////////////////
// インプット形式（csv）
// 歌詞（半角スペースは改行として扱う）, 曲名, アーティスト
function getTweet($data)
{
    $splitdata = explode(",", $data);
    $lyric = $splitdata[0];
    $title = $splitdata[1];
    $artist = $splitdata[2];

    $lyric = str_replace(" ", "\n", $lyric);

    return "今日の一節\n\n\n" . $lyric . "\n\n\n\n頑張っていきましょう！\n\n" . $title . "\n" . $artist;
}

// カウントファイルを設定
// ツイート数、インデックスを設定
function setCount($cnt, $index)
{
    $line = "$cnt,$index\n";
    $fp = fopen(COUNTFILE, "w");
    fwrite($fp, $line);
    fclose($fp);
}
// ツイートリスト数をもらい、カウントファイルを初期化
function resetCount($cnt)
{
    setCount($cnt, 0);
}
//ツイートのリストを並び替え
// シャッフル前の最後のツイートと、シャッフル後の最初のツイートを
// 比較し、同じ場合は再度シャッフルする。
// 同じ内容を連続ツイートできないため。
function shuffleList(&$twilist)
{
    //最後（直前）のツイートを取得
    $last = $twilist[count($twilist) - 1];

    // 先頭ツイートと直前ツイートが異なるまでシャッフルを繰り返す。
    do {
        shuffle($twilist);
        $first = $twilist[0];
    } while (strcmp($last, $first) == 0);
}
// 引数のデータを書き込み
function writeList($twilist)
{
    // 更新前ファイルをリネームしてバックアップ
    rename(TWEETFILE, TWEETFILE . ".old");

    $fp = fopen(TWEETFILE, "w");
    foreach ($twilist as $line) {
        fwrite($fp, $line);
    }
    fclose($fp);
}
// csvファイルからツイートリストを取得。
function getList()
{
    $fp = fopen(TWEETFILE, "r");
    $i = 0;
    while ($line = fgets($fp)) {
        $twilist[$i++] = $line;
    }
    fclose($fp);
    return $twilist;
}
