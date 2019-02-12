(function () {
    // ======== private vars ========
    let socket;

    let init = function () {

        socket = new WebSocket("ws://pg-dev.loc:8000");

        socket.onmessage = messageReceived;

        document.getElementById("message-send").onclick = function () {
            socket.send(JSON.stringify({
                author: 1,
                type: 'message',
                content: "test message " + (new Date()).toTimeString()
            }));
        };

    };

    function messageReceived(e) {
        let message = JSON.parse(e.data);

        switch(message.type) {
            case 'info':
                console.info(message.content);
                break;
            case 'warning':
                console.warn(message.content);
                break;
            case 'error':
                console.error(message.content);
                break;
            default:
                console.log(message.content);
        }
    }


    return {
        // ---- onload event ----
        load : function () {
            window.addEventListener('load', function () {
                init();
            }, false);
        }
    }
})().load();