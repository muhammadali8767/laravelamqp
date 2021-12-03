<?php
if (!empty($_REQUEST)) {
    $responce = [
        "method" => $_SERVER['REQUEST_METHOD'],
        "time" => date("Y m d H:i:s"),
        "data" => $_REQUES
    ];

    $file = file_get_contents('request.json');
    $requestArray = json_decode($file) ?? [];
    array_push($requestArray, $responce);
    $request = json_encode($array);
    echo json_encode($responce);

    file_put_contents('request.json', $request);
}
