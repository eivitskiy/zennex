(function () {
    // ======== private vars ========
    let socket;

    let init = function () {

        checkNickname();

        socket = new WebSocket("ws://pg-dev.loc:8000");

        socket.onmessage = messageReceived;
        socket.onclose = onClose;

        document.getElementById('message-content').oninput = function() {
            if(checkValidURL(this.value)) {
                let youtubeID = checkYoutubeURL(this.value);
                if(youtubeID) {
                    let youtubePreview = document.getElementById("youtubePreview");

                    let newDivYoutube = document.createElement('div');
                    newDivYoutube.className = 'uploaded-youtube';

                    let newRemoveBtn = document.createElement('span');
                    newRemoveBtn.innerHTML = 'x';
                    newRemoveBtn.onclick = uploadedLinkRemove;

                    let newYoutube = document.createElement("img");
                    newYoutube.setAttribute('src', 'https://img.youtube.com/vi/'+youtubeID+'/0.jpg');
                    newYoutube.setAttribute('data-id', youtubeID);
                    newYoutube.innerHTML = this.value;

                    newDivYoutube.appendChild(newRemoveBtn);
                    newDivYoutube.appendChild(newYoutube);

                    youtubePreview.appendChild(newDivYoutube);
                } else {
                    let linkPreview = document.getElementById("linkPreview");

                    let newDivLink = document.createElement('div');
                    newDivLink.className = 'uploaded-link';

                    let newRemoveBtn = document.createElement('span');
                    newRemoveBtn.innerHTML = 'x';
                    newRemoveBtn.onclick = uploadedLinkRemove;

                    let newLink = document.createElement("a");
                    newLink.setAttribute('href', this.value);
                    newLink.setAttribute('target', '_blank');
                    newLink.innerHTML = this.value;

                    newDivLink.appendChild(newRemoveBtn);
                    newDivLink.appendChild(newLink);

                    linkPreview.appendChild(newDivLink);
                }
            }
        };

        document.getElementById("message-send").onclick = function () {

            let msgInput = document.getElementById('message-content');
            let imgInput = document.getElementById('imgInput');
            let linkInput = document.querySelectorAll('.uploaded-link a');
            let youtubeInput = document.querySelectorAll('.uploaded-youtube img');

            if(msgInput.value.length > 0 || imgInput.files.length > 0 || linkInput.length > 0 || youtubeInput.length > 0) {
                let message = {
                    type: 'message',
                    content: msgInput.value
                };

                if(imgInput.files.length > 0 || linkInput.length > 0 || youtubeInput.length > 0) {
                    let formData = new FormData();

                    for(let i = 0; i < imgInput.files.length; i++) {
                        formData.append('img_'+i, imgInput.files[i]);
                    }

                    for(let i = 0; i < linkInput.length; i++) {
                        formData.append('link['+i+']', linkInput[i].getAttribute('href'));
                    }

                    for(let i = 0; i < youtubeInput.length; i++) {
                        formData.append('youtube['+i+']', 'https://www.youtube.com/embed/'+youtubeInput[i].getAttribute('data-id'));
                    }

                    let xhr = new XMLHttpRequest();
                    xhr.open('POST', '/main/attachmentsUpload', false);
                    xhr.send(formData);

                    if (xhr.status !== 200) {
                        alert( xhr.status + ': ' + xhr.statusText );
                        return ;
                    } else {
                        message.attachments = xhr.responseText;
                    }
                }

                socket.send(JSON.stringify(message));

                msgInput.value = null;
                imgInput.value = null;
                document.getElementById('imgPreview').innerHTML = null;
                document.getElementById('linkPreview').innerHTML = null;
                document.getElementById('youtubePreview').innerHTML = null;
            }
        };


        let likes_elements = document.getElementsByClassName('message-likes');
        for(let i = 0; i < likes_elements.length; i++) {
            likes_elements[i].onclick = toLikeMsg;
        }

        let remove_elements = document.getElementsByClassName('message-remove');
        for(let i = 0; i < remove_elements.length; i++) {
            remove_elements[i].onclick = removeMsg;
        }

        document.getElementById("imgInput").onchange = function(e) {
            readURL(this);
        };
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

    function uploadedLinkRemove() {
        this.parentElement.remove();
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

        if(message.author.username === getCookie('username')) {
            span_remove.className += 'message-remove';
            span_remove.innerHTML = '❌';
            span_remove.onclick = removeMsg;
            li.appendChild(span_remove);
        }

        if(message['attach'] !== undefined) {
            for(let i = 0; i < message['attach'].length; i++) {
                span_attachments.className += 'message-attachments';
                switch(message['attach'][i]['type']) {
                    case 'img':
                        let attachImg = document.createElement('img');
                        attachImg.setAttribute('src', '/main/getAttachment/'+message['attach'][i]['id']);
                        span_attachments.appendChild(attachImg);
                        break;
                    case 'link':
                        let attachLink = document.createElement('a');
                        attachLink.setAttribute('target', '_blank');
                        attachLink.setAttribute('href', message['attach'][i]['link']);
                        attachLink.innerHTML = message['attach'][i]['link'];
                        span_attachments.appendChild(attachLink);
                        break;
                    case 'youtube':
                        let attachYoutube = document.createElement('iframe');
                        attachYoutube.setAttribute('width', '320');
                        attachYoutube.setAttribute('src', message['attach'][i]['link']);
                        attachYoutube.setAttribute('frameborder', '0');
                        attachYoutube.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
                        attachYoutube.setAttribute('allowfullscreen', true);
                        span_attachments.appendChild(attachYoutube);
                }
                li.appendChild(span_attachments);
            }
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
        // console.log('соединение закрылось', e);
    }

    function readURL(input) {
        if (input.files && input.files[0]) {
            let imgPreview = document.getElementById("imgPreview");
            imgPreview.innerHTML = '';
            for (let i = 0; i < input.files.length; i++) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    let newImg = document.createElement("img");
                    newImg.className = 'upload-image';
                    newImg.setAttribute('src', e.target.result);
                    imgPreview.appendChild(newImg);
                };

                reader.readAsDataURL(input.files[i]);
            }
        }
    }

    function checkValidURL(str) {
        let pattern = new RegExp(/^(?:(?:https?|ftp):\/\/)(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/\S*)?$/);
        if(!pattern.test(str)) {
            return false;
        } else {
            return true;
        }
    }

    function checkYoutubeURL(str) {
        if (str !== undefined || str !== '') {
            let regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
            let match = str.match(regExp);
            if (match && match[2].length === 11) {
                return match[2];
            }
            else {
                return false;
            }
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