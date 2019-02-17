<div class="content">
    <div class="users">
        <ul id="users-list">
            <!-- здесь будут пользователи -->
        </ul>
    </div><!--

 --><div class="chat">
        <div class="message-history" id="message-history-div">
            <ul id="message-list">
                <?php foreach($messages as $message): ?>
                    <li data-message-id="<?php echo $message['id'] ?>">
                        <span class="message-title">
                            <?php echo "{$message['created_at']} {$message['author']['username']}:" ?>
                        </span>
                        <span class="message-content">
                            <?php echo "{$message['content']}" ?>
                        </span>
                        <span class="message-likes">
                            🖤 <small class="like-count"><?php echo $message['likes'] ?></small>
                        </span>
                        <?php if(isset($_COOKIE['username']) && $_COOKIE['username'] == $message['author']['username']): ?>
                            <span class="message-remove">
                                ❌
                            </span>
                        <?php endif ?>
                        <?php if(isset($message['attachments'])): ?>
                        <span class="message-attachments">
                            <?php foreach($message['attach'] as $attachment): ?>
                                <?php switch($attachment['type']) {
                                    case 'img':
                                        echo "<img src='/main/getAttachment/{$attachment['id']}' />";
                                        break;
                                    case 'link':
                                        echo "<a target='_blank' href='{$attachment['link']}'>{$attachment['link']}</a>";
                                        break;
                                    case 'youtube':
                                        echo '<iframe width="320" src="'.$attachment['link'].'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                        break;
                                } ?>
                            <?php endforeach ?>
                        <?php endif ?>
                    </span>
                    </li>
                <?php endforeach ?>
            </ul>

            <br />
        </div>

        <div class="message-input">
            <div id="imgPreview">
            </div>
            <div id="linkPreview">
            </div>
            <div id="youtubePreview">
            </div>

            <input type="text" id="message-content" />
            <input type='file' id="imgInput" accept="image/jpeg,image/png,image/gif" multiple />
            <button id="message-send">Отправить сообщение</button>
        </div>
    </div>
</div>