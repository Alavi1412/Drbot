<?php
/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 2/27/17
 * Time: 7:48 AM
 */
function makeCurl($method,$datas=[])    //make and receive requests to bot
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot358879899:AAGukK4oGIpoK7c4s66ghQYkNLM46_T-fHE/{$method}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($datas));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec ($ch);
    curl_close ($ch);
    return $server_output;
}
$db;                            //global database connect
$level;                         //user level
$user_id;                       //user unique user id.find in main function in each update
$text;                          //text that user sent.sometimes the callback data of inline keyboard
$username;                      //user telegram username
$message_id;                    //message_id of button that user pressed
$question;                      //the question should be asked from user
$user_firstname;                //user first name;
$locale;                        //user language
$last_updated_id = 0;           //should be removed
$db=mysqli_connect("localhost","root","root","padporsc_drbot");
function levelFinder()          //find user's level and return it
{
    global $user_id;
    global $level;
    global $db;
    $b = 0;
    $result = mysqli_query($db,"SELECT * FROM padporsc_drbot.dr WHERE user_id={$user_id}");       //DOCTOR
    while($row = mysqli_fetch_array($result))
    {
        if($row['level'])
        {
            $level = $row['level'];
            $b = 1;
        }
    }
    if($b == 0)
        $level = "Begin";
}
function ask()
{
    global $db;
    global $user_id;
    global $level;
    $result = mysqli_query($db, "SELECT * FROM padporsc_drbot.questions WHERE name = 'alavi'");
    $row = mysqli_fetch_array($result);
    $question = $row['question'];
    if($level == "Begin")
        mysqli_query($db, "INSERT INTO padporsc_drbot.dr (user_id, level) VALUES ({$user_id}, 'asked')");
    else
        mysqli_query($db, "UPDATE padporsc_drbot.dr SET level = 'asked' WHERE user_id = {$user_id}");
    makeCurl("sendMessage", ["chat_id" => $user_id, "text" => $question]);

}
function answered()
{
    global $db;
    global $user_id;
    global $text;
    makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "ممنون ازین که به این سوال پاسخ دادی.", "reply_markup" => json_encode([
        "inline_keyboard" => [
            [
                ["text" => "پاسخ دوباره", "callback_data" => "Ahusdg5a456adsg"]
            ]
        ]
    ])]);
    //TODO mail the answer
    mail("content.padpors@gmail.com", "content", $text);
}
function set()
{
    global $user_id;
    global $text;
    global $db;
    mysqli_query($db, "UPDATE padporsc_drbot.questions SET question = \"{$text}\" WHERE name = 'alavi'");
    makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "سوال شما تغییر کرد:
     {$text}"]);
    $result = mysqli_query($db, "SELECT * FROM padporsc_drbot.dr");
    while($row = mysqli_fetch_array($result))
        makeCurl("sendMessage", ["chat_id" => $row['user_id'], "text" => "میتونی با زدن روی دکمه ی زیر سوال جدید رو ببینی و جواب بدی", "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    ["text" => "سوال جدید", "callback_data" => "Ahusdg5a456adsg"]
                ]
            ]
        ])]);
}
function main()
{
    global $level;
    global $user_id;
    global $text;
    global $username;
    global $user_firstname;
    global $message_id;
    global $last_updated_id;
    global $db;
//    $update = json_decode(file_get_contents("php://input"));          //should not be comment
    $updates = json_decode(makeCurl("getUpdates",["offset"=>($last_updated_id+1)]));        //should be removed
    if($updates->ok == true && count($updates->result) > 0) {               //should be removed
        foreach ($updates->result as $update) {                             //should be removed
            if ($update->callback_query) {
                makeCurl("answerCallbackQuery", ["callback_query_id" => $update->callback_query->id]);
                $text = $update->callback_query->data;
                $user_id = $update->callback_query->from->id;
                $user_firstname = $update->callback_query->from->first_name;
                $username = $update->callback_query->from->username;
                $message_id = $update->callback_query->message->message_id;
            } else {
                $text = $update->message->text;
                $user_id = $update->message->chat->id;
                $username = $update->message->from->username;
                $user_firstname = $update->message->from->first_name;
            }
            levelFinder();
            if ($user_id == 54654) {
                echo "here2";
                set();
            }
            elseif ($text == "Ahusdg5a456adsg" || $level == "Begin") {
                ask();
            }
            elseif ($level == "asked") {
                answered();
            }
            $last_updated_id = $update->update_id;              //should be removed
        }           //should be removed
    }               //should be removed
}
while(1) {
    main();
}