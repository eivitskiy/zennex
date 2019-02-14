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
                            <?php echo "{$message['created_at']} {$message['author']}:" ?>
                        </span>
                        <span class="message-content">
                            <?php echo "{$message['content']}" ?>
                        </span>
                        <span class="message-likes" data-message-id="<?php echo $message['id'] ?>">
                            🖤 <small class="like-count"><?php echo $message['likes'] ?></small>
                        </span>
                        <?php if(isset($message['attachments'])): ?>
                        <span class="message-attachments">
                            <?php foreach($message['attachments'] as $attachment): ?>
                                <?php switch($attachment['type']) {
                                    case 'img':
                                        echo "<img src='{$attachment['url']}' alt='{$attachment['desc']}'/>";
                                        break;
                                    case 'link':
                                        echo "<a href='{$attachment['url']}'>{$attachment['desc']}</a>";
                                        break;
                                    case 'youtube':
                                        echo "ну а тут будет youtube ролик";
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
            <input type="text" id="message-content" />
            <button id="message-send">Отправить сообщение</button>
        </div>
    </div>
</div>