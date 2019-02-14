(function () {
    // ======== private vars ========
    let socket;

    let init = function () {

        checkNickname();

        socket = new WebSocket("ws://pg-dev.loc:8000");

        socket.onmessage = messageReceived;
        socket.onclose = onClose;

        document.getElementById("message-send").onclick = function () {
            socket.send(JSON.stringify({
                author: 1,
                type: 'message',
                content: document.getElementById("message-content").value
            }));

            document.getElementById("message-content").value = null;
        };

        // document.getElementsByClassName('message-likes').click = toLike;



        let likes_elements = document.getElementsByClassName('message-likes');
        for(let i = 0; i < likes_elements.length; i++) {
            likes_elements[i].onclick = toLike;
        }



        // setCookie('username', "", {
        //     expires: -1
        // });
    };

    function getCookie(name) {
        let matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : false;
    }

    // function setCookie(name, value, options) {
    //     options = options || {};
    //
    //     let expires = options.expires;
    //
    //     if (typeof expires == "number" && expires) {
    //         let d = new Date();
    //         d.setTime(d.getTime() + expires * 1000);
    //         expires = options.expires = d;
    //     }
    //     if (expires && expires.toUTCString) {
    //         options.expires = expires.toUTCString();
    //     }
    //
    //     value = encodeURIComponent(value);
    //
    //     let updatedCookie = name + "=" + value;
    //
    //     for (let propName in options) {
    //         updatedCookie += "; " + propName;
    //         let propValue = options[propName];
    //         if (propValue !== true) {
    //             updatedCookie += "=" + propValue;
    //         }
    //     }
    //
    //     document.cookie = updatedCookie;
    // }

    function checkNickname() {
        if(!getCookie('username')) {
            let username;
            do {
                username = prompt('Укажите ваш никнейм (не менее 5 символов)')
            } while(username.length < 5);
            document.cookie = "username=" + username;
        }
    }

    function scrollMessageHistoryDiv(){
        let div = document.getElementById("message-history-div");
        div.scrollTop = div.clientHeight;
    }

    function newMessage(message) {
        let ul = document.getElementById("message-list");
        let li = document.createElement("li");
        let span_title = document.createElement('span'),
            span_content = document.createElement('span'),
            span_likes = document.createElement('span'),
            span_attachments = document.createElement('span');

        span_title.className += 'message-title';
        span_title.innerHTML = message.created_at + ' ' + message.author + ': ';

        span_content.className += 'message-content';
        span_content.innerHTML = message.content;

        span_likes.className += 'message-likes';
        span_likes.innerHTML = '🖤 <small class="like-count">'+message.likes+'</small>';
        span_likes.onclick = toLike;

        li.setAttribute('data-message-id', message.id);

        li.appendChild(span_title);
        li.appendChild(span_content);
        li.appendChild(span_likes);

        if(message.attachments !== undefined) {
            span_attachments.className += 'message-attachments';
            span_attachments.innerHTML = 'здесь будут прикрепляшки';
            li.appendChild(span_attachments);
        }

        ul.appendChild(li);

        scrollMessageHistoryDiv();
    }

    function addUser(message) {
        let ul = document.getElementById("users-list");
        let li = document.createElement("li");

        li.innerHTML = message.username;

        li.setAttribute('data-user-id', message.id);

        ul.appendChild(li);
    }

    function removeUser(message) {
        let li = document.querySelectorAll("[data-user-id='"+message.id+"']")[0];
        li.remove();
    }

    function toLike() {
        socket.send(JSON.stringify({
            type: 'like',
            message_id: this.parentElement.getAttribute('data-message-id')
        }));
    }

    function liked(message) {
        console.log(message);
        let span_liked = document.querySelectorAll("[data-message-id='"+message.id+"'] > span.message-likes > small.like-count")[0];
        console.log(span_liked);
        span_liked.innerHTML = message.likes;
    }

    function messageReceived(e) {
        let message = JSON.parse(e.data);

        switch(message.type) {
            case 'message':
                newMessage(message);
                break;
            case 'addUser':
                addUser(message);
                break;
            case 'removeUser':
                removeUser(message);
                break;
            case 'liked':
                liked(message);
                break;
            case 'info':
                console.info(message.content);
                break;
            default:
                console.log(message);
        }
    }

    function onClose(e) {
        console.log('соединение закрылось', e);
    }


    return {
        // ---- onload event ----
        load : function () {
            window.addEventListener('load', function () {
                console.log('load', (new Date()).toTimeString());
                init();
            }, false);
        }
    }
})().load();