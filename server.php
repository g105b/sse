<?php
require("vendor/autoload.php");
require("messages.php");

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server as HttpServer;
use Gt\DomTemplate\HTMLDocument;
use React\Stream\ThroughStream;
use React\Socket\Server;

$loop = React\EventLoop\Factory::create();

$server = new HttpServer(function (ServerRequestInterface $request) use($loop) {
	$document = new HTMLDocument(
		file_get_contents("template/chat-page.html")
	);
	$document->extractTemplates();

	$accept = $request->getHeaderLine("Accept");
	if($accept === "text/event-stream") {
		$stream = new ThroughStream();
		$lastCheckedTime = time();

		$loop->addPeriodicTimer(1, function()use($stream, $document, &$lastCheckedTime) {
			foreach(getMessages($lastCheckedTime) as $message) {
				$html = getMessageHTML(
					$document,
					$message
				);

				$stream->write("id: $message[timestamp]\n");
				$stream->write("event: newchat\n");
				$stream->write("data: $html\n");
				$stream->write("\n");

				$lastCheckedTime = $message["timestamp"];
			}
		});

		return new Response(
			200,
			[
				"Content-Type" => "text/event-stream",
				"Transfer-Encoding" => "chunked",
				"Cache-control" => "no-cache",
			],
			$stream
		);
	}

	$uri = $request->getUri()->getPath();
	switch($uri) {
	case "/style.css":
		return new Response(
			200,
			[
				"Content-Type" => "text/css"
			],
			file_get_contents(__DIR__ . "/style.css")
		);
		break;

	case "/sse.js":
		return new Response(
			200,
			[
				"Content-Type" => "text/javascript",
			],
			file_get_contents(__DIR__ . "/sse.js")
		);
		break;
	}

	$headers = [
		"Content-Type" => "text/html",
	];

	$user = null;
	$data = $request->getParsedBody();
	if(!empty($data)) {
		saveMessage($data["user"], $data["message"]);
		$headers["Set-Cookie"] = "user=$data[user]";
		$user = $data["user"];
	}

	$document->getElementById("message-list")->bind(getMessages());

	$cookie = $request->getCookieParams();
	$user = $user ?? $cookie["user"] ?? null;
	if($user) {
		$nameInput = $document->querySelector("input[name='user']");
		$nameInput->value = $user;
		$nameInput->setAttribute("readonly", true);
	}


	return new Response(
		200,
		$headers,
		$document
	);
});

$socket = new Server(8080, $loop);
$server->listen($socket);

$loop->run();