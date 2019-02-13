<?php
use Gt\DomTemplate\HTMLDocument;

require("vendor/autoload.php");
require("messages.php");
session_start();

$document = new HTMLDocument(
	file_get_contents("template/chat-page.html")
);
$document->extractTemplates();

if($_SERVER["HTTP_ACCEPT"] === "text/event-stream") {
	header("Content-type: text/event-stream");
	header("Transfer-Encoding: chunked");
	header("Cache-control: no-cache");

	do {
		$now = time();
		$messages = getMessages($lastChecked ?? $now);

		if(!empty($messages)) {
			$chatElement = $document->getTemplate("/html/body/form/ul/li");
			$sseOutput = $document->createDocumentFragment();
			$sseOutput->appendChild($chatElement);

			$obj = new StdClass();
			$obj->appendTo = "#message-list";
			$obj->html = $sseOutput->innerHTML;

			$data = json_encode($obj);

			echo "id: $now\n";
			echo "event: newchat\n";
			echo "data: $data\n";
			echo "\n";
			ob_flush();
			flush();
		}

		$lastChecked = time();
		sleep(1);
	}
	while(!connection_aborted());
	exit;
}

if(!empty($_POST["message"])) {
	saveMessage($_POST["user"], $_POST["message"]);
	$_SESSION["user"] = $_POST["user"];
	header("Location: /");
	exit;
}
if(!empty($_SESSION["user"])) {
	$nameInput = $document->querySelector("input[name='user']");
	$nameInput->value = $_SESSION["user"];
	$nameInput->setAttribute("readonly", true);
}

//$document->getElementById("message-list")->bind(getMessages());
echo $document;