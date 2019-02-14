(function() {

let outputTo = document.currentScript.previousElementSibling;
let sse = new EventSource("/");
sse.addEventListener("newchat", function(event) {
	outputTo.innerHTML += event.data;
});

})();