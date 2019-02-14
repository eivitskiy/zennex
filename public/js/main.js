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
                type: 'message',
                content: document.getElementById("message-content").value
            }));

            document.getElementById("message-content").value = null;
        };


        let likes_elements = document.getElementsByClassName('message-likes');
        for(let i = 0; i < likes_elements.length; i++) {
            likes_elements[i].onclick = toLikeMsg;
        }

        let remove_elements = document.getElementsByClassName('message-remove');
        for(let i = 0; i < remove_elements.length; i++) {
            remove_elements[i].onclick = removeMsg;
        }
    };

    function getCookie(name) {
        let matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : false;
    }

    function checkNickname() {
        if(!getCookie('username')) {
            let username;
            do {
                username = prompt('Укажите ваш никнейм (не менее 5 символов)')
            } while(username && username.length < 5);
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
            span_remove = document.createElement('span'),
            span_attachments = document.createElement('span');

        span_title.className += 'message-title';
        span_title.innerHTML = message.created_at + ' ' + message.author.username + ': ';

        span_content.className += 'message-content';
        span_content.innerHTML = message.content;

        span_likes.className += 'message-likes';
        span_likes.innerHTML = '🖤 <small class="like-count">'+message.likes+'</small>';
        span_likes.onclick = toLikeMsg;

        li.setAttribute('data-message-id', message.id);

        li.appendChild(span_title);
        li.appendChild(span_content);
        li.appendChild(span_likes);

        if(message.author.username == getCookie('username')) {
            span_remove.className += 'message-remove';
            span_remove.innerHTML = '❌';
            span_remove.onclick = removeMsg;
            li.appendChild(span_remove);
        }

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

    function toLikeMsg() {
        socket.send(JSON.stringify({
            type: 'like',
            message_id: this.parentElement.getAttribute('data-message-id')
        }));
    }

    function removeMsg() {
        socket.send(JSON.stringify({
            type: 'remove',
            message_id: this.parentElement.getAttribute('data-message-id')
        }));
    }

    function liked(message) {
        let span_liked = document.querySelectorAll("[data-message-id='"+message.id+"'] > span.message-likes > small.like-count")[0];
        span_liked.innerHTML = message.likes;
    }

    function removed(message) {
        let span_removed = document.querySelectorAll("[data-message-id='"+message.id+"']")[0];
        span_removed.innerHTML = 'Сообщение было удалено';
    }

    function changeUsername() {
        let username;
        do {
            username = prompt('Этот никнейм уже занят\nУкажите другой никнейм (не менее 5 символов)')
        } while(username && username.length < 5);

        document.cookie = "username="+username;

        location.reload();
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
            case 'removed':
                removed(message);
                break;
            case 'changeUsername':
                changeUsername();
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
                init();
            }, false);
        }
    }
})().load();