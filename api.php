<?php

if (!empty($_POST)) {
    $post = json_encode($_POST);
    file_put_contents('post.txt', $post);
}

if (!empty($_GET)) {
    $get = json_encode($_GET);
    file_put_contents('get.txt', $get);
}
