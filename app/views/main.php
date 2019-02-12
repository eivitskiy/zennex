<div class="content">
    <div class="user-list">
        <ul>
            <?php foreach($users as $user): ?>
                <li><?php echo $user['name'] ?></li>
            <?php endforeach ?>
        </ul>
    </div><!--

 --><div class="chat">
        <div class="message-history">
            <ul>
                <?php foreach($messages as $message): ?>
                    <li>
                        <span class="message-title">
                            <?php echo "{$message['created_at']} {$message['author']}:" ?>
                        </span>
                        <span class="message-content">
                            <?php echo "{$message['content']}" ?>
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
            <button id="message-send">Отправить сообщение</button>
        </div>
    </div>
</div>