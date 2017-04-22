<?php

error_reporting(1);
ini_set('display_errors', 0);

date_default_timezone_set("Asia/Tashkent");

require_once "logger/Log.php";

require_once "http/Router.php";
require_once "http/Request.php";
require_once "http/Response.php";

require_once "router/UserRouter.php";

require_once "database/PersistentModel.php";
require_once "database/DataSource.php";

require_once "domain/Account.php";

$dispatcher = new RouterDispatcher();
$dispatcher->onErrorReturn(function (Exception $error, Request $req, Response $res) {
    /** @var $res Response */
    Log::write("debug", "RequestDispatcher", $error);

    $htmlTemplate = "<h1>{$error->getCode()} {$error->getMessage()}</h1>";
    $htmlTemplate .= "<strong style='font-size: 18px'>{$error->getMessage()}</strong>";
    $htmlTemplate .= "<pre>";
    foreach ($error->getTrace() as $trace) {
        $line = "";
        foreach ($trace as $key => $value) {
            $line .= "<strong>{$key}</strong>: " . var_export($value, true);
        }
        $htmlTemplate .= "{$line}\n";
    }
    $htmlTemplate .= "</pre>";

    $res->status($error->getCode())
        ->setContentType("text/html")
        ->send($htmlTemplate);
});

$dispatcher->middleware(function (Request $req, Response $res, Chain $chain) {
    Log::write("debug", "RequestDispatcher",
        "{$req->method()} {$req->path()} \n\t" . json_encode($req->body()));

    $chain->proceed($req, $res);
});

$dispatcher->path("GET", '', function (Request $req, Response $res, Chain $chain) {
    /**
     * @var $req Request
     * @var $res Response
     * @var $chain Chain
     */

    $res->status(200)->json(array("status" => "ok"));

    $chain->proceed($req, $res);
});

$users = new UserRouter();
$dispatcher->route('users', $users->dispatcher());

$dispatcher->middleware(function (Request $req, Response $res, Chain $chain) {
    if (!$res->hasBody()) {
        $res->status(404)->setContentType("text/html")->send("404, Not found");
    }

    $chain->proceed($req, $res);
});

$dispatcher->middleware(function (Request $req, Response $res) {
    $response = "{$req->protocol()} {$res->getStatusCode()}";
    foreach ($res->getHeaders() as $key => $value) {
        $response .= "\n{$key}: {$value}";
    }
    $response .= "\n{$res->body()}";
    Log::write("debug", "ResponseDispatcher", $response);
});

$dispatcher->start();

?>