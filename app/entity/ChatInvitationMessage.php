<?php

declare(strict_types=1);

namespace app\entity;

class ChatInvitationMessage
{
  /** 聊天室ID */
  public $chatroomId;
  /** 聊天室名称 */
  public $name;
  /** 聊天室描述 */
  public $description;
  /** 聊天室头像 */
  public $avatarThumbnail;

  public function __construct(int $chatroomId)
  {
    $this->chatroomId = $chatroomId;
  }
}
