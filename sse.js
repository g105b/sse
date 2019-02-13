(function() {

let sse = new EventSource("/");
sse.onmessage = function(event) {
	console.log(event);
}

})();