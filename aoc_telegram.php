<?php
$year = date("Y");
$day = date("j");
$botName = '@AocElfBot';

if (!\file_exists('madeline.php')) {
    \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$settings = [
    'logger' => [
        'logger_level' => \danog\MadelineProto\Logger::ERROR
    ]
];

$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);

$MadelineProto->start();

$dialogs = $MadelineProto->getFullDialogs();
foreach ($dialogs as $peerCandidate) {
    $chat = $MadelineProto->getInfo($peerCandidate);
    if (($chat["Chat"]["title"] ?? "") == "AoC $year Day $day") {
        $peer = $chat["Peer"];
        break;
    }
}

if (empty($peer)) {
    echo "Creating supergroup AoC $year Day $day\n";
    $updates = $MadelineProto->channels->createChannel([
        'broadcast' => false, 
        'megagroup' => true, 
        'title' => "AoC $year Day $day",
        'about' => "Advent of Code $year day $day discussion"
    ]);
    $peer = [
        "_" => "peerChannel",
        "channel_id" => $updates["chats"][0]["id"]
    ];
} else {
    echo "Found peer\n";
    var_dump($peer);
}


$MessageMedia = $MadelineProto->messages->uploadMedia([
    'peer' => $peer,
    'media' => [
        '_' => 'inputMediaUploadedPhoto',
        'file' => sprintf("res/aoc%02d.png", $day)
    ],
]);

$MadelineProto->channels->editPhoto([
    'channel' => $peer,
    'photo' => ['_' => 'inputChatPhoto', 'id' => $MessageMedia]
]);

$MadelineProto->messages->editChatDefaultBannedRights(['peer' => $peer, 'banned_rights' => [
    '_' => 'chatBannedRights',
    'until_date' => 0,
    'invite_users' => false,
    'pin_messages' => false,
    'change_info' =>false
]]);

$MadelineProto->channels->inviteToChannel(['channel' => $peer, 'users' => [$botName]]);
$MadelineProto->channels->editAdmin(['channel' => $peer, 'user_id' => $botName, 'admin_rights' => [
    '_' => 'chatAdminRights',
    'change_info' => true,
    'post_messages' => true,
    'edit_messages' => true,
    'delete_messages' => true,
    'ban_users' => true,
    'invite_users' => true,
    'pin_messages' => true,
    'add_admins' => true,
    'anonymous' => false,
    'manage_call' => false,
    'other' => false
], 'rank' => 'admin']);

